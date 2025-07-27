<?php
require_once 'includes/init.php';

// 设置页面信息
$pageTitle = SITE_NAME . ' - 专业的健康医疗信息平台';
$pageDescription = '提供医院查询、医生预约、疾病百科、健康资讯、医疗问答等专业服务，让健康更简单';
$pageKeywords = '健康医疗,医院查询,医生预约,疾病百科,健康资讯,医疗问答';
$currentPage = 'index';

// 获取首页数据
$latestNews = getLatestArticles(6);
$featuredDoctors = getFeaturedDoctors(8);
$hotQuestions = getHotQuestions(6);
$categories = getCategoryTree();
$popularSearches = getPopularSearches(8);
$siteStats = getSiteStats();

include 'templates/header.php';
?>

<!-- 首页英雄区域 -->
<section class="hero-section">
    <div class="hero-slider">
        <div class="hero-slide active">
            <div class="hero-background">
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1 class="hero-title animate-fade-up">专业的健康医疗平台</h1>
                        <p class="hero-subtitle animate-fade-up-delay">汇聚全国优质医疗资源，为您提供专业的健康服务</p>
                        <div class="hero-actions animate-fade-up-delay-2">
                            <a href="/hospitals/" class="btn btn-primary btn-lg">
                                <i class="fas fa-hospital"></i>
                                查找医院
                            </a>
                            <a href="/doctors/" class="btn btn-secondary btn-lg">
                                <i class="fas fa-user-md"></i>
                                预约专家
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide">
            <div class="hero-background">
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1 class="hero-title">权威医学知识库</h1>
                        <p class="hero-subtitle">专业医生编写，提供准确可靠的疾病知识和健康指导</p>
                        <div class="hero-actions">
                            <a href="/diseases/" class="btn btn-primary btn-lg">
                                <i class="fas fa-book-medical"></i>
                                疾病百科
                            </a>
                            <a href="/qa/" class="btn btn-secondary btn-lg">
                                <i class="fas fa-question-circle"></i>
                                健康问答
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide">
            <div class="hero-background">
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="container">
                    <div class="hero-text">
                        <h1 class="hero-title">在线预约挂号</h1>
                        <p class="hero-subtitle">便捷的预约系统，让您轻松找到合适的医生和时间</p>
                        <div class="hero-actions">
                            <a href="/appointment/book.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-plus"></i>
                                立即预约
                            </a>
                            <a href="/help/" class="btn btn-secondary btn-lg">
                                <i class="fas fa-info-circle"></i>
                                预约指南
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 轮播控制 -->
    <div class="hero-controls">
        <div class="hero-indicators">
            <button class="indicator active" data-slide="0" aria-label="第一张幻灯片"></button>
            <button class="indicator" data-slide="1" aria-label="第二张幻灯片"></button>
            <button class="indicator" data-slide="2" aria-label="第三张幻灯片"></button>
        </div>
        <div class="hero-nav">
            <button class="hero-prev" aria-label="上一张">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="hero-next" aria-label="下一张">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- 快速导航 -->
<section class="quick-nav-section">
    <div class="container">
        <div class="section-header">
            <h2>快速服务</h2>
            <p>一站式医疗健康服务，让健康触手可及</p>
        </div>
        
        <div class="quick-nav-grid">
            <a href="/hospitals/" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-hospital"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>找医院</h3>
                    <p>全国医院查询</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            
            <a href="/doctors/" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-user-md"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>找医生</h3>
                    <p>专家在线预约</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            
            <a href="/diseases/" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-book-medical"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>查疾病</h3>
                    <p>疾病百科大全</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            
            <a href="/qa/" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-question-circle"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>问医生</h3>
                    <p>在线健康咨询</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            
            <a href="/news/" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-newspaper"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>健康资讯</h3>
                    <p>最新医疗动态</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            
            <a href="/appointment/book.php" class="quick-nav-item animate-on-scroll">
                <div class="nav-icon">
                    <i class="fas fa-calendar-check"></i>
                    <div class="icon-bg"></div>
                </div>
                <div class="nav-content">
                    <h3>预约挂号</h3>
                    <p>在线预约服务</p>
                    <span class="nav-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- 科室分类 -->
