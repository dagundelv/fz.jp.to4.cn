<?php
require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方法不允许'], 405);
}

// 检查用户是否登录
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '请先登录'], 401);
}

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);
$userId = $_SESSION['user_id'];

// 验证参数
$allowedActions = ['like', 'unlike'];
$allowedTypes = ['article', 'question', 'answer', 'comment'];

if (!in_array($action, $allowedActions)) {
    jsonResponse(['success' => false, 'message' => '无效的操作']);
}

if (!in_array($type, $allowedTypes)) {
    jsonResponse(['success' => false, 'message' => '无效的类型']);
}

if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => '无效的ID']);
}

try {
    $db = Database::getInstance();
    
    // 根据类型确定表名和字段
    $tableMap = [
        'article' => 'articles',
        'question' => 'questions',
        'answer' => 'answers',
        'comment' => 'comments'
    ];
    
    $table = $tableMap[$type];
    
    // 开始事务
    $db->beginTransaction();
    
    // 检查是否已点赞（这里简化处理，在实际项目中可能需要单独的点赞表）
    $currentItem = $db->fetch("SELECT like_count FROM {$table} WHERE id = ?", [$id]);
    
    if (!$currentItem) {
        $db->rollback();
        jsonResponse(['success' => false, 'message' => '内容不存在']);
    }
    
    $currentLikeCount = intval($currentItem['like_count']);
    
    if ($action === 'like') {
        // 增加点赞数
        $newLikeCount = $currentLikeCount + 1;
        $db->update(
            $table,
            ['like_count' => $newLikeCount],
            'id = ?',
            [$id]
        );
        
        $message = '点赞成功';
        
    } else if ($action === 'unlike') {
        // 减少点赞数
        $newLikeCount = max(0, $currentLikeCount - 1);
        $db->update(
            $table,
            ['like_count' => $newLikeCount],
            'id = ?',
            [$id]
        );
        
        $message = '取消点赞成功';
    }
    
    // 提交事务
    $db->commit();
    
    jsonResponse([
        'success' => true, 
        'message' => $message,
        'like_count' => $newLikeCount
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()], 500);
}
?>