<?php
require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方法不允许'], 405);
}

$keyword = trim($_POST['keyword'] ?? '');

if (empty($keyword)) {
    jsonResponse(['success' => false, 'message' => '关键词不能为空']);
}

try {
    recordSearchKeyword($keyword);
    jsonResponse(['success' => true, 'message' => '记录成功']);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => '记录失败'], 500);
}
?>