<section class="departments-section">
    <div class="container">
        <div class="section-header">
            <h2>专科科室</h2>
            <p>选择您需要的科室，快速找到相关医院和医生</p>
            <a href="/doctors/" class="more-link">查看所有科室 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="departments-grid">
            <?php foreach (array_slice($categories, 0, 12) as $index => $category): ?>
            <a href="/doctors/?category=<?php echo $category['id']; ?>" class="department-item animate-on-scroll" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="dept-icon">
                    <?php if ($category['icon']): ?>
                        <img src="<?php echo h($category['icon']); ?>" alt="<?php echo h($category['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-stethoscope"></i>
                    <?php endif; ?>
                    <div class="icon-effect"></div>
                </div>
                <div class="dept-content">
                    <h3><?php echo h($category['name']); ?></h3>
                    <?php if (!empty($category['children'])): ?>
                        <div class="sub-departments">
                            <?php foreach (array_slice($category['children'], 0, 4) as $subCategory): ?>
                                <span class="sub-dept-tag"><?php echo h($subCategory['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="dept-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 推荐专家 -->
<section class="featured-doctors-section">
    <div class="container">
        <div class="section-header">
            <h2>推荐专家</h2>
            <p>汇聚全国知名专家，为您提供权威的医疗服务</p>
            <a href="/doctors/" class="more-link">查看更多专家 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="doctors-grid">
            <?php foreach ($featuredDoctors as $index => $doctor): ?>
            <div class="doctor-card animate-on-scroll" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="doctor-header">
                    <div class="doctor-avatar">
                        <?php if ($doctor['avatar']): ?>
                            <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>
                        <div class="doctor-status">
                            <span class="status-badge online">在线</span>
                        </div>
                    </div>
                    
                    <div class="doctor-basic">
                        <h3>
                            <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                                <?php echo h($doctor['name']); ?>
                            </a>
                        </h3>
                        <p class="doctor-title"><?php echo h($doctor['title']); ?></p>
                        <p class="doctor-hospital">
                            <i class="fas fa-hospital"></i>
                            <?php echo h($doctor['hospital_name']); ?>
                        </p>
                        <p class="doctor-category">
                            <i class="fas fa-stethoscope"></i>
                            <?php echo h($doctor['category_name']); ?>
                        </p>
                    </div>
                </div>
                
                <div class="doctor-body">
                    <?php if ($doctor['specialties']): ?>
                        <div class="doctor-specialties">
                            <strong>擅长领域：</strong>
                            <p><?php echo h(truncate($doctor['specialties'], 60)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="doctor-stats">
                        <div class="stat-group">
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
                                <span class="rating-score"><?php echo number_format($rating, 1); ?>分</span>
                            </div>
                            <div class="view-count">
                                <i class="fas fa-eye"></i>
                                <?php echo number_format($doctor['view_count']); ?>次查看
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="doctor-footer">
                    <div class="doctor-actions">
                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-info-circle"></i>
                            查看详情
                        </a>
                        <a href="/appointment/book.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i>
                            立即预约
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 健康资讯 -->
<section class="latest-news-section">
    <div class="container">
        <div class="section-header">
            <h2>健康资讯</h2>
            <p>获取最新的健康资讯和医疗行业动态</p>
            <a href="/news/" class="more-link">查看更多资讯 <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="news-container">
            <!-- 头条新闻 -->
            <?php if (!empty($latestNews[0])): ?>
            <div class="featured-news animate-on-scroll">
                <article class="news-card large">
                    <?php if ($latestNews[0]['featured_image']): ?>
                        <div class="news-image">
                            <img src="<?php echo h($latestNews[0]['featured_image']); ?>" alt="<?php echo h($latestNews[0]['title']); ?>">
                            <div class="news-overlay">
                                <span class="news-badge hot">热门</span>
                                <span class="news-category"><?php echo h($latestNews[0]['category_name'] ?: '健康头条'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="news-content">
                        <h3>
                            <a href="/news/detail.php?id=<?php echo $latestNews[0]['id']; ?>">
                                <?php echo h($latestNews[0]['title']); ?>
                            </a>
                        </h3>
                        
                        <?php if ($latestNews[0]['summary']): ?>
                            <p class="news-summary"><?php echo h(truncate($latestNews[0]['summary'], 150)); ?></p>
                        <?php endif; ?>
                        
                        <div class="news-meta">
                            <div class="meta-left">
                                <span class="news-author">
                                    <i class="fas fa-user-edit"></i>
                                    <?php echo h($latestNews[0]['author'] ?: '健康编辑'); ?>
                                </span>
                                <span class="news-date">
                                    <i class="fas fa-clock"></i>
                                    <?php echo formatTime($latestNews[0]['publish_time']); ?>
                                </span>
                            </div>
                            <div class="meta-right">
                                <span class="news-views">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($latestNews[0]['view_count']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <?php endif; ?>
            
            <!-- 其他新闻 -->
            <div class="news-grid">
                <?php foreach (array_slice($latestNews, 1) as $index => $article): ?>
                <article class="news-item animate-on-scroll" style="animation-delay: <?php echo ($index + 1) * 0.1; ?>s">
                    <?php if ($article['featured_image']): ?>
                        <div class="news-image">
                            <img src="<?php echo h($article['featured_image']); ?>" alt="<?php echo h($article['title']); ?>">
                            <div class="news-overlay">
                                <span class="news-category"><?php echo h($article['category_name'] ?: '健康资讯'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="news-content">
                        <h4>
                            <a href="/news/detail.php?id=<?php echo $article['id']; ?>">
                                <?php echo h($article['title']); ?>
                            </a>
                        </h4>
                        
                        <?php if ($article['summary']): ?>
                            <p class="news-summary"><?php echo h(truncate($article['summary'], 80)); ?></p>
                        <?php endif; ?>
                        
                        <div class="news-meta">
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
    </div>
</section>

<!-- 热门问答 -->
<section class="hot-qa-section">
    <div class="container">
        <div class="section-header">
            <div class="header-content">
                <div class="header-text">
                    <h2>
                        <i class="fas fa-question-circle"></i>
                        热门问答
                    </h2>
                    <p>专业医生在线解答，为您答疑解惑</p>
                </div>
                <div class="header-actions">
                    <a href="/qa/ask.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        我要提问
                    </a>
                    <a href="/qa/" class="more-link">
                        查看更多 <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="qa-container">
            <!-- 热门问答标签过滤 -->
            <div class="qa-filter-tabs">
                <button class="tab-btn active" data-category="all" aria-pressed="true" aria-label="显示全部问答">
                    <i class="fas fa-th-large"></i>
                    全部问答
                </button>
                <button class="tab-btn" data-category="answered" aria-pressed="false" aria-label="显示已解答问题">
                    <i class="fas fa-check-circle"></i>
                    已解答
                </button>
                <button class="tab-btn" data-category="pending" aria-pressed="false" aria-label="显示待解答问题">
                    <i class="fas fa-clock"></i>
                    待解答
                </button>
                <button class="tab-btn" data-category="hot" aria-pressed="false" aria-label="显示热门问题">
                    <i class="fas fa-fire"></i>
                    热门问题
                </button>
            </div>
            
            <div class="qa-grid">
                <?php foreach ($hotQuestions as $index => $question): ?>
                <div class="qa-card animate-on-scroll" 
                     style="animation-delay: <?php echo $index * 0.1; ?>s"
                     data-category="<?php echo $question['answer_count'] > 0 ? 'answered' : 'pending'; ?>"
                     data-views="<?php echo $question['view_count']; ?>">
                    
                    <div class="qa-card-header">
                        <div class="question-priority">
                            <?php if ($question['view_count'] > 1000): ?>
                                <span class="priority-badge hot">
                                    <i class="fas fa-fire"></i>
                                    热门
                                </span>
                            <?php elseif ($question['answer_count'] > 0): ?>
                                <span class="priority-badge answered">
                                    <i class="fas fa-check-circle"></i>
                                    已解答
                                </span>
                            <?php else: ?>
                                <span class="priority-badge pending">
                                    <i class="fas fa-clock"></i>
                                    待解答
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-category">
                            <i class="fas fa-tag"></i>
                            <?php echo h($question['category_name'] ?: '综合咨询'); ?>
                        </div>
                    </div>
                    
                    <div class="qa-card-content">
                        <h3 class="question-title">
                            <a href="/qa/detail.php?id=<?php echo $question['id']; ?>">
                                <?php echo h($question['title']); ?>
                            </a>
                        </h3>
                        
                        <p class="question-preview">
                            <?php echo h(truncate(strip_tags($question['content']), 100)); ?>
                        </p>
                        
                        <div class="question-tags">
                            <?php if (!empty($question['tags'])): ?>
                                <?php foreach (explode(',', $question['tags']) as $tag): ?>
                                    <span class="tag">#<?php echo h(trim($tag)); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="qa-card-footer">
                        <div class="question-author">
                            <div class="author-avatar">
                                <?php if ($question['is_anonymous']): ?>
                                    <i class="fas fa-user-secret"></i>
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="author-info">
                                <span class="author-name">
                                    <?php echo $question['is_anonymous'] ? '匿名用户' : h($question['username']); ?>
                                </span>
                                <span class="question-time">
                                    <?php echo formatTime($question['created_at']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="question-stats">
                            <div class="stat-group">
                                <span class="stat-item">
                                    <i class="fas fa-comments"></i>
                                    <?php echo $question['answer_count']; ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?php 
                                    $viewCount = $question['view_count'] ?? 0;
                                    echo $viewCount > 1000 ? number_format($viewCount/1000, 1).'k' : $viewCount; 
                                    ?>
                                </span>
                                <?php if ($question['answer_count'] > 0): ?>
                                <span class="stat-item helpful">
                                    <i class="fas fa-thumbs-up"></i>
                                    有帮助
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <button class="qa-expand-btn" title="查看详情" aria-label="查看问题详情">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 加载更多按钮 -->
            <div class="qa-load-more">
                <button class="btn btn-outline btn-lg load-more-btn">
                    <i class="fas fa-plus"></i>
                    加载更多问答
                </button>
                <div class="loading-indicator" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    正在加载...
                </div>
            </div>
        </div>
        
        <!-- 问答统计信息 -->
        <div class="qa-stats-summary">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($siteStats['questions']); ?></span>
                        <span class="stat-label">总问题数</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($siteStats['answered_questions'] ?? 0); ?></span>
                        <span class="stat-label">已解答</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format($siteStats['active_doctors'] ?? 0); ?></span>
                        <span class="stat-label">在线医生</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number">2</span>
                        <span class="stat-label">小时平均响应</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 快速提问入口 -->
        <div class="qa-quick-ask">
            <div class="quick-ask-content">
                <div class="quick-ask-text">
                    <h3>还有其他健康问题？</h3>
                    <p>专业医生团队为您提供免费在线咨询服务</p>
                </div>
                <div class="quick-ask-actions">
                    <a href="/qa/ask.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i>
                        立即提问
                    </a>
                    <a href="/doctors/" class="btn btn-outline btn-lg">
                        <i class="fas fa-user-md"></i>
                        找医生
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 热门搜索 -->
<section class="popular-search-section">
    <div class="container">
        <div class="section-header">
            <h2>热门搜索</h2>
            <p>大家都在搜索的健康话题</p>
        </div>
        
        <div class="search-container">
            <div class="search-tags">
                <?php foreach ($popularSearches as $index => $search): ?>
                <a href="/search.php?q=<?php echo urlencode($search['keyword']); ?>" 
                   class="search-tag animate-on-scroll" 
                   style="animation-delay: <?php echo $index * 0.05; ?>s">
                    <span class="tag-text"><?php echo h($search['keyword']); ?></span>
                    <span class="search-count"><?php echo number_format($search['search_count']); ?></span>
                    <?php if ($index < 3): ?>
                        <span class="hot-indicator">
                            <i class="fas fa-fire"></i>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="search-tip">
                <i class="fas fa-lightbulb"></i>
                <span>提示：点击标签可快速搜索相关内容</span>
            </div>
        </div>
    </div>
</section>

<!-- 数据统计 -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card animate-on-scroll">
                <div class="stat-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" data-count="<?php echo $siteStats['hospitals']; ?>">0</span>
                    <span class="stat-label">合作医院</span>
                </div>
            </div>
            
            <div class="stat-card animate-on-scroll" style="animation-delay: 0.1s">
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" data-count="<?php echo $siteStats['doctors']; ?>">0</span>
                    <span class="stat-label">专业医生</span>
                </div>
            </div>
            
            <div class="stat-card animate-on-scroll" style="animation-delay: 0.2s">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" data-count="<?php echo $siteStats['users']; ?>">0</span>
                    <span class="stat-label">注册用户</span>
                </div>
            </div>
            
            <div class="stat-card animate-on-scroll" style="animation-delay: 0.3s">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" data-count="<?php echo $siteStats['questions']; ?>">0</span>
                    <span class="stat-label">问答数量</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>