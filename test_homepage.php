<?php
require_once 'includes/init.php';

// 设置页面信息
$pageTitle = SITE_NAME . ' - 专业的健康医疗信息平台';
$pageDescription = '提供医院查询、医生预约、疾病百科、健康资讯、医疗问答等专业服务，让健康更简单';
$pageKeywords = '健康医疗,医院查询,医生预约,疾病百科,健康资讯,医疗问答';

// 获取首页数据
$latestNews = getLatestArticles(6);
$featuredDoctors = getFeaturedDoctors(8);
$hotQuestions = getHotQuestions(6);
$categories = getCategoryTree();
$popularSearches = getPopularSearches(8);
$siteStats = getSiteStats();

include 'templates/header.php';
?>

<style>
.test-section {
    padding: 40px 0;
    background: #f8f9fa;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
.section-title {
    text-align: center;
    margin-bottom: 30px;
    color: #2c3e50;
}
.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.content-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 30px;
}
.stat-item {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #3498db;
}
.stat-label {
    margin-top: 10px;
    color: #7f8c8d;
}
</style>

<section class="test-section">
    <div class="container">
        <h1 class="section-title">网站首页内容测试</h1>
        
        <div class="content-grid">
            <!-- 最新文章 -->
            <div class="content-card">
                <h3>最新文章 (<?php echo count($latestNews); ?>)</h3>
                <?php if ($latestNews): ?>
                    <ul>
                        <?php foreach (array_slice($latestNews, 0, 3) as $article): ?>
                        <li>
                            <strong><?php echo h($article['title']); ?></strong><br>
                            <small>分类: <?php echo h($article['category_name'] ?? '无分类'); ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无文章</p>
                <?php endif; ?>
            </div>
            
            <!-- 推荐医生 -->
            <div class="content-card">
                <h3>推荐医生 (<?php echo count($featuredDoctors); ?>)</h3>
                <?php if ($featuredDoctors): ?>
                    <ul>
                        <?php foreach (array_slice($featuredDoctors, 0, 3) as $doctor): ?>
                        <li>
                            <strong><?php echo h($doctor['name']); ?></strong> - <?php echo h($doctor['title']); ?><br>
                            <small><?php echo h($doctor['hospital_name']); ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无医生</p>
                <?php endif; ?>
            </div>
            
            <!-- 热门问题 -->
            <div class="content-card">
                <h3>热门问题 (<?php echo count($hotQuestions); ?>)</h3>
                <?php if ($hotQuestions): ?>
                    <ul>
                        <?php foreach (array_slice($hotQuestions, 0, 3) as $question): ?>
                        <li>
                            <strong><?php echo h($question['title']); ?></strong><br>
                            <small>提问人: <?php echo h($question['username']); ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无问题</p>
                <?php endif; ?>
            </div>
            
            <!-- 科室分类 -->
            <div class="content-card">
                <h3>科室分类 (<?php echo count($categories); ?>)</h3>
                <?php if ($categories): ?>
                    <ul>
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li><?php echo h($category['name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无分类</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 网站统计 -->
        <h3 class="section-title">网站统计</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $siteStats['hospitals']; ?></div>
                <div class="stat-label">合作医院</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $siteStats['doctors']; ?></div>
                <div class="stat-label">专业医生</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $siteStats['articles']; ?></div>
                <div class="stat-label">健康文章</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $siteStats['questions']; ?></div>
                <div class="stat-label">问答数量</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $siteStats['users']; ?></div>
                <div class="stat-label">注册用户</div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>