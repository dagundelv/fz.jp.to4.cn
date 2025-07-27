<?php
require_once '../includes/init.php';

// 设置JSON响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 检查用户是否登录
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $userId = $currentUser['id'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    if ($requestMethod === 'GET') {
        // 获取用户预约记录
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 10);
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereClause = "a.user_id = ?";
        $params = [$userId];
        
        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            $whereClause .= " AND a.status = ?";
            $params[] = $status;
        }
        
        // 获取预约列表 (用户特定数据不适合长期缓存，使用短时间缓存)
        $cacheKey = 'user_appointments_' . $userId . '_' . md5(serialize($params) . $limit . $offset);
        $appointments = cache_remember($cacheKey, function() use ($db, $whereClause, $params, $limit, $offset) {
            return $db->fetchAll("
                SELECT a.*, 
                       d.name as doctor_name, 
                       d.title as doctor_title, 
                       d.avatar as doctor_avatar,
                       h.name as hospital_name, 
                       h.address as hospital_address, 
                       h.phone as hospital_phone,
                       c.name as category_name
                FROM appointments a
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN hospitals h ON d.hospital_id = h.id
                LEFT JOIN categories c ON d.category_id = c.id
                WHERE $whereClause
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset
            ", $params);
        }, 300); // 短期缓存5分钟
        
        // 获取总数
        $total = $db->fetch("
            SELECT COUNT(*) as count 
            FROM appointments a
            WHERE $whereClause
        ", $params)['count'];
        
        echo json_encode([
            'success' => true,
            'data' => $appointments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]);
        
    } elseif ($requestMethod === 'POST') {
        // 处理预约操作（取消预约等）
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? '';
        $appointmentId = intval($input['appointment_id'] ?? 0);
        
        if (!$appointmentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的预约ID']);
            exit;
        }
        
        // 验证预约是否属于当前用户
        $appointment = $db->fetch("
            SELECT * FROM appointments 
            WHERE id = ? AND user_id = ?
        ", [$appointmentId, $userId]);
        
        if (!$appointment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '预约记录不存在']);
            exit;
        }
        
        switch ($action) {
            case 'cancel':
                if ($appointment['status'] !== 'pending') {
                    echo json_encode(['success' => false, 'message' => '只能取消待确认的预约']);
                    exit;
                }
                
                // 检查是否可以取消（距离预约时间至少24小时）
                $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                $now = time();
                $hoursDiff = ($appointmentDateTime - $now) / 3600;
                
                if ($hoursDiff < 24) {
                    echo json_encode(['success' => false, 'message' => '距离预约时间不足24小时，无法取消']);
                    exit;
                }
                
                $db->query("
                    UPDATE appointments 
                    SET status = 'cancelled', updated_at = NOW() 
                    WHERE id = ?
                ", [$appointmentId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => '预约已取消',
                    'appointment_id' => $appointmentId
                ]);
                break;
                
            case 'reschedule':
                // 重新安排预约时间（这里可以扩展更复杂的逻辑）
                if ($appointment['status'] !== 'pending') {
                    echo json_encode(['success' => false, 'message' => '只能重新安排待确认的预约']);
                    exit;
                }
                
                $newDate = $input['new_date'] ?? '';
                $newTime = $input['new_time'] ?? '';
                
                if (!$newDate || !$newTime) {
                    echo json_encode(['success' => false, 'message' => '请提供新的预约时间']);
                    exit;
                }
                
                // 检查新时间是否可用
                $conflictCheck = $db->fetch("
                    SELECT id FROM appointments 
                    WHERE doctor_id = ? 
                    AND appointment_date = ? 
                    AND appointment_time = ? 
                    AND status IN ('pending', 'confirmed')
                    AND id != ?
                ", [$appointment['doctor_id'], $newDate, $newTime, $appointmentId]);
                
                if ($conflictCheck) {
                    echo json_encode(['success' => false, 'message' => '新的时间段已被预约']);
                    exit;
                }
                
                $db->query("
                    UPDATE appointments 
                    SET appointment_date = ?, appointment_time = ?, updated_at = NOW() 
                    WHERE id = ?
                ", [$newDate, $newTime, $appointmentId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => '预约时间已更新',
                    'appointment_id' => $appointmentId
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '无效的操作']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '操作失败，请稍后重试',
        'error' => $e->getMessage()
    ]);
}
?>