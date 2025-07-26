<?php
// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/init.php';

// 设置页面信息
$pageTitle = SITE_NAME . ' - 专业的健康医疗信息平台';
$pageDescription = '提供医院查询、医生预约、疾病百科、健康资讯、医疗问答等专业服务，让健康更简单';
$pageKeywords = '健康医疗,医院查询,医生预约,疾病百科,健康资讯,医疗问答';

echo "<h2>Testing homepage data loading...</h2>";

// 获取首页数据
echo "<p>Loading articles...</p>";
$latestNews = getLatestArticles(6);
echo "<p>Articles loaded: " . count($latestNews) . "</p>";

echo "<p>Loading doctors...</p>";
$featuredDoctors = getFeaturedDoctors(8);
echo "<p>Doctors loaded: " . count($featuredDoctors) . "</p>";

echo "<p>Loading questions...</p>";
$hotQuestions = getHotQuestions(6);
echo "<p>Questions loaded: " . count($hotQuestions) . "</p>";

echo "<p>Loading categories...</p>";
$categories = getCategoryTree();
echo "<p>Categories loaded: " . count($categories) . "</p>";

echo "<p>Loading popular searches...</p>";
$popularSearches = getPopularSearches(8);
echo "<p>Popular searches loaded: " . count($popularSearches) . "</p>";

echo "<p>Loading site stats...</p>";
$siteStats = getSiteStats();
echo "<p>Site stats loaded: " . json_encode($siteStats) . "</p>";

echo "<h3>Data loading completed. Now testing template...</h3>";

echo "<p>Including header template...</p>";
try {
    include 'templates/header.php';
    echo "<p style='color: green'>Header template loaded successfully</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Header template error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p>Testing if we reach here after header...</p>";
?>

<div style="border: 2px solid red; padding: 20px; margin: 20px 0;">
    <h2>Test Content Section</h2>
    <p>If you can see this, the header template loaded successfully and PHP is still executing.</p>
    
    <h3>Sample Data:</h3>
    <ul>
        <li>Articles: <?php echo count($latestNews); ?></li>
        <li>Doctors: <?php echo count($featuredDoctors); ?></li>
        <li>Questions: <?php echo count($hotQuestions); ?></li>
        <li>Categories: <?php echo count($categories); ?></li>
    </ul>
</div>

<?php
echo "<p>Testing footer template...</p>";
try {
    include 'templates/footer.php';
    echo "<p style='color: green'>Footer template loaded successfully</p>";
} catch (Throwable $e) {
    echo "<p style='color: red'>Footer template error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p>Debug completed.</p>";
?>