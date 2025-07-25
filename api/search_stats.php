<?php
require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '请求方法不允许'], 405);
}

$query = trim($_POST['query'] ?? '');
$resultCount = intval($_POST['result_count'] ?? 0);
$category = $_POST['category'] ?? null;

if (empty($query)) {
    jsonResponse(['success' => false, 'message' => '查询关键词不能为空']);
}

try {
    recordSearchKeyword($query, $category, $resultCount);
    jsonResponse(['success' => true, 'message' => '统计记录成功']);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => '统计记录失败'], 500);
}
?>