<?php
// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Error Debugging</h2>";

// 测试基本PHP
echo "<p>1. Basic PHP: Working</p>";

// 测试配置文件
echo "<p>2. Testing config...</p>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p style='color: green'>Config loaded successfully</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Config error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试Database类
echo "<p>3. Testing Database class...</p>";
try {
    require_once __DIR__ . '/includes/Database.php';
    echo "<p style='color: green'>Database class loaded</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Database class error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试functions.php
echo "<p>4. Testing functions.php...</p>";
try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p style='color: green'>Functions loaded</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Functions error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试数据库连接
echo "<p>5. Testing database connection...</p>";
try {
    $db = Database::getInstance();
    echo "<p style='color: green'>Database connection successful</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Database connection error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试EmailService
echo "<p>6. Testing EmailService...</p>";
try {
    require_once __DIR__ . '/includes/EmailService.php';
    echo "<p style='color: green'>EmailService loaded</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>EmailService error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试init.php
echo "<p>7. Testing init.php...</p>";
try {
    require_once __DIR__ . '/includes/init.php';
    echo "<p style='color: green'>Init loaded successfully</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Init error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试getCurrentUser函数
echo "<p>8. Testing getCurrentUser function...</p>";
try {
    $currentUser = getCurrentUser();
    echo "<p style='color: green'>getCurrentUser function works</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>getCurrentUser error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 测试首页数据函数
echo "<p>9. Testing homepage data functions...</p>";
try {
    $latestNews = getLatestArticles(6);
    echo "<p style='color: green'>getLatestArticles: " . count($latestNews) . " articles</p>";
    
    $featuredDoctors = getFeaturedDoctors(8);
    echo "<p style='color: green'>getFeaturedDoctors: " . count($featuredDoctors) . " doctors</p>";
    
    $hotQuestions = getHotQuestions(6);
    echo "<p style='color: green'>getHotQuestions: " . count($hotQuestions) . " questions</p>";
    
    $categories = getCategoryTree();
    echo "<p style='color: green'>getCategoryTree: " . count($categories) . " categories</p>";
    
    $popularSearches = getPopularSearches(8);
    echo "<p style='color: green'>getPopularSearches: " . count($popularSearches) . " searches</p>";
    
    $siteStats = getSiteStats();
    echo "<p style='color: green'>getSiteStats: " . json_encode($siteStats) . "</p>";
    
} catch (Throwable $e) {
    echo "<p style='color: red'>Homepage data error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3 style='color: green'>All tests passed!</h3>";
?>