<?php
require_once '../includes/init.php';

// 设置JSON响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    if ($requestMethod === 'GET') {
        // 获取指定医生在指定日期的可用时间段
        $doctorId = intval($_GET['doctor_id'] ?? 0);
        $date = $_GET['date'] ?? '';
        
        if (!$doctorId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请提供医生ID']);
            exit;
        }
        
        if (!$date || !strtotime($date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请提供有效的日期']);
            exit;
        }
        
        // 检查日期是否为未来日期
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            echo json_encode([
                'success' => true,
                'message' => '过期日期',
                'data' => []
            ]);
            exit;
        }
        
        $dayOfWeek = date('w', strtotime($date));
        
        // 获取医生在该星期几的工作时间安排
        $cacheKey = 'doctor_slots_' . $doctorId . '_' . $date;
        $availableSlots = cache_remember($cacheKey, function() use ($db, $doctorId, $dayOfWeek, $date) {
            // 获取医生的工作时间表
            $schedules = $db->fetchAll("
                SELECT s.*, slot.slot_time
                FROM doctor_schedules s
                LEFT JOIN appointment_slots slot ON s.id = slot.schedule_id
                WHERE s.doctor_id = ? AND s.day_of_week = ? AND s.is_active = 1
                ORDER BY s.session_type, slot.slot_time
            ", [$doctorId, $dayOfWeek]);
            
            if (empty($schedules)) {
                return [];
            }
            
            // 获取该日期已经被预约的时间段
            $bookedSlots = $db->fetchAll("
                SELECT appointment_time 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? 
                AND status IN ('pending', 'confirmed')
            ", [$doctorId, $date]);
            
            $bookedTimes = array_column($bookedSlots, 'appointment_time');
            
            // 组织可用时间段
            $result = [];
            $currentSession = '';
            
            foreach ($schedules as $schedule) {
                if (!$schedule['slot_time']) continue;
                
                $session = $schedule['session_type'];
                if ($session !== $currentSession) {
                    $currentSession = $session;
                    $sessionName = [
                        'morning' => '上午',
                        'afternoon' => '下午', 
                        'evening' => '晚上'
                    ][$session] ?? $session;
                    
                    if (!isset($result[$session])) {
                        $result[$session] = [
                            'name' => $sessionName,
                            'slots' => []
                        ];
                    }
                }
                
                $slotTime = $schedule['slot_time'];
                $isAvailable = !in_array($slotTime, $bookedTimes);
                
                // 检查时间是否已过（如果是今天的话）
                if ($date === date('Y-m-d')) {
                    $slotDateTime = strtotime($date . ' ' . $slotTime);
                    if ($slotDateTime <= time() + 3600) { // 至少提前1小时预约
                        $isAvailable = false;
                    }
                }
                
                $result[$session]['slots'][] = [
                    'time' => $slotTime,
                    'display' => date('H:i', strtotime($slotTime)),
                    'available' => $isAvailable
                ];
            }
            
            return array_values($result);
        }, 900); // 缓存15分钟
        
        echo json_encode([
            'success' => true,
            'data' => $availableSlots,
            'doctor_id' => $doctorId,
            'date' => $date,
            'day_of_week' => $dayOfWeek
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器错误',
        'error' => DEBUG_MODE ? $e->getMessage() : '请稍后重试'
    ]);
}
?>