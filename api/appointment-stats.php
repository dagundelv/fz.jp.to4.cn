<?php
require_once '../includes/init.php';

// 设置JSON响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 检查管理员权限
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '权限不足']);
    exit;
}

try {
    $type = $_GET['type'] ?? 'summary';
    $startDate = $_GET['start_date'] ?? date('Y-m-01'); // 本月第一天
    $endDate = $_GET['end_date'] ?? date('Y-m-t'); // 本月最后一天
    
    switch ($type) {
        case 'summary':
            // 预约统计摘要
            $stats = cache_remember('appointment_stats_' . md5($startDate . $endDate), function() use ($db, $startDate, $endDate) {
                $total = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ?
                ", [$startDate, $endDate])['count'];
                
                $pending = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ? AND status = 'pending'
                ", [$startDate, $endDate])['count'];
                
                $confirmed = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ? AND status = 'confirmed'
                ", [$startDate, $endDate])['count'];
                
                $completed = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ? AND status = 'completed'
                ", [$startDate, $endDate])['count'];
                
                $cancelled = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ? AND status = 'cancelled'
                ", [$startDate, $endDate])['count'];
                
                return [
                    'total' => $total,
                    'pending' => $pending,
                    'confirmed' => $confirmed,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                    'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0
                ];
            }, 600); // 缓存10分钟
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'daily':
            // 每日预约统计
            $dailyStats = cache_remember('appointment_daily_' . md5($startDate . $endDate), function() use ($db, $startDate, $endDate) {
                return $db->fetchAll("
                    SELECT 
                        appointment_date,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ?
                    GROUP BY appointment_date
                    ORDER BY appointment_date
                ", [$startDate, $endDate]);
            }, 600);
            
            echo json_encode(['success' => true, 'data' => $dailyStats]);
            break;
            
        case 'doctors':
            // 医生预约统计
            $doctorStats = cache_remember('appointment_doctors_' . md5($startDate . $endDate), function() use ($db, $startDate, $endDate) {
                return $db->fetchAll("
                    SELECT 
                        d.id,
                        d.name,
                        d.title,
                        h.name as hospital_name,
                        COUNT(a.id) as total_appointments,
                        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                        SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
                        AVG(CASE WHEN a.status = 'completed' THEN 5 ELSE NULL END) as avg_rating
                    FROM doctors d
                    LEFT JOIN appointments a ON d.id = a.doctor_id 
                        AND a.appointment_date BETWEEN ? AND ?
                    LEFT JOIN hospitals h ON d.hospital_id = h.id
                    WHERE d.status = 'active'
                    GROUP BY d.id
                    HAVING total_appointments > 0
                    ORDER BY total_appointments DESC
                    LIMIT 20
                ", [$startDate, $endDate]);
            }, 600);
            
            echo json_encode(['success' => true, 'data' => $doctorStats]);
            break;
            
        case 'hospitals':
            // 医院预约统计
            $hospitalStats = cache_remember('appointment_hospitals_' . md5($startDate . $endDate), function() use ($db, $startDate, $endDate) {
                return $db->fetchAll("
                    SELECT 
                        h.id,
                        h.name,
                        h.level,
                        h.city,
                        COUNT(a.id) as total_appointments,
                        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                        COUNT(DISTINCT a.doctor_id) as active_doctors
                    FROM hospitals h
                    LEFT JOIN doctors d ON h.id = d.hospital_id
                    LEFT JOIN appointments a ON d.id = a.doctor_id 
                        AND a.appointment_date BETWEEN ? AND ?
                    WHERE h.status = 'active'
                    GROUP BY h.id
                    HAVING total_appointments > 0
                    ORDER BY total_appointments DESC
                ", [$startDate, $endDate]);
            }, 600);
            
            echo json_encode(['success' => true, 'data' => $hospitalStats]);
            break;
            
        case 'time_analysis':
            // 时间段分析
            $timeStats = cache_remember('appointment_time_' . md5($startDate . $endDate), function() use ($db, $startDate, $endDate) {
                return $db->fetchAll("
                    SELECT 
                        HOUR(appointment_time) as hour,
                        COUNT(*) as count,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ?
                    GROUP BY HOUR(appointment_time)
                    ORDER BY hour
                ", [$startDate, $endDate]);
            }, 600);
            
            echo json_encode(['success' => true, 'data' => $timeStats]);
            break;
            
        case 'export':
            // 导出预约数据
            $exportData = $db->fetchAll("
                SELECT 
                    a.appointment_number as '预约号',
                    a.appointment_date as '预约日期',
                    a.appointment_time as '预约时间',
                    u.username as '用户名',
                    a.patient_name as '患者姓名',
                    a.patient_phone as '联系电话',
                    d.name as '医生姓名',
                    d.title as '医生职称',
                    h.name as '医院名称',
                    CASE a.status 
                        WHEN 'pending' THEN '待确认'
                        WHEN 'confirmed' THEN '已确认' 
                        WHEN 'completed' THEN '已完成'
                        WHEN 'cancelled' THEN '已取消'
                        ELSE a.status 
                    END as '状态',
                    a.symptoms as '症状描述',
                    a.created_at as '创建时间'
                FROM appointments a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN hospitals h ON d.hospital_id = h.id
                WHERE a.appointment_date BETWEEN ? AND ?
                ORDER BY a.created_at DESC
            ", [$startDate, $endDate]);
            
            // 设置导出文件头
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="appointments_' . $startDate . '_to_' . $endDate . '.csv"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            // 输出CSV内容
            $output = fopen('php://output', 'w');
            
            // 输出BOM以支持中文
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 输出表头
            if (!empty($exportData)) {
                fputcsv($output, array_keys($exportData[0]));
                
                // 输出数据
                foreach ($exportData as $row) {
                    fputcsv($output, $row);
                }
            }
            
            fclose($output);
            exit;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的统计类型']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '获取统计数据失败',
        'error' => DEBUG_MODE ? $e->getMessage() : '请稍后重试'
    ]);
}

// 检查是否为管理员的辅助函数
function isAdmin() {
    global $currentUser;
    return $currentUser && isset($currentUser['role']) && $currentUser['role'] === 'admin';
}
?>