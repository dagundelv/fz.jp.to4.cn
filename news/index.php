<?php
require_once '../includes/init.php';

// 获取分页和筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = "健康头条 - " . SITE_NAME;
$pageDescription = "最新的健康医疗资讯、医学研究进展、行业政策解读和健康科普知识";
$pageKeywords = "健康资讯,医疗新闻,医学进展,健康科普,医疗政策";

// 获取分类信息
$categoryInfo = null;
if ($category) {
    $categoryInfo = getCategoryById($category);
    if ($categoryInfo) {
        $pageTitle = $categoryInfo['name'] . "资讯 - " . SITE_NAME;
        $pageDescription = $categoryInfo['description'] ?: $pageDescription;
    }
}

// 获取新闻列表
$newsFilter = '';
$newsParams = [];

if ($category) {
    $newsFilter = " AND a.category_id = ?";
    $newsParams[] = $category;
}

$offset = ($page - 1) * $pageSize;
$newsParams[] = $pageSize;
$newsParams[] = $offset;

$newsList = $db->fetchAll("
    SELECT a.*, c.name as category_name,
           COUNT(*) OVER() as total_count
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' {$newsFilter}
    ORDER BY a.is_featured DESC, a.publish_time DESC
    LIMIT ? OFFSET ?
", $newsParams);

$totalNews = $newsList ? $newsList[0]['total_count'] : 0;
$totalPages = ceil($totalNews / $pageSize);

// 获取热门新闻
$hotNews = $db->fetchAll("
    SELECT a.*, c.name as category_name
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published'
    ORDER BY a.view_count DESC
    LIMIT 10
");

// 获取分类列表
$newsCategories = $db->fetchAll("
    SELECT c.*, COUNT(a.id) as article_count
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
    WHERE c.parent_id = 0
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.id ASC
");

// 添加页面特定的CSS
$pageCSS = ['/assets/css/news.css'];

include '../templates/header.php';
?>

<div class="news-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [['title' => '健康头条', 'url' => '/news/']];
            if ($categoryInfo) {
                $breadcrumbs[] = ['title' => $categoryInfo['name']];
            }
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="news-layout">
            <!-- 主要内容区 -->
            <main class="news-main">
                <!-- 页面标题 -->
                <div class="page-header">
                    <h1><?php echo $categoryInfo ? h($categoryInfo['name']) . '资讯' : '健康头条'; ?></h1>
                    <p class="page-description">
                        <?php echo $categoryInfo ? h($categoryInfo['description']) : '获取最新的健康医疗资讯和行业动态'; ?>
                    </p>
                </div>
                
                <!-- 分类筛选 -->
                <div class="news-filters">
                    <div class="filter-tabs">
                        <a href="/news/" class="filter-tab <?php echo !$category ? 'active' : ''; ?>">
                            全部资讯
                        </a>
                        <?php foreach ($newsCategories as $cat): ?>
                            <a href="/news/?category=<?php echo $cat['id']; ?>" 
                               class="filter-tab <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo h($cat['name']); ?>
                                <span class="count">(<?php echo $cat['article_count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($newsList): ?>
                    <!-- 新闻列表 -->
                    <div class="news-list">
                        <?php foreach ($newsList as $index => $article): ?>
                            <article class="news-item <?php echo $article['is_featured'] ? 'featured' : ''; ?>">
                                <?php if ($article['featured_image']): ?>
                                    <div class="news-image">
                                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>">
                                            <img src="<?php echo h($article['featured_image']); ?>" 
                                                 alt="<?php echo h($article['title']); ?>"
                                                 loading="lazy">
                                        </a>
                                        <?php if ($article['is_featured']): ?>
                                            <div class="featured-badge">
                                                <i class="fas fa-star"></i>
                                                推荐
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="news-content">
                                    <div class="news-meta">
                                        <?php if ($article['category_name']): ?>
                                            <span class="category">
                                                <i class="fas fa-tag"></i>
                                                <?php echo h($article['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="publish-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo formatTime($article['publish_time']); ?>
                                        </span>
                                        <?php if ($article['author']): ?>
                                            <span class="author">
                                                <i class="fas fa-user"></i>
                                                <?php echo h($article['author']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h2 class="news-title">
                                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>">
                                            <?php echo h($article['title']); ?>
                                        </a>
                                    </h2>
                                    
                                    <?php if ($article['subtitle']): ?>
                                        <h3 class="news-subtitle">
                                            <?php echo h($article['subtitle']); ?>
                                        </h3>
                                    <?php endif; ?>
                                    
                                    <?php if ($article['summary']): ?>
                                        <p class="news-summary">
                                            <?php echo h(truncate($article['summary'], 200)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="news-stats">
                                        <span class="views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($article['view_count']); ?>
                                        </span>
                                        <span class="likes">
                                            <i class="fas fa-heart"></i>
                                            <?php echo number_format($article['like_count']); ?>
                                        </span>
                                        <span class="shares">
                                            <i class="fas fa-share"></i>
                                            <?php echo number_format($article['share_count']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="news-actions">
                                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>" 
                                           class="btn btn-primary">
                                            阅读全文
                                        </a>
                                        <button class="btn btn-secondary favorite-btn" 
                                                data-type="article" 
                                                data-id="<?php echo $article['id']; ?>">
                                            <i class="fas fa-bookmark"></i>
                                            收藏
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-section">
                            <?php
                            $paginationParams = $_GET;
                            unset($paginationParams['page']);
                            echo generatePagination($page, $totalPages, '/news/', $paginationParams);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 无内容提示 -->
                    <div class="no-content">
                        <div class="no-content-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h3>暂无相关资讯</h3>
                        <p><?php echo $categoryInfo ? '该分类下' : ''; ?>暂时没有发布相关资讯内容</p>
                        <?php if ($categoryInfo): ?>
                            <a href="/news/" class="btn btn-primary">查看全部资讯</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="news-sidebar">
                <!-- 热门资讯 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-fire"></i>
                        热门资讯
                    </h3>
                    <div class="hot-news-list">
                        <?php foreach ($hotNews as $index => $hot): ?>
                            <div class="hot-news-item">
                                <div class="hot-rank"><?php echo $index + 1; ?></div>
                                <div class="hot-content">
                                    <h4>
                                        <a href="/news/detail.php?id=<?php echo $hot['id']; ?>">
                                            <?php echo h(truncate($hot['title'], 60)); ?>
                                        </a>
                                    </h4>
                                    <div class="hot-meta">
                                        <span class="views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo formatNumber($hot['view_count']); ?>
                                        </span>
                                        <span class="time">
                                            <?php echo formatTime($hot['publish_time']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 分类导航 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-list"></i>
                        资讯分类
                    </h3>
                    <div class="category-list">
                        <a href="/news/" class="category-item <?php echo !$category ? 'active' : ''; ?>">
                            <span class="category-name">全部资讯</span>
                            <span class="category-count"><?php echo number_format($totalNews); ?></span>
                        </a>
                        <?php foreach ($newsCategories as $cat): ?>
                            <a href="/news/?category=<?php echo $cat['id']; ?>" 
                               class="category-item <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                <span class="category-name"><?php echo h($cat['name']); ?></span>
                                <span class="category-count"><?php echo $cat['article_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 健康小贴士 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-lightbulb"></i>
                        健康小贴士
                    </h3>
                    <div class="health-tips">
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="tip-content">
                                <h4>每日健康</h4>
                                <p>每天至少步行30分钟，有助于心血管健康</p>
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="tip-content">
                                <h4>饮食建议</h4>
                                <p>多吃蔬菜水果，减少加工食品摄入</p>
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="tip-content">
                                <h4>睡眠质量</h4>
                                <p>保持7-8小时充足睡眠，规律作息</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 推荐医生 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-user-md"></i>
                        推荐专家
                    </h3>
                    <div class="recommended-doctors">
                        <?php
                        $sidebarDoctors = getFeaturedDoctors(3);
                        foreach ($sidebarDoctors as $doctor):
                        ?>
                            <div class="doctor-mini-card">
                                <div class="doctor-avatar">
                                    <?php if ($doctor['avatar']): ?>
                                        <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="doctor-info">
                                    <h4>
                                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                                            <?php echo h($doctor['name']); ?>
                                        </a>
                                    </h4>
                                    <p class="doctor-title"><?php echo h($doctor['title']); ?></p>
                                    <p class="doctor-hospital"><?php echo h($doctor['hospital_name']); ?></p>
                                    <div class="rating">
                                        <?php
                                        $rating = floatval($doctor['rating']);
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating):
                                        ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; endfor; ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="/doctors/" class="view-all-doctors">
                            查看更多专家 <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>


<?php include '../templates/footer.php'; ?>