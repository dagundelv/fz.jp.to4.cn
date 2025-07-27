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
        
        // 检查是否为工作日（周一到周五）
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            echo json_encode([
                'success' => true,
                'message' => '周末不出诊',
                'data' => []
            ]);
            exit;
        }
        
        // 获取该日期已经被预约的时间段
        try {
            $bookedSlots = $db->fetchAll("
                SELECT appointment_time 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? 
                AND status IN ('pending', 'confirmed')
            ", [$doctorId, $date]);
        } catch (Exception $e) {
            // 如果查询失败，默认为没有预约
            $bookedSlots = [];
        }
        
        $bookedTimes = array_column($bookedSlots, 'appointment_time');
        
        // 定义标准时间段
        $timeSlots = [
            'morning' => [
                ['time' => '08:00', 'label' => '8:00'],
                ['time' => '08:30', 'label' => '8:30'],
                ['time' => '09:00', 'label' => '9:00'],
                ['time' => '09:30', 'label' => '9:30'],
                ['time' => '10:00', 'label' => '10:00'],
                ['time' => '10:30', 'label' => '10:30'],
                ['time' => '11:00', 'label' => '11:00'],
                ['time' => '11:30', 'label' => '11:30']
            ],
            'afternoon' => [
                ['time' => '14:00', 'label' => '14:00'],
                ['time' => '14:30', 'label' => '14:30'],
                ['time' => '15:00', 'label' => '15:00'],
                ['time' => '15:30', 'label' => '15:30'],
                ['time' => '16:00', 'label' => '16:00'],
                ['time' => '16:30', 'label' => '16:30'],
                ['time' => '17:00', 'label' => '17:00'],
                ['time' => '17:30', 'label' => '17:30']
            ]
        ];
        
        $availableSlots = [];
        
        foreach ($timeSlots as $period => $slots) {
            $availableSlots[] = [
                'name' => $period === 'morning' ? '上午' : '下午',
                'period' => $period,
                'slots' => array_map(function($slot) use ($bookedTimes, $date) {
                    $isAvailable = !in_array($slot['time'], $bookedTimes);
                    
                    // 检查时间是否已过（如果是今天的话）
                    if ($date === date('Y-m-d')) {
                        $slotDateTime = strtotime($date . ' ' . $slot['time']);
                        if ($slotDateTime <= time() + 3600) { // 至少提前1小时预约
                            $isAvailable = false;
                        }
                    }
                    
                    return [
                        'time' => $slot['time'],
                        'display' => $slot['label'],
                        'available' => $isAvailable
                    ];
                }, $slots)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $availableSlots,
            'doctor_id' => $doctorId,
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'total_slots' => array_sum(array_map(function($period) { return count($period['slots']); }, $availableSlots)),
            'available_count' => array_sum(array_map(function($period) { 
                return count(array_filter($period['slots'], function($slot) { return $slot['available']; }));
            }, $availableSlots))
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