<?php
// 初始化文件 - 所有页面都应该包含此文件

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含配置文件
require_once __DIR__ . '/../config/config.php';

// 包含数据库类
require_once __DIR__ . '/Database.php';

// 包含通用函数
require_once __DIR__ . '/functions.php';

// 包含邮件服务
require_once __DIR__ . '/EmailService.php';

// 初始化数据库连接
$db = Database::getInstance();

// 设置响应头
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 获取当前用户信息（如果已登录）
try {
    $currentUser = getCurrentUser();
} catch (Exception $e) {
    // 如果获取用户信息失败，设为null
    $currentUser = null;
}

// 页面标题和描述的默认值
$pageTitle = SITE_NAME;
$pageDescription = SITE_DESCRIPTION;
$pageKeywords = SITE_KEYWORDS;

// 获取当前页面信息
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$requestUri = $_SERVER['REQUEST_URI'];
$queryString = $_SERVER['QUERY_STRING'];

// 解析URL参数
$urlParams = [];
if (!empty($queryString)) {
    parse_str($queryString, $urlParams);
}

// 所有函数现在都在functions.php中定义，避免重复定义
?>