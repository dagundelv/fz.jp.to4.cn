<?php
// 网站配置文件

// 数据库配置
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'fz_jp_to4_cn');
define('DB_USER', 'fz_jp_to4_cn');
define('DB_PASS', 'thWAXAri4AsjznTG');
define('DB_CHARSET', 'utf8mb4');

// 网站基本配置
define('SITE_NAME', '健康医疗网');
define('SITE_URL', 'http://fz.jp.to4.cn');
define('SITE_DESCRIPTION', '专业的健康医疗信息平台，提供医院信息、医生介绍、疾病百科、健康资讯等服务');
define('SITE_KEYWORDS', '健康,医疗,医院,医生,疾病,问答,预约挂号');

// 文件上传配置
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// 分页配置
define('PAGE_SIZE', 20);
define('PAGE_SIZE_SMALL', 10);

// 缓存配置
define('CACHE_TIME', 3600); // 1小时

// 安全配置
define('HASH_ALGORITHM', 'sha256');
define('SESSION_NAME', 'HEALTH_SESSION');

// 错误报告配置
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 开启会话
session_name(SESSION_NAME);
session_start();
?>