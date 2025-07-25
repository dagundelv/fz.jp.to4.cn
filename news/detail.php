<?php
require_once '../includes/init.php';

// 获取文章ID
$articleId = intval($_GET['id'] ?? 0);

if (!$articleId) {
    redirect('/news/');
}

// 获取文章详情
$article = $db->fetch("
    SELECT a.*, c.name as category_name, c.id as category_id
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ? AND a.status = 'published'
", [$articleId]);

if (!$article) {
    redirect('/404.html');
}

// 更新浏览量
$db->update(
    'articles',
    ['view_count' => $article['view_count'] + 1],
    'id = ?',
    [$articleId]
);

// 设置页面信息
$pageTitle = h($article['title']) . " - " . SITE_NAME;
$pageDescription = $article['summary'] ? h(truncate($article['summary'], 160)) : h(truncate(strip_tags($article['content']), 160));
$pageKeywords = $article['tags'] ? h($article['tags']) : "健康资讯,医疗新闻";

// 获取相关文章
$relatedArticles = [];
if ($article['category_id']) {
    $relatedArticles = $db->fetchAll("
        SELECT a.*, c.name as category_name
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published' 
        AND a.category_id = ? 
        AND a.id != ?
        ORDER BY a.publish_time DESC
        LIMIT 6
    ", [$article['category_id'], $articleId]);
}

// 获取最新文章
$latestArticles = $db->fetchAll("
    SELECT a.*, c.name as category_name
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' 
    AND a.id != ?
    ORDER BY a.publish_time DESC
    LIMIT 8
", [$articleId]);

// 获取文章评论
$comments = $db->fetchAll("
    SELECT c.*, u.username, u.avatar
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.target_type = 'article' 
    AND c.target_id = ? 
    AND c.status = 'active'
    ORDER BY c.created_at DESC
    LIMIT 20
", [$articleId]);

// 添加页面特定的CSS
$pageCSS = ['/assets/css/news.css'];

include '../templates/header.php';
?>

<div class="article-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '健康头条', 'url' => '/news/']
            ];
            if ($article['category_name']) {
                $breadcrumbs[] = ['title' => $article['category_name'], 'url' => '/news/?category=' . $article['category_id']];
            }
            $breadcrumbs[] = ['title' => truncate($article['title'], 50)];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="article-layout">
            <!-- 主要内容区 -->
            <main class="article-main">
                <article class="article-content">
                    <!-- 文章头部 -->
                    <header class="article-header">
                        <?php if ($article['category_name']): ?>
                            <div class="article-category">
                                <a href="/news/?category=<?php echo $article['category_id']; ?>">
                                    <i class="fas fa-tag"></i>
                                    <?php echo h($article['category_name']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <h1 class="article-title"><?php echo h($article['title']); ?></h1>
                        
                        <?php if ($article['subtitle']): ?>
                            <h2 class="article-subtitle"><?php echo h($article['subtitle']); ?></h2>
                        <?php endif; ?>
                        
                        <div class="article-meta">
                            <div class="meta-left">
                                <?php if ($article['author']): ?>
                                    <span class="author">
                                        <i class="fas fa-user"></i>
                                        <?php echo h($article['author']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="publish-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('Y年m月d日 H:i', strtotime($article['publish_time'])); ?>
                                </span>
                                
                                <?php if ($article['source']): ?>
                                    <span class="source">
                                        <i class="fas fa-link"></i>
                                        来源：<?php echo h($article['source']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="meta-right">
                                <span class="views">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($article['view_count']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- 特色图片 -->
                        <?php if ($article['featured_image']): ?>
                            <div class="article-featured-image">
                                <img src="<?php echo h($article['featured_image']); ?>" 
                                     alt="<?php echo h($article['title']); ?>">
                            </div>
                        <?php endif; ?>
                    </header>
                    
                    <!-- 文章摘要 -->
                    <?php if ($article['summary']): ?>
                        <div class="article-summary">
                            <div class="summary-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="summary-content">
                                <?php echo nl2br(h($article['summary'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 文章正文 -->
                    <div class="article-body">
                        <?php echo stripTags($article['content']); ?>
                    </div>
                    
                    <!-- 文章标签 -->
                    <?php if ($article['tags']): ?>
                        <div class="article-tags">
                            <div class="tags-label">
                                <i class="fas fa-tags"></i>
                                相关标签：
                            </div>
                            <div class="tags-list">
                                <?php
                                $tags = explode(',', $article['tags']);
                                foreach ($tags as $tag):
                                    $tag = trim($tag);
                                    if ($tag):
                                ?>
                                    <a href="/search.php?q=<?php echo urlencode($tag); ?>" class="tag">
                                        <?php echo h($tag); ?>
                                    </a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 文章操作 -->
                    <div class="article-actions">
                        <div class="action-buttons">
                            <button class="action-btn like-btn" data-type="article" data-id="<?php echo $article['id']; ?>">
                                <i class="fas fa-heart"></i>
                                <span class="like-count"><?php echo number_format($article['like_count']); ?></span>
                                点赞
                            </button>
                            
                            <button class="action-btn favorite-btn" data-type="article" data-id="<?php echo $article['id']; ?>">
                                <i class="fas fa-bookmark"></i>
                                收藏
                            </button>
                            
                            <button class="action-btn share-btn" data-url="<?php echo SITE_URL . '/news/detail.php?id=' . $article['id']; ?>" data-title="<?php echo h($article['title']); ?>">
                                <i class="fas fa-share"></i>
                                分享
                            </button>
                        </div>
                        
                        <div class="share-buttons" id="shareButtons" style="display: none;">
                            <a href="#" class="share-wechat" data-share="wechat">
                                <i class="fab fa-weixin"></i>
                                微信
                            </a>
                            <a href="#" class="share-weibo" data-share="weibo">
                                <i class="fab fa-weibo"></i>
                                微博
                            </a>
                            <a href="#" class="share-qq" data-share="qq">
                                <i class="fab fa-qq"></i>
                                QQ
                            </a>
                            <a href="#" class="share-copy" data-share="copy">
                                <i class="fas fa-copy"></i>
                                复制链接
                            </a>
                        </div>
                    </div>
                </article>
                
                <!-- 相关文章 -->
                <?php if ($relatedArticles): ?>
                    <section class="related-articles">
                        <h3 class="section-title">
                            <i class="fas fa-newspaper"></i>
                            相关资讯
                        </h3>
                        
                        <div class="related-grid">
                            <?php foreach ($relatedArticles as $related): ?>
                                <div class="related-item">
                                    <?php if ($related['featured_image']): ?>
                                        <div class="related-image">
                                            <a href="/news/detail.php?id=<?php echo $related['id']; ?>">
                                                <img src="<?php echo h($related['featured_image']); ?>" 
                                                     alt="<?php echo h($related['title']); ?>"
                                                     loading="lazy">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="related-content">
                                        <h4>
                                            <a href="/news/detail.php?id=<?php echo $related['id']; ?>">
                                                <?php echo h(truncate($related['title'], 80)); ?>
                                            </a>
                                        </h4>
                                        
                                        <div class="related-meta">
                                            <span class="time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo formatTime($related['publish_time']); ?>
                                            </span>
                                            <span class="views">
                                                <i class="fas fa-eye"></i>
                                                <?php echo formatNumber($related['view_count']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- 评论区 -->
                <section class="comments-section">
                    <h3 class="section-title">
                        <i class="fas fa-comments"></i>
                        评论 (<?php echo count($comments); ?>)
                    </h3>
                    
                    <!-- 评论表单 -->
                    <?php if (isLoggedIn()): ?>
                        <form class="comment-form" data-ajax action="/api/comments.php" method="POST">
                            <input type="hidden" name="target_type" value="article">
                            <input type="hidden" name="target_id" value="<?php echo $article['id']; ?>">
                            
                            <div class="comment-input">
                                <textarea name="content" placeholder="写下你的评论..." rows="4" required></textarea>
                            </div>
                            
                            <div class="comment-actions">
                                <label class="anonymous-option">
                                    <input type="checkbox" name="is_anonymous" value="1">
                                    匿名评论
                                </label>
                                
                                <button type="submit" class="btn btn-primary">
                                    发表评论
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p>
                                <i class="fas fa-sign-in-alt"></i>
                                <a href="/user/login.php">登录</a> 后才能发表评论
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 评论列表 -->
                    <?php if ($comments): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-avatar">
                                        <?php if ($comment['avatar'] && !$comment['is_anonymous']): ?>
                                            <img src="<?php echo h($comment['avatar']); ?>" 
                                                 alt="<?php echo h($comment['username']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <span class="username">
                                                <?php echo $comment['is_anonymous'] ? '匿名用户' : h($comment['username']); ?>
                                            </span>
                                            <span class="comment-time">
                                                <?php echo formatTime($comment['created_at']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="comment-text">
                                            <?php echo nl2br(h($comment['content'])); ?>
                                        </div>
                                        
                                        <div class="comment-actions">
                                            <button class="comment-like like-btn" data-type="comment" data-id="<?php echo $comment['id']; ?>">
                                                <i class="fas fa-thumbs-up"></i>
                                                <span class="like-count"><?php echo $comment['like_count']; ?></span>
                                            </button>
                                            
                                            <?php if (isLoggedIn()): ?>
                                                <button class="comment-reply" data-id="<?php echo $comment['id']; ?>">
                                                    <i class="fas fa-reply"></i>
                                                    回复
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-comments">
                            <div class="no-comments-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <p>暂无评论，快来发表第一个评论吧！</p>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="article-sidebar">
                <!-- 最新资讯 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-newspaper"></i>
                        最新资讯
                    </h3>
                    <div class="latest-articles">
                        <?php foreach (array_slice($latestArticles, 0, 5) as $latest): ?>
                            <div class="latest-item">
                                <?php if ($latest['featured_image']): ?>
                                    <div class="latest-image">
                                        <a href="/news/detail.php?id=<?php echo $latest['id']; ?>">
                                            <img src="<?php echo h($latest['featured_image']); ?>" 
                                                 alt="<?php echo h($latest['title']); ?>"
                                                 loading="lazy">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="latest-content">
                                    <h4>
                                        <a href="/news/detail.php?id=<?php echo $latest['id']; ?>">
                                            <?php echo h(truncate($latest['title'], 60)); ?>
                                        </a>
                                    </h4>
                                    
                                    <div class="latest-meta">
                                        <span class="time">
                                            <?php echo formatTime($latest['publish_time']); ?>
                                        </span>
                                        <span class="views">
                                            <?php echo formatNumber($latest['view_count']); ?>次浏览
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="/news/" class="view-more">
                            查看更多资讯 <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- 健康提醒 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        健康提醒
                    </h3>
                    <div class="health-warning">
                        <div class="warning-content">
                            <p>
                                <strong>医疗免责声明：</strong>
                                本文内容仅供参考，不能替代专业医生的诊断和治疗建议。如有健康问题，请及时就医。
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- 推荐专家 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-user-md"></i>
                        推荐专家
                    </h3>
                    <div class="recommended-doctors">
                        <?php
                        $categoryId = $article['category_id'];
                        $recommendedDoctors = $categoryId ? 
                            getFeaturedDoctors(3, $categoryId) : 
                            getFeaturedDoctors(3);
                        
                        foreach ($recommendedDoctors as $doctor):
                        ?>
                            <div class="doctor-card-mini">
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
                                    
                                    <div class="doctor-actions">
                                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-secondary">
                                            查看详情
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="/doctors/" class="view-all-link">
                            查看更多专家 <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
// 分享功能
$(document).ready(function() {
    $('.share-btn').on('click', function() {
        $('#shareButtons').toggle();
    });
    
    $('[data-share]').on('click', function(e) {
        e.preventDefault();
        const type = $(this).data('share');
        const url = $('.share-btn').data('url');
        const title = $('.share-btn').data('title');
        
        switch(type) {
            case 'wechat':
                // 微信分享需要生成二维码
                showMessage('请使用微信扫描功能分享', 'info');
                break;
            case 'weibo':
                window.open(`https://service.weibo.com/share/share.php?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`);
                break;
            case 'qq':
                window.open(`https://connect.qq.com/widget/shareqq/index.html?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`);
                break;
            case 'copy':
                navigator.clipboard.writeText(url).then(() => {
                    showMessage('链接已复制到剪贴板', 'success');
                });
                break;
        }
        
        $('#shareButtons').hide();
    });
    
    // 点击其他地方关闭分享面板
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.share-btn, #shareButtons').length) {
            $('#shareButtons').hide();
        }
    });
});
</script>

<?php include '../templates/footer.php'; ?>