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

// 支持GET和POST请求
$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    $userId = $currentUser['id'];
    
    if ($requestMethod === 'GET') {
        // 获取用户收藏列表
        $type = $_GET['type'] ?? 'all';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = "user_id = ?";
        $params = [$userId];
        
        if ($type !== 'all' && in_array($type, ['doctor', 'hospital', 'article', 'disease', 'question'])) {
            $whereClause .= " AND item_type = ?";
            $params[] = $type;
        }
        
        // 获取收藏列表
        $favorites = $db->fetchAll("
            SELECT * FROM user_favorites 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ", $params);
        
        // 获取总数
        $total = $db->fetch("
            SELECT COUNT(*) as count FROM user_favorites WHERE $whereClause
        ", $params)['count'];
        
        // 获取每种类型的详细信息
        $enrichedFavorites = [];
        foreach ($favorites as $favorite) {
            $item = $favorite;
            
            switch ($favorite['item_type']) {
                case 'doctor':
                    $doctor = $db->fetch("
                        SELECT d.*, h.name as hospital_name, c.name as category_name
                        FROM doctors d
                        LEFT JOIN hospitals h ON d.hospital_id = h.id
                        LEFT JOIN categories c ON d.category_id = c.id
                        WHERE d.id = ?
                    ", [$favorite['item_id']]);
                    if ($doctor) {
                        $item['item_data'] = $doctor;
                    }
                    break;
                    
                case 'hospital':
                    $hospital = $db->fetch("SELECT * FROM hospitals WHERE id = ?", [$favorite['item_id']]);
                    if ($hospital) {
                        $item['item_data'] = $hospital;
                    }
                    break;
                    
                case 'article':
                    $article = $db->fetch("
                        SELECT a.*, c.name as category_name
                        FROM articles a
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE a.id = ?
                    ", [$favorite['item_id']]);
                    if ($article) {
                        $item['item_data'] = $article;
                    }
                    break;
                    
                case 'disease':
                    $disease = $db->fetch("
                        SELECT d.*, c.name as category_name
                        FROM diseases d
                        LEFT JOIN categories c ON d.category_id = c.id
                        WHERE d.id = ?
                    ", [$favorite['item_id']]);
                    if ($disease) {
                        $item['item_data'] = $disease;
                    }
                    break;
                    
                case 'question':
                    $question = $db->fetch("
                        SELECT q.*, c.name as category_name, u.username
                        FROM qa_questions q
                        LEFT JOIN categories c ON q.category_id = c.id
                        LEFT JOIN users u ON q.user_id = u.id
                        WHERE q.id = ?
                    ", [$favorite['item_id']]);
                    if ($question) {
                        $item['item_data'] = $question;
                    }
                    break;
            }
            
            $enrichedFavorites[] = $item;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $enrichedFavorites,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]);
        
    } elseif ($requestMethod === 'POST') {
        // 处理收藏操作
        
        // 获取JSON输入或POST数据
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? '';
        $itemType = $input['item_type'] ?? '';
        $itemId = intval($input['item_id'] ?? 0);
        
        // 验证参数
        if (!in_array($action, ['add', 'remove', 'toggle', 'check'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的操作类型']);
            exit;
        }
        
        if (!in_array($itemType, ['doctor', 'hospital', 'article', 'disease', 'question'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的收藏类型']);
            exit;
        }
        
        if ($itemId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的项目ID']);
            exit;
        }
        
        // 检查收藏是否已存在
        $exists = $db->fetch("
            SELECT id FROM user_favorites 
            WHERE user_id = ? AND item_type = ? AND item_id = ?
        ", [$userId, $itemType, $itemId]);
        
        $response = ['success' => true];
        
        switch ($action) {
            case 'check':
                $response['favorited'] = $exists ? true : false;
                break;
                
            case 'add':
                if (!$exists) {
                    $db->query("
                        INSERT INTO user_favorites (user_id, item_type, item_id, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ", [$userId, $itemType, $itemId]);
                    $response['favorited'] = true;
                    $response['message'] = '收藏成功';
                } else {
                    $response['favorited'] = true;
                    $response['message'] = '已收藏';
                }
                break;
                
            case 'remove':
                if ($exists) {
                    $db->query("
                        DELETE FROM user_favorites 
                        WHERE user_id = ? AND item_type = ? AND item_id = ?
                    ", [$userId, $itemType, $itemId]);
                    $response['favorited'] = false;
                    $response['message'] = '取消收藏成功';
                } else {
                    $response['favorited'] = false;
                    $response['message'] = '未收藏';
                }
                break;
                
            case 'toggle':
                if ($exists) {
                    $db->query("
                        DELETE FROM user_favorites 
                        WHERE user_id = ? AND item_type = ? AND item_id = ?
                    ", [$userId, $itemType, $itemId]);
                    $response['favorited'] = false;
                    $response['message'] = '取消收藏成功';
                } else {
                    $db->query("
                        INSERT INTO user_favorites (user_id, item_type, item_id, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ", [$userId, $itemType, $itemId]);
                    $response['favorited'] = true;
                    $response['message'] = '收藏成功';
                }
                break;
        }
        
        // 获取用户收藏总数
        $totalFavorites = $db->fetch("
            SELECT COUNT(*) as count FROM user_favorites WHERE user_id = ?
        ", [$userId])['count'];
        
        $response['total_favorites'] = $totalFavorites;
        
        echo json_encode($response);
        
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