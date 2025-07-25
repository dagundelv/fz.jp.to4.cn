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

<!-- 首页轮播横幅 -->
<section class="hero-section">
    <div class="hero-slider">
        <div class="hero-slide active">
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1>专业的健康医疗平台</h1>
                        <p>汇聚全国优质医疗资源，为您提供专业的健康服务</p>
                        <div class="hero-actions">
                            <a href="/hospitals/" class="btn btn-primary">查找医院</a>
                            <a href="/doctors/" class="btn btn-secondary">预约专家</a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="/assets/images/hero-1.jpg" alt="专业医疗服务" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide">
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1>权威医学知识库</h1>
                        <p>专业医生编写，提供准确可靠的疾病知识和健康指导</p>
                        <div class="hero-actions">
                            <a href="/diseases/" class="btn btn-primary">疾病百科</a>
                            <a href="/qa/" class="btn btn-secondary">健康问答</a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="/assets/images/hero-2.jpg" alt="疾病百科" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 轮播指示器 -->
    <div class="hero-indicators">
        <button class="indicator active" data-slide="0"></button>
        <button class="indicator" data-slide="1"></button>
    </div>
</section>

<!-- 快速导航 -->
<section class="quick-nav-section">
    <div class="container">
        <div class="quick-nav-grid">
            <a href="/hospitals/" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3>找医院</h3>
                <p>全国医院查询</p>
            </a>
            
            <a href="/doctors/" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>找医生</h3>
                <p>专家在线预约</p>
            </a>
            
            <a href="/diseases/" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-book-medical"></i>
                </div>
                <h3>查疾病</h3>
                <p>疾病百科大全</p>
            </a>
            
            <a href="/qa/" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>问医生</h3>
                <p>在线健康咨询</p>
            </a>
            
            <a href="/news/" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h3>健康资讯</h3>
                <p>最新医疗动态</p>
            </a>
            
            <a href="/user/appointment.php" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>预约挂号</h3>
                <p>在线预约服务</p>
            </a>
        </div>
    </div>
</section>

<!-- 科室分类 -->
<section class="departments-section">
    <div class="container">
        <div class="section-header">
            <h2>按科室查找</h2>
            <p>选择您需要的科室，快速找到相关医院和医生</p>
        </div>
        
        <div class="departments-grid">
            <?php foreach (array_slice($categories, 0, 12) as $category): ?>
            <a href="/doctors/?category=<?php echo $category['id']; ?>" class="department-item">
                <div class="dept-icon">
                    <?php if ($category['icon']): ?>
                        <img src="<?php echo h($category['icon']); ?>" alt="<?php echo h($category['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-stethoscope"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo h($category['name']); ?></h3>
                <?php if (!empty($category['children'])): ?>
                    <div class="sub-departments">
                        <?php foreach (array_slice($category['children'], 0, 4) as $subCategory): ?>
                            <span><?php echo h($subCategory['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 推荐医生 -->
<section class="featured-doctors-section">
    <div class="container">
        <div class="section-header">
            <h2>推荐专家</h2>
            <p>汇聚全国知名专家，为您提供权威的医疗服务</p>
            <a href="/doctors/" class="more-link">查看更多 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="doctors-grid">
            <?php foreach ($featuredDoctors as $doctor): ?>
            <div class="doctor-card">
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
                    <h3>
                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                            <?php echo h($doctor['name']); ?>
                        </a>
                    </h3>
                    <p class="doctor-title"><?php echo h($doctor['title']); ?></p>
                    <p class="doctor-hospital"><?php echo h($doctor['hospital_name']); ?></p>
                    <p class="doctor-category"><?php echo h($doctor['category_name']); ?></p>
                    
                    <?php if ($doctor['specialties']): ?>
                        <p class="doctor-specialties">
                            擅长：<?php echo h(truncate($doctor['specialties'], 50)); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="doctor-meta">
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
                            <span class="rating-score"><?php echo number_format($rating, 1); ?></span>
                        </div>
                        <span class="view-count"><?php echo number_format($doctor['view_count']); ?>次查看</span>
                    </div>
                    
                    <div class="doctor-actions">
                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                        <a href="/user/appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-secondary btn-sm">立即预约</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 健康头条 -->
<section class="latest-news-section">
    <div class="container">
        <div class="section-header">
            <h2>健康头条</h2>
            <p>获取最新的健康资讯和医疗行业动态</p>
            <a href="/news/" class="more-link">查看更多 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="news-grid">
            <?php foreach ($latestNews as $index => $article): ?>
            <article class="news-item <?php echo $index === 0 ? 'featured' : ''; ?>">
                <?php if ($article['featured_image']): ?>
                    <div class="news-image">
                        <img src="<?php echo h($article['featured_image']); ?>" alt="<?php echo h($article['title']); ?>">
                        <div class="news-overlay">
                            <span class="news-category"><?php echo h($article['category_name'] ?: '健康资讯'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="news-content">
                    <h3>
                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>">
                            <?php echo h($article['title']); ?>
                        </a>
                    </h3>
                    
                    <?php if ($article['summary']): ?>
                        <p class="news-summary"><?php echo h(truncate($article['summary'], 120)); ?></p>
                    <?php endif; ?>
                    
                    <div class="news-meta">
                        <span class="news-author">
                            <i class="fas fa-user"></i>
                            <?php echo h($article['author'] ?: '健康编辑'); ?>
                        </span>
                        <span class="news-date">
                            <i class="fas fa-clock"></i>
                            <?php echo formatTime($article['publish_time']); ?>
                        </span>
                        <span class="news-views">
                            <i class="fas fa-eye"></i>
                            <?php echo number_format($article['view_count']); ?>
                        </span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 热门问答 -->
<section class="hot-qa-section">
    <div class="container">
        <div class="section-header">
            <h2>热门问答</h2>
            <p>专业医生在线解答，为您答疑解惑</p>
            <a href="/qa/" class="more-link">查看更多 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="qa-list">
            <?php foreach ($hotQuestions as $question): ?>
            <div class="qa-item">
                <div class="question-content">
                    <h3>
                        <a href="/qa/detail.php?id=<?php echo $question['id']; ?>">
                            <?php echo h($question['title']); ?>
                        </a>
                    </h3>
                    <p class="question-preview"><?php echo h(truncate(strip_tags($question['content']), 100)); ?></p>
                    
                    <div class="question-meta">
                        <span class="category">
                            <i class="fas fa-tag"></i>
                            <?php echo h($question['category_name'] ?: '综合'); ?>
                        </span>
                        <span class="asker">
                            <i class="fas fa-user"></i>
                            <?php echo $question['is_anonymous'] ? '匿名用户' : h($question['username']); ?>
                        </span>
                        <span class="time">
                            <i class="fas fa-clock"></i>
                            <?php echo formatTime($question['created_at']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="question-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $question['answer_count']; ?></span>
                        <span class="stat-label">回答</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($question['view_count']); ?></span>
                        <span class="stat-label">浏览</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="qa-actions">
            <a href="/qa/ask.php" class="btn btn-primary">我要提问</a>
            <a href="/qa/hot.php" class="btn btn-secondary">热门问题</a>
        </div>
    </div>
</section>

<!-- 热门搜索 -->
<section class="popular-search-section">
    <div class="container">
        <div class="section-header">
            <h2>热门搜索</h2>
        </div>
        
        <div class="search-tags">
            <?php foreach ($popularSearches as $search): ?>
            <a href="/search.php?q=<?php echo urlencode($search['keyword']); ?>" class="search-tag">
                <?php echo h($search['keyword']); ?>
                <span class="search-count"><?php echo number_format($search['search_count']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>