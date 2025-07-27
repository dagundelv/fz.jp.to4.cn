<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

// 检查用户是否登录
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

// 处理POST请求（删除历史记录）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'delete') {
            $item_type = $_POST['item_type'] ?? '';
            $item_id = $_POST['item_id'] ?? '';
            
            if (!$item_type || !$item_id) {
                echo json_encode(['success' => false, 'message' => '参数不完整']);
                exit;
            }
            
            $result = $db->execute(
                "DELETE FROM user_browse_history 
                 WHERE user_id = ? AND item_type = ? AND item_id = ?",
                [$user_id, $item_type, $item_id]
            );
            
            echo json_encode(['success' => true, 'message' => '删除成功']);
            
        } elseif ($action === 'clear') {
            $type = $_POST['type'] ?? '';
            
            if ($type) {
                $result = $db->execute(
                    "DELETE FROM user_browse_history 
                     WHERE user_id = ? AND item_type = ?",
                    [$user_id, $type]
                );
            } else {
                $result = $db->execute(
                    "DELETE FROM user_browse_history WHERE user_id = ?",
                    [$user_id]
                );
            }
            
            echo json_encode(['success' => true, 'message' => '清空成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '无效的操作']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '操作失败: ' . $e->getMessage()]);
    }
    exit;
}

// 处理GET请求（获取历史记录）
try {
    $type = $_GET['type'] ?? '';
    $time_range = $_GET['time_range'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(5, intval($_GET['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;
    
    // 构建基础查询
    $where_conditions = ['ubh.user_id = ?'];
    $params = [$user_id];
    
    // 根据类型筛选
    if ($type && in_array($type, ['doctor', 'hospital', 'article', 'disease', 'question'])) {
        $where_conditions[] = 'ubh.item_type = ?';
        $params[] = $type;
    }
    
    // 根据时间范围筛选
    switch ($time_range) {
        case 'today':
            $where_conditions[] = 'DATE(ubh.last_viewed_at) = CURDATE()';
            break;
        case 'week':
            $where_conditions[] = 'ubh.last_viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case 'month':
            $where_conditions[] = 'ubh.last_viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 获取总数
    $total_query = "SELECT COUNT(*) as total FROM user_browse_history ubh $where_clause";
    $total_result = $db->fetchOne($total_query, $params);
    $total = $total_result['total'] ?? 0;
    
    // 获取历史记录
    $history_query = "
        SELECT 
            ubh.*,
            CASE 
                WHEN ubh.item_type = 'doctor' THEN JSON_OBJECT(
                    'id', d.id,
                    'name', d.name,
                    'title', d.title,
                    'hospital_name', h.name,
                    'category_name', c.name
                )
                WHEN ubh.item_type = 'hospital' THEN JSON_OBJECT(
                    'id', h2.id,
                    'name', h2.name,
                    'level', h2.level,
                    'type', h2.type,
                    'address', h2.address
                )
                WHEN ubh.item_type = 'article' THEN JSON_OBJECT(
                    'id', a.id,
                    'title', a.title,
                    'summary', a.summary,
                    'category_name', c2.name,
                    'created_at', a.created_at
                )
                WHEN ubh.item_type = 'question' THEN JSON_OBJECT(
                    'id', q.id,
                    'title', q.title,
                    'content', q.content,
                    'username', u.username
                )
                WHEN ubh.item_type = 'disease' THEN JSON_OBJECT(
                    'id', ds.id,
                    'name', ds.name,
                    'summary', ds.summary,
                    'category_name', c3.name
                )
            END as item_data
        FROM user_browse_history ubh
        LEFT JOIN doctors d ON ubh.item_type = 'doctor' AND ubh.item_id = d.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        LEFT JOIN categories c ON d.category_id = c.id
        LEFT JOIN hospitals h2 ON ubh.item_type = 'hospital' AND ubh.item_id = h2.id
        LEFT JOIN articles a ON ubh.item_type = 'article' AND ubh.item_id = a.id
        LEFT JOIN categories c2 ON a.category_id = c2.id
        LEFT JOIN questions q ON ubh.item_type = 'question' AND ubh.item_id = q.id
        LEFT JOIN users u ON q.user_id = u.id
        LEFT JOIN diseases ds ON ubh.item_type = 'disease' AND ubh.item_id = ds.id
        LEFT JOIN categories c3 ON ds.category_id = c3.id
        $where_clause
        ORDER BY ubh.last_viewed_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $history_records = $db->fetchAll($history_query, $params);
    
    // 解码JSON数据
    foreach ($history_records as &$record) {
        if ($record['item_data']) {
            $record['item_data'] = json_decode($record['item_data'], true);
        }
    }
    
    // 返回结果
    echo json_encode([
        'success' => true,
        'data' => $history_records,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '获取历史记录失败: ' . $e->getMessage()
    ]);
}
?>