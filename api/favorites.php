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
$allowedActions = ['add', 'remove'];
$allowedTypes = ['article', 'doctor', 'hospital', 'disease', 'question'];

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
    
    if ($action === 'add') {
        // 检查是否已收藏
        $exists = $db->exists(
            'favorites',
            'user_id = ? AND target_type = ? AND target_id = ?',
            [$userId, $type, $id]
        );
        
        if ($exists) {
            jsonResponse(['success' => false, 'message' => '已经收藏过了']);
        }
        
        // 添加收藏
        $db->insert('favorites', [
            'user_id' => $userId,
            'target_type' => $type,
            'target_id' => $id
        ]);
        
        jsonResponse(['success' => true, 'message' => '收藏成功']);
        
    } else if ($action === 'remove') {
        // 删除收藏
        $result = $db->delete(
            'favorites',
            'user_id = ? AND target_type = ? AND target_id = ?',
            [$userId, $type, $id]
        );
        
        if ($result->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => '取消收藏成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '未找到收藏记录']);
        }
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()], 500);
}
?>