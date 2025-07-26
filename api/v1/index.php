<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/init.php';

// API版本信息
$apiInfo = [
    'name' => '健康医疗网 API',
    'version' => '1.0.0',
    'description' => '提供医疗健康信息的RESTful API接口',
    'endpoints' => [
        'GET /api/v1/news' => '获取健康资讯列表',
        'GET /api/v1/news/{id}' => '获取指定资讯详情',
        'GET /api/v1/doctors' => '获取医生列表',
        'GET /api/v1/doctors/{id}' => '获取医生详情',
        'GET /api/v1/hospitals' => '获取医院列表',
        'GET /api/v1/hospitals/{id}' => '获取医院详情',
        'GET /api/v1/diseases' => '获取疾病列表',
        'GET /api/v1/diseases/{id}' => '获取疾病详情',
        'GET /api/v1/questions' => '获取问答列表',
        'GET /api/v1/questions/{id}' => '获取问答详情',
        'POST /api/v1/auth/login' => '用户登录',
        'POST /api/v1/auth/register' => '用户注册',
        'POST /api/v1/auth/logout' => '用户登出',
        'GET /api/v1/user/profile' => '获取用户信息',
        'PUT /api/v1/user/profile' => '更新用户信息',
        'GET /api/v1/user/favorites' => '获取用户收藏',
        'POST /api/v1/user/favorites' => '添加收藏',
        'DELETE /api/v1/user/favorites/{id}' => '删除收藏',
        'GET /api/v1/search' => '全站搜索'
    ],
    'authentication' => 'Bearer Token',
    'rate_limit' => '1000 requests per hour',
    'documentation' => SITE_URL . '/api/v1/docs'
];

// 返回API信息
echo json_encode([
    'success' => true,
    'data' => $apiInfo,
    'timestamp' => time(),
    'server_time' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>