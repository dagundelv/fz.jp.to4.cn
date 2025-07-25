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

// 初始化数据库连接
$db = Database::getInstance();

// 设置响应头
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 获取当前用户信息（如果已登录）
$currentUser = getCurrentUser();

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

// 常用的SQL查询函数
function getCategories($parentId = 0) {
    global $db;
    return $db->fetchAll(
        "SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC, id ASC",
        [$parentId]
    );
}

function getCategoryById($id) {
    global $db;
    return $db->fetch("SELECT * FROM categories WHERE id = ?", [$id]);
}

function getCategoryTree() {
    global $db;
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY parent_id ASC, sort_order ASC, id ASC");
    
    $tree = [];
    $lookup = [];
    
    // 第一遍：创建所有节点
    foreach ($categories as $category) {
        $category['children'] = [];
        $lookup[$category['id']] = $category;
    }
    
    // 第二遍：构建树形结构
    foreach ($lookup as $id => $category) {
        if ($category['parent_id'] == 0) {
            $tree[] = &$lookup[$id];
        } else {
            if (isset($lookup[$category['parent_id']])) {
                $lookup[$category['parent_id']]['children'][] = &$lookup[$id];
            }
        }
    }
    
    return $tree;
}

// 获取热门搜索词
function getPopularSearches($limit = 8) {
    return getHotSearchKeywords($limit);
}

// 统计函数
function getSiteStats() {
    global $db;
    
    $stats = [
        'hospitals' => $db->count('hospitals', "status = 'active'"),
        'doctors' => $db->count('doctors', "status = 'active'"),
        'articles' => $db->count('articles', "status = 'published'"),
        'questions' => $db->count('questions', "status = 'active'"),
        'users' => $db->count('users', "status = 'active'")
    ];
    
    return $stats;
}

// 获取最新文章
function getLatestArticles($limit = 10, $categoryId = null) {
    global $db;
    
    $sql = "SELECT a.*, c.name as category_name 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'published'";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND a.category_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY a.publish_time DESC LIMIT ?";
    $params[] = $limit;
    
    return $db->fetchAll($sql, $params);
}

// 获取推荐医生
function getFeaturedDoctors($limit = 10, $categoryId = null) {
    global $db;
    
    $sql = "SELECT d.*, h.name as hospital_name, c.name as category_name 
            FROM doctors d 
            LEFT JOIN hospitals h ON d.hospital_id = h.id 
            LEFT JOIN categories c ON d.category_id = c.id 
            WHERE d.status = 'active' AND h.status = 'active'";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND d.category_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY d.rating DESC, d.view_count DESC LIMIT ?";
    $params[] = $limit;
    
    return $db->fetchAll($sql, $params);
}

// 获取热门问题
function getHotQuestions($limit = 10, $categoryId = null) {
    global $db;
    
    $sql = "SELECT q.*, u.username, c.name as category_name 
            FROM questions q 
            LEFT JOIN users u ON q.user_id = u.id 
            LEFT JOIN categories c ON q.category_id = c.id 
            WHERE q.status = 'active'";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND q.category_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY q.view_count DESC, q.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return $db->fetchAll($sql, $params);
}
?>