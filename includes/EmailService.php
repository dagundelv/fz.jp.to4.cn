<?php
class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->host = EMAIL_HOST ?? 'localhost';
        $this->port = EMAIL_PORT ?? 25;
        $this->username = EMAIL_USERNAME ?? '';
        $this->password = EMAIL_PASSWORD ?? '';
        $this->from_email = EMAIL_FROM ?? 'noreply@fz.jp.to4.cn';
        $this->from_name = EMAIL_FROM_NAME ?? SITE_NAME;
    }
    
    /**
     * 发送邮件
     */
    public function sendMail($to, $subject, $body, $isHTML = true) {
        try {
            // 如果配置了SMTP，使用SMTP发送
            if ($this->host !== 'localhost' && $this->username) {
                return $this->sendSMTP($to, $subject, $body, $isHTML);
            } else {
                // 否则使用PHP内置mail函数
                return $this->sendPHPMail($to, $subject, $body, $isHTML);
            }
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 使用SMTP发送邮件
     */
    private function sendSMTP($to, $subject, $body, $isHTML) {
        // 简化的SMTP实现，实际项目中建议使用PHPMailer
        $headers = $this->buildHeaders($isHTML);
        
        // 这里简化处理，实际应该实现完整的SMTP协议
        return mail($to, $subject, $body, $headers);
    }
    
    /**
     * 使用PHP mail函数发送邮件
     */
    private function sendPHPMail($to, $subject, $body, $isHTML) {
        $headers = $this->buildHeaders($isHTML);
        return mail($to, $subject, $body, $headers);
    }
    
    /**
     * 构建邮件头
     */
    private function buildHeaders($isHTML) {
        $headers = [];
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: " . SITE_NAME . " Mailer";
        $headers[] = "MIME-Version: 1.0";
        
        if ($isHTML) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * 发送用户注册确认邮件
     */
    public function sendRegistrationConfirmation($user) {
        $subject = "欢迎注册" . SITE_NAME;
        $body = $this->getRegistrationTemplate($user);
        
        return $this->sendMail($user['email'], $subject, $body, true);
    }
    
    /**
     * 发送密码重置邮件
     */
    public function sendPasswordReset($user, $resetToken) {
        $subject = SITE_NAME . " - 密码重置";
        $body = $this->getPasswordResetTemplate($user, $resetToken);
        
        return $this->sendMail($user['email'], $subject, $body, true);
    }
    
    /**
     * 发送预约确认邮件
     */
    public function sendAppointmentConfirmation($appointment) {
        $subject = "预约确认 - " . SITE_NAME;
        $body = $this->getAppointmentTemplate($appointment);
        
        return $this->sendMail($appointment['user_email'], $subject, $body, true);
    }
    
    /**
     * 发送问答回复通知邮件
     */
    public function sendAnswerNotification($question, $answer) {
        $subject = "您的问题有新回复 - " . SITE_NAME;
        $body = $this->getAnswerNotificationTemplate($question, $answer);
        
        return $this->sendMail($question['user_email'], $subject, $body, true);
    }
    
    /**
     * 获取注册确认邮件模板
     */
    private function getRegistrationTemplate($user) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>欢迎注册</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>欢迎加入" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>尊敬的 " . h($user['username']) . "，</h2>
                    <p>感谢您注册" . SITE_NAME . "！</p>
                    <p>您现在可以：</p>
                    <ul>
                        <li>浏览最新的健康资讯</li>
                        <li>咨询专业医生</li>
                        <li>预约医院挂号</li>
                        <li>收藏感兴趣的内容</li>
                    </ul>
                    <p style='text-align: center;'>
                        <a href='" . SITE_URL . "' class='btn'>立即体验</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 获取密码重置邮件模板
     */
    private function getPasswordResetTemplate($user, $resetToken) {
        $resetUrl = SITE_URL . "/user/reset-password.php?token=" . $resetToken;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>密码重置</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>密码重置</h1>
                </div>
                <div class='content'>
                    <h2>尊敬的 " . h($user['username']) . "，</h2>
                    <p>我们收到了您的密码重置请求。</p>
                    <div class='warning'>
                        <p><strong>请注意：</strong>如果这不是您的操作，请忽略此邮件。</p>
                    </div>
                    <p>点击下面的按钮重置您的密码：</p>
                    <p style='text-align: center;'>
                        <a href='" . $resetUrl . "' class='btn'>重置密码</a>
                    </p>
                    <p>或者复制以下链接到浏览器：</p>
                    <p><a href='" . $resetUrl . "'>" . $resetUrl . "</a></p>
                    <p><small>此链接将在24小时后失效。</small></p>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 获取预约确认邮件模板
     */
    private function getAppointmentTemplate($appointment) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>预约确认</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
                .appointment-info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .label { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>预约确认</h1>
                </div>
                <div class='content'>
                    <h2>预约成功！</h2>
                    <p>您的预约信息如下：</p>
                    <div class='appointment-info'>
                        <div class='info-row'>
                            <span class='label'>医生：</span>
                            <span>" . h($appointment['doctor_name']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>医院：</span>
                            <span>" . h($appointment['hospital_name']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>预约时间：</span>
                            <span>" . h($appointment['appointment_time']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>预约号码：</span>
                            <span>" . h($appointment['appointment_number']) . "</span>
                        </div>
                    </div>
                    <p><strong>温馨提示：</strong></p>
                    <ul>
                        <li>请提前30分钟到达医院</li>
                        <li>携带身份证和相关病历资料</li>
                        <li>如需取消或改期，请提前24小时联系</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 获取问答回复通知邮件模板
     */
    private function getAnswerNotificationTemplate($question, $answer) {
        $questionUrl = SITE_URL . "/qa/detail.php?id=" . $question['id'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>问题回复通知</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f39c12; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
                .question-box { background: white; padding: 15px; border-left: 4px solid #f39c12; margin: 15px 0; }
                .answer-box { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #f39c12; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>您的问题有新回复</h1>
                </div>
                <div class='content'>
                    <h2>问题回复通知</h2>
                    
                    <div class='question-box'>
                        <h3>您的问题：</h3>
                        <p>" . h(truncate($question['title'], 100)) . "</p>
                    </div>
                    
                    <div class='answer-box'>
                        <h3>回复内容：</h3>
                        <p>" . h(truncate(strip_tags($answer['content']), 200)) . "</p>
                        <p><small>回复者：" . h($answer['doctor_name'] ?: $answer['username'] ?: '匿名用户') . "</small></p>
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='" . $questionUrl . "' class='btn'>查看完整回复</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 批量发送邮件
     */
    public function sendBulkEmail($recipients, $subject, $body, $isHTML = true) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $results[$email] = $this->sendMail($email, $subject, $body, $isHTML);
        }
        
        return $results;
    }
    
    /**
     * 发送系统通知邮件
     */
    public function sendSystemNotification($to, $title, $message, $type = 'info') {
        $subject = SITE_NAME . " - " . $title;
        $body = $this->getSystemNotificationTemplate($title, $message, $type);
        
        return $this->sendMail($to, $subject, $body, true);
    }
    
    /**
     * 获取系统通知邮件模板
     */
    private function getSystemNotificationTemplate($title, $message, $type) {
        $colors = [
            'info' => '#3498db',
            'success' => '#27ae60',
            'warning' => '#f39c12',
            'error' => '#e74c3c'
        ];
        
        $color = $colors[$type] ?? $colors['info'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>系统通知</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: $color; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
                .message-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$title</h1>
                </div>
                <div class='content'>
                    <div class='message-box'>
                        $message
                    </div>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>