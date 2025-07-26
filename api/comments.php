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

$targetType = $_POST['target_type'] ?? '';
$targetId = intval($_POST['target_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parentId = intval($_POST['parent_id'] ?? 0);
$isAnonymous = intval($_POST['is_anonymous'] ?? 0);
$userId = $_SESSION['user_id'];

// 验证参数
$allowedTypes = ['article', 'question', 'answer', 'doctor', 'hospital'];

if (!in_array($targetType, $allowedTypes)) {
    jsonResponse(['success' => false, 'message' => '无效的评论类型']);
}

if ($targetId <= 0) {
    jsonResponse(['success' => false, 'message' => '无效的目标ID']);
}

if (empty($content)) {
    jsonResponse(['success' => false, 'message' => '评论内容不能为空']);
}

if (mb_strlen($content, 'UTF-8') > 500) {
    jsonResponse(['success' => false, 'message' => '评论内容不能超过500字']);
}

// 检查目标是否存在
$targetExists = false;
switch ($targetType) {
    case 'article':
        $targetExists = $db->exists('articles', 'id = ? AND status = ?', [$targetId, 'published']);
        break;
    case 'question':
        $targetExists = $db->exists('questions', 'id = ? AND status = ?', [$targetId, 'active']);
        break;
    case 'answer':
        $targetExists = $db->exists('answers', 'id = ? AND status = ?', [$targetId, 'active']);
        break;
    case 'doctor':
        $targetExists = $db->exists('doctors', 'id = ? AND status = ?', [$targetId, 'active']);
        break;
    case 'hospital':
        $targetExists = $db->exists('hospitals', 'id = ? AND status = ?', [$targetId, 'active']);
        break;
}

if (!$targetExists) {
    jsonResponse(['success' => false, 'message' => '评论目标不存在']);
}

// 检查父评论是否存在
if ($parentId > 0) {
    $parentExists = $db->exists(
        'comments',
        'id = ? AND target_type = ? AND target_id = ? AND status = ?',
        [$parentId, $targetType, $targetId, 'active']
    );
    
    if (!$parentExists) {
        jsonResponse(['success' => false, 'message' => '回复的评论不存在']);
    }
}

// 防止重复提交（检查最近是否有相同内容的评论）
$recentComment = $db->fetch(
    "SELECT id FROM comments 
     WHERE user_id = ? AND target_type = ? AND target_id = ? 
     AND content = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
    [$userId, $targetType, $targetId, $content]
);

if ($recentComment) {
    jsonResponse(['success' => false, 'message' => '请不要重复提交相同的评论']);
}

try {
    // 插入评论
    $commentId = $db->insert('comments', [
        'user_id' => $userId,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'parent_id' => $parentId,
        'content' => $content,
        'is_anonymous' => $isAnonymous
    ]);
    
    // 获取新插入的评论信息
    $newComment = $db->fetch(
        "SELECT c.*, u.username, u.avatar 
         FROM comments c 
         LEFT JOIN users u ON c.user_id = u.id 
         WHERE c.id = ?",
        [$commentId]
    );
    
    jsonResponse([
        'success' => true,
        'message' => '评论发表成功',
        'data' => [
            'id' => $commentId,
            'content' => $newComment['content'],
            'username' => $newComment['is_anonymous'] ? '匿名用户' : $newComment['username'],
            'avatar' => $newComment['is_anonymous'] ? null : $newComment['avatar'],
            'created_at' => $newComment['created_at'],
            'like_count' => 0,
            'is_anonymous' => $newComment['is_anonymous']
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => '评论发表失败：' . $e->getMessage()], 500);
}
?>