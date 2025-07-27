<?php
/**
 * 预约通知服务类
 * 处理预约相关的通知发送
 */
class AppointmentNotification {
    private $db;
    private $emailService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->emailService = new EmailService();
    }
    
    /**
     * 发送预约确认通知
     */
    public function sendAppointmentConfirmation($appointmentId) {
        $appointment = $this->getAppointmentDetails($appointmentId);
        if (!$appointment) {
            return false;
        }
        
        // 发送邮件通知
        $emailSent = $this->sendConfirmationEmail($appointment);
        
        // 发送短信通知（如果集成了短信服务）
        $smsSent = $this->sendConfirmationSMS($appointment);
        
        // 记录通知日志
        $this->logNotification($appointmentId, 'confirmation', [
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent
        ]);
        
        return $emailSent || $smsSent;
    }
    
    /**
     * 发送预约提醒
     */
    public function sendAppointmentReminder($appointmentId, $reminderType = '24h') {
        $appointment = $this->getAppointmentDetails($appointmentId);
        if (!$appointment) {
            return false;
        }
        
        $reminderText = $this->getReminderText($reminderType);
        
        // 发送邮件提醒
        $emailSent = $this->sendReminderEmail($appointment, $reminderText);
        
        // 发送短信提醒
        $smsSent = $this->sendReminderSMS($appointment, $reminderText);
        
        // 记录提醒日志
        $this->logNotification($appointmentId, 'reminder_' . $reminderType, [
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent
        ]);
        
        return $emailSent || $smsSent;
    }
    
    /**
     * 发送预约取消通知
     */
    public function sendAppointmentCancellation($appointmentId, $reason = '') {
        $appointment = $this->getAppointmentDetails($appointmentId);
        if (!$appointment) {
            return false;
        }
        
        // 发送邮件通知
        $emailSent = $this->sendCancellationEmail($appointment, $reason);
        
        // 发送短信通知
        $smsSent = $this->sendCancellationSMS($appointment, $reason);
        
        // 记录通知日志
        $this->logNotification($appointmentId, 'cancellation', [
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent,
            'reason' => $reason
        ]);
        
        return $emailSent || $smsSent;
    }
    
    /**
     * 批量发送预约提醒
     */
    public function sendBatchReminders($reminderType = '24h') {
        $timeThreshold = $this->getTimeThreshold($reminderType);
        
        // 获取需要提醒的预约
        $appointments = $this->db->fetchAll("
            SELECT a.id 
            FROM appointments a
            WHERE a.status = 'confirmed'
            AND CONCAT(a.appointment_date, ' ', a.appointment_time) > NOW()
            AND CONCAT(a.appointment_date, ' ', a.appointment_time) <= DATE_ADD(NOW(), INTERVAL ? HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM appointment_notifications an 
                WHERE an.appointment_id = a.id 
                AND an.type = ?
                AND an.sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            )
        ", [$timeThreshold, 'reminder_' . $reminderType]);
        
        $successCount = 0;
        $totalCount = count($appointments);
        
        foreach ($appointments as $appointment) {
            if ($this->sendAppointmentReminder($appointment['id'], $reminderType)) {
                $successCount++;
            }
            
            // 避免发送过快，休息一下
            usleep(100000); // 100毫秒
        }
        
        return [
            'total' => $totalCount,
            'success' => $successCount,
            'failed' => $totalCount - $successCount
        ];
    }
    
    /**
     * 获取预约详细信息
     */
    private function getAppointmentDetails($appointmentId) {
        return $this->db->fetch("
            SELECT a.*, 
                   u.username, u.email, u.phone,
                   d.name as doctor_name, d.title as doctor_title,
                   h.name as hospital_name, h.address as hospital_address,
                   h.phone as hospital_phone
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN hospitals h ON d.hospital_id = h.id
            WHERE a.id = ?
        ", [$appointmentId]);
    }
    
    /**
     * 发送确认邮件
     */
    private function sendConfirmationEmail($appointment) {
        if (!$appointment['email']) {
            return false;
        }
        
        $subject = '预约确认 - ' . SITE_NAME;
        $template = $this->getEmailTemplate('appointment_confirmation', $appointment);
        
        return $this->emailService->send($appointment['email'], $subject, $template);
    }
    
    /**
     * 发送提醒邮件
     */
    private function sendReminderEmail($appointment, $reminderText) {
        if (!$appointment['email']) {
            return false;
        }
        
        $subject = '预约提醒 - ' . SITE_NAME;
        $template = $this->getEmailTemplate('appointment_reminder', $appointment, ['reminder_text' => $reminderText]);
        
        return $this->emailService->send($appointment['email'], $subject, $template);
    }
    
    /**
     * 发送取消邮件
     */
    private function sendCancellationEmail($appointment, $reason) {
        if (!$appointment['email']) {
            return false;
        }
        
        $subject = '预约取消通知 - ' . SITE_NAME;
        $template = $this->getEmailTemplate('appointment_cancellation', $appointment, ['reason' => $reason]);
        
        return $this->emailService->send($appointment['email'], $subject, $template);
    }
    
    /**
     * 发送确认短信
     */
    private function sendConfirmationSMS($appointment) {
        if (!$appointment['patient_phone']) {
            return false;
        }
        
        $message = "【" . SITE_NAME . "】您的预约已确认：" . 
                  $appointment['doctor_name'] . "医生，" .
                  date('m月d日 H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) .
                  "，预约号：" . $appointment['appointment_number'] . "。请提前30分钟到达医院。";
        
        return $this->sendSMS($appointment['patient_phone'], $message);
    }
    
    /**
     * 发送提醒短信
     */
    private function sendReminderSMS($appointment, $reminderText) {
        if (!$appointment['patient_phone']) {
            return false;
        }
        
        $message = "【" . SITE_NAME . "】" . $reminderText . "：" . 
                  $appointment['doctor_name'] . "医生，" .
                  date('m月d日 H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) .
                  "，预约号：" . $appointment['appointment_number'] . "。";
        
        return $this->sendSMS($appointment['patient_phone'], $message);
    }
    
    /**
     * 发送取消短信
     */
    private function sendCancellationSMS($appointment, $reason) {
        if (!$appointment['patient_phone']) {
            return false;
        }
        
        $message = "【" . SITE_NAME . "】您的预约已取消：" . 
                  $appointment['doctor_name'] . "医生，" .
                  date('m月d日 H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) .
                  ($reason ? "，取消原因：" . $reason : "") . "。";
        
        return $this->sendSMS($appointment['patient_phone'], $message);
    }
    
    /**
     * 发送短信（需要集成短信服务商）
     */
    private function sendSMS($phone, $message) {
        // 这里需要集成具体的短信服务商API
        // 例如阿里云、腾讯云等短信服务
        
        // 临时返回true，实际实现时需要调用短信API
        return true;
    }
    
    /**
     * 获取邮件模板
     */
    private function getEmailTemplate($templateName, $appointment, $extraData = []) {
        $data = array_merge($appointment, $extraData);
        
        $templates = [
            'appointment_confirmation' => "
                <h2>预约确认</h2>
                <p>尊敬的 {$data['patient_name']}，</p>
                <p>您的预约已成功确认：</p>
                <ul>
                    <li><strong>预约号：</strong>{$data['appointment_number']}</li>
                    <li><strong>医生：</strong>{$data['doctor_name']} {$data['doctor_title']}</li>
                    <li><strong>医院：</strong>{$data['hospital_name']}</li>
                    <li><strong>时间：</strong>" . date('Y年m月d日 H:i', strtotime($data['appointment_date'] . ' ' . $data['appointment_time'])) . "</li>
                </ul>
                <p>请您按时就诊，并提前30分钟到达医院。</p>
                <p>如需取消预约，请提前24小时联系我们。</p>
            ",
            
            'appointment_reminder' => "
                <h2>预约提醒</h2>
                <p>尊敬的 {$data['patient_name']}，</p>
                <p>{$data['reminder_text']}：</p>
                <ul>
                    <li><strong>预约号：</strong>{$data['appointment_number']}</li>
                    <li><strong>医生：</strong>{$data['doctor_name']} {$data['doctor_title']}</li>
                    <li><strong>医院：</strong>{$data['hospital_name']}</li>
                    <li><strong>时间：</strong>" . date('Y年m月d日 H:i', strtotime($data['appointment_date'] . ' ' . $data['appointment_time'])) . "</li>
                </ul>
                <p>请准时就诊，记得携带身份证和相关检查资料。</p>
            ",
            
            'appointment_cancellation' => "
                <h2>预约取消通知</h2>
                <p>尊敬的 {$data['patient_name']}，</p>
                <p>您的预约已被取消：</p>
                <ul>
                    <li><strong>预约号：</strong>{$data['appointment_number']}</li>
                    <li><strong>医生：</strong>{$data['doctor_name']} {$data['doctor_title']}</li>
                    <li><strong>原定时间：</strong>" . date('Y年m月d日 H:i', strtotime($data['appointment_date'] . ' ' . $data['appointment_time'])) . "</li>
                    " . ($data['reason'] ? "<li><strong>取消原因：</strong>{$data['reason']}</li>" : "") . "
                </ul>
                <p>如需重新预约，请访问我们的网站或联系客服。</p>
            "
        ];
        
        return $templates[$templateName] ?? '';
    }
    
    /**
     * 获取提醒文本
     */
    private function getReminderText($reminderType) {
        $texts = [
            '24h' => '您有预约即将到期（24小时内）',
            '2h' => '您有预约即将开始（2小时内）',
            '30m' => '您的预约即将开始（30分钟内）'
        ];
        
        return $texts[$reminderType] ?? '预约提醒';
    }
    
    /**
     * 获取时间阈值
     */
    private function getTimeThreshold($reminderType) {
        $thresholds = [
            '24h' => 24,
            '2h' => 2,
            '30m' => 0.5
        ];
        
        return $thresholds[$reminderType] ?? 24;
    }
    
    /**
     * 记录通知日志
     */
    private function logNotification($appointmentId, $type, $details) {
        try {
            $this->db->query("
                INSERT INTO appointment_notifications 
                (appointment_id, type, details, sent_at) 
                VALUES (?, ?, ?, NOW())
            ", [$appointmentId, $type, json_encode($details)]);
        } catch (Exception $e) {
            error_log("Failed to log appointment notification: " . $e->getMessage());
        }
    }
}
?>