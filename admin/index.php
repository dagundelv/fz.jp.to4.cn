<?php
require_once '../includes/init.php';

// 检查管理员权限
if (!isLoggedIn() || !isAdmin()) {
    redirect('/user/login.php?redirect=' . urlencode('/admin/'));
}

$currentUser = getCurrentUser();
$pageTitle = "管理员后台 - " . SITE_NAME;

// 获取统计数据
$stats = [
    'users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'doctors' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")['count'],
    'hospitals' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE status = 'active'")['count'],
    'articles' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
    'questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions")['count'],
    'answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers")['count'],
    'appointments' => $db->fetch("SELECT COUNT(*) as count FROM appointments")['count'],
    'diseases' => $db->fetch("SELECT COUNT(*) as count FROM diseases WHERE status = 'active'")['count']
];

// 获取最近活动
$recentUsers = $db->fetchAll("
    SELECT id, username, email, created_at, status 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

$recentArticles = $db->fetchAll("
    SELECT id, title, author, publish_time, view_count 
    FROM articles 
    WHERE status = 'published' 
    ORDER BY publish_time DESC 
    LIMIT 5
");

$recentQuestions = $db->fetchAll("
    SELECT q.id, q.title, q.created_at, u.username 
    FROM qa_questions q 
    LEFT JOIN users u ON q.user_id = u.id 
    ORDER BY q.created_at DESC 
    LIMIT 5
");

// 系统信息
$systemInfo = [
    'php_version' => phpversion(),
    'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'disk_usage' => disk_free_space('.') ? round((disk_total_space('.') - disk_free_space('.')) / disk_total_space('.') * 100, 2) : 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'memory_limit' => ini_get('memory_limit')
];

include 'templates/header.php';
?>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1>管理员控制台</h1>
        <p>欢迎回来，<?php echo h($currentUser['username']); ?>！</p>
    </div>
    
    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['users']); ?></h3>
                <p>注册用户</p>
            </div>
            <a href="/admin/users.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['doctors']); ?></h3>
                <p>入驻医生</p>
            </div>
            <a href="/admin/doctors.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-hospital"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['hospitals']); ?></h3>
                <p>合作医院</p>
            </div>
            <a href="/admin/hospitals.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['articles']); ?></h3>
                <p>发布文章</p>
            </div>
            <a href="/admin/articles.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['questions']); ?></h3>
                <p>问题数量</p>
            </div>
            <a href="/admin/questions.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['answers']); ?></h3>
                <p>回答数量</p>
            </div>
            <a href="/admin/answers.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['appointments']); ?></h3>
                <p>预约记录</p>
            </div>
            <a href="/admin/appointments.php" class="stat-link">查看详情</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-virus"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['diseases']); ?></h3>
                <p>疾病百科</p>
            </div>
            <a href="/admin/diseases.php" class="stat-link">查看详情</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="dashboard-left">
            <!-- 最近用户 -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>最新注册用户</h3>
                    <a href="/admin/users.php" class="widget-link">查看全部</a>
                </div>
                <div class="widget-content">
                    <?php if ($recentUsers): ?>
                        <div class="user-list">
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="user-item">
                                    <div class="user-info">
                                        <h4><?php echo h($user['username']); ?></h4>
                                        <p><?php echo h($user['email']); ?></p>
                                        <span class="user-date"><?php echo formatTime($user['created_at']); ?></span>
                                    </div>
                                    <div class="user-status">
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo $user['status'] == 'active' ? '正常' : '禁用'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">暂无用户数据</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 最近文章 -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>最新发布文章</h3>
                    <a href="/admin/articles.php" class="widget-link">查看全部</a>
                </div>
                <div class="widget-content">
                    <?php if ($recentArticles): ?>
                        <div class="article-list">
                            <?php foreach ($recentArticles as $article): ?>
                                <div class="article-item">
                                    <div class="article-info">
                                        <h4>
                                            <a href="/news/detail.php?id=<?php echo $article['id']; ?>" target="_blank">
                                                <?php echo h(truncate($article['title'], 50)); ?>
                                            </a>
                                        </h4>
                                        <p>作者：<?php echo h($article['author']); ?></p>
                                        <span class="article-date"><?php echo formatTime($article['publish_time']); ?></span>
                                    </div>
                                    <div class="article-stats">
                                        <span class="view-count">
                                            <i class="fas fa-eye"></i>
                                            <?php echo formatNumber($article['view_count']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">暂无文章数据</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="dashboard-right">
            <!-- 最近问题 -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>最新提问</h3>
                    <a href="/admin/questions.php" class="widget-link">查看全部</a>
                </div>
                <div class="widget-content">
                    <?php if ($recentQuestions): ?>
                        <div class="question-list">
                            <?php foreach ($recentQuestions as $question): ?>
                                <div class="question-item">
                                    <div class="question-info">
                                        <h4>
                                            <a href="/qa/detail.php?id=<?php echo $question['id']; ?>" target="_blank">
                                                <?php echo h(truncate($question['title'], 60)); ?>
                                            </a>
                                        </h4>
                                        <p>提问者：<?php echo h($question['username'] ?? '匿名用户'); ?></p>
                                        <span class="question-date"><?php echo formatTime($question['created_at']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">暂无问题数据</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 系统信息 -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>系统信息</h3>
                </div>
                <div class="widget-content">
                    <div class="system-info">
                        <div class="info-item">
                            <span class="label">PHP版本：</span>
                            <span class="value"><?php echo $systemInfo['php_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">MySQL版本：</span>
                            <span class="value"><?php echo $systemInfo['mysql_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Web服务器：</span>
                            <span class="value"><?php echo $systemInfo['server_software']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">磁盘使用率：</span>
                            <span class="value"><?php echo $systemInfo['disk_usage']; ?>%</span>
                        </div>
                        <div class="info-item">
                            <span class="label">上传限制：</span>
                            <span class="value"><?php echo $systemInfo['upload_max_filesize']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">内存限制：</span>
                            <span class="value"><?php echo $systemInfo['memory_limit']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>