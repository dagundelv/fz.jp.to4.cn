<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/user/profile.php'));
    exit;
}

// 获取用户详细信息
$user = $db->fetch("
    SELECT u.*, COUNT(q.id) as question_count, COUNT(a.id) as answer_count
    FROM users u
    LEFT JOIN qa_questions q ON u.id = q.user_id
    LEFT JOIN qa_answers a ON u.id = a.user_id
    WHERE u.id = ?
    GROUP BY u.id
", [$currentUser['id']]);

// 设置页面信息
$pageTitle = "个人中心 - " . SITE_NAME;
$pageDescription = "管理您的个人信息和健康数据";
$pageKeywords = "个人中心,用户资料,健康档案";
$currentPage = 'profile';

// 处理资料更新
$updateError = '';
$updateSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $realName = trim($_POST['real_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // 验证输入
    if ($phone && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
        $updateError = '请输入有效的手机号码';
    } elseif ($birthday && strtotime($birthday) > time()) {
        $updateError = '出生日期不能是未来时间';
    } else {
        try {
            $db->query("
                UPDATE users 
                SET real_name = ?, gender = ?, birthday = ?, phone = ?, city = ?, bio = ?, updated_at = NOW()
                WHERE id = ?
            ", [$realName, $gender, $birthday, $phone, $city, $bio, $currentUser['id']]);
            
            $updateSuccess = true;
            // 重新获取用户信息
            $user = $db->fetch("
                SELECT u.*, COUNT(q.id) as question_count, COUNT(a.id) as answer_count
                FROM users u
                LEFT JOIN qa_questions q ON u.id = q.user_id
                LEFT JOIN qa_answers a ON u.id = a.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ", [$currentUser['id']]);
            
        } catch (Exception $e) {
            $updateError = '更新失败，请稍后重试';
        }
    }
}

// 获取用户活动统计
$userStats = [
    'questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions WHERE user_id = ?", [$currentUser['id']])['count'],
    'answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers WHERE user_id = ?", [$currentUser['id']])['count'],
    'favorites' => $db->fetch("SELECT COUNT(*) as count FROM user_favorites WHERE user_id = ?", [$currentUser['id']])['count'] ?? 0,
    'appointments' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?", [$currentUser['id']])['count'] ?? 0
];

// 获取最近活动
$recentQuestions = $db->fetchAll("
    SELECT q.*, c.name as category_name
    FROM qa_questions q
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
    LIMIT 5
", [$currentUser['id']]);

$recentAnswers = $db->fetchAll("
    SELECT a.*, q.title as question_title, q.id as question_id
    FROM qa_answers a
    LEFT JOIN qa_questions q ON a.question_id = q.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
", [$currentUser['id']]);

// 添加页面特定的CSS和JS
$pageCSS = ['/assets/css/user.css'];
$pageJS = ['/assets/js/favorites.js'];

include '../templates/header.php';
?>

<div class="profile-page">
    <div class="container">
        <div class="profile-layout">
            <!-- 用户侧边栏 -->
            <aside class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo h($user['avatar']); ?>" alt="<?php echo h($user['username']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <button class="avatar-upload-btn" title="更换头像">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    
                    <div class="user-info">
                        <h3><?php echo h($user['real_name'] ?: $user['username']); ?></h3>
                        <p class="username">@<?php echo h($user['username']); ?></p>
                        <?php if ($user['bio']): ?>
                            <p class="bio"><?php echo h($user['bio']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userStats['questions']; ?></span>
                            <span class="stat-label">提问</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userStats['answers']; ?></span>
                            <span class="stat-label">回答</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userStats['favorites']; ?></span>
                            <span class="stat-label">收藏</span>
                        </div>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    <a href="#dashboard" class="nav-item active" data-tab="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>个人概览</span>
                    </a>
                    <a href="#profile-edit" class="nav-item" data-tab="profile-edit">
                        <i class="fas fa-user-edit"></i>
                        <span>编辑资料</span>
                    </a>
                    <a href="#my-questions" class="nav-item" data-tab="my-questions">
                        <i class="fas fa-question-circle"></i>
                        <span>我的提问</span>
                    </a>
                    <a href="#my-answers" class="nav-item" data-tab="my-answers">
                        <i class="fas fa-comment"></i>
                        <span>我的回答</span>
                    </a>
                    <a href="#favorites" class="nav-item" data-tab="favorites">
                        <i class="fas fa-heart"></i>
                        <span>我的收藏</span>
                    </a>
                    <a href="#appointments" class="nav-item" data-tab="appointments">
                        <i class="fas fa-calendar-check"></i>
                        <span>预约记录</span>
                    </a>
                    <a href="#settings" class="nav-item" data-tab="settings">
                        <i class="fas fa-cog"></i>
                        <span>账户设置</span>
                    </a>
                </nav>
            </aside>
            
            <!-- 主要内容区 -->
            <main class="profile-main">
                <!-- 个人概览 -->
                <div class="tab-content active" id="dashboard">
                    <div class="content-header">
                        <h2>个人概览</h2>
                        <p>欢迎回来，<?php echo h($user['real_name'] ?: $user['username']); ?>！</p>
                    </div>
                    
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="card-content">
                                <h3><?php echo $userStats['questions']; ?></h3>
                                <p>我的提问</p>
                                <a href="#my-questions" class="card-link" data-tab="my-questions">查看全部</a>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="card-content">
                                <h3><?php echo $userStats['answers']; ?></h3>
                                <p>我的回答</p>
                                <a href="#my-answers" class="card-link" data-tab="my-answers">查看全部</a>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="card-content">
                                <h3><?php echo $userStats['favorites']; ?></h3>
                                <p>我的收藏</p>
                                <a href="#favorites" class="card-link" data-tab="favorites">查看全部</a>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-content">
                                <h3><?php echo $userStats['appointments']; ?></h3>
                                <p>预约记录</p>
                                <a href="#appointments" class="card-link" data-tab="appointments">查看全部</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <h3>最近活动</h3>
                        <div class="activity-tabs">
                            <button class="tab-btn active" data-activity="questions">最近提问</button>
                            <button class="tab-btn" data-activity="answers">最近回答</button>
                        </div>
                        
                        <div class="activity-content" id="activity-questions">
                            <?php if ($recentQuestions): ?>
                                <div class="activity-list">
                                    <?php foreach ($recentQuestions as $question): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4>
                                                    <a href="/qa/detail.php?id=<?php echo $question['id']; ?>">
                                                        <?php echo h($question['title']); ?>
                                                    </a>
                                                </h4>
                                                <div class="activity-meta">
                                                    <span class="category"><?php echo h($question['category_name']); ?></span>
                                                    <span class="time"><?php echo timeAgo($question['created_at']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-question-circle"></i>
                                    <p>您还没有提问过</p>
                                    <a href="/qa/ask.php" class="btn btn-primary">立即提问</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="activity-content" id="activity-answers" style="display: none;">
                            <?php if ($recentAnswers): ?>
                                <div class="activity-list">
                                    <?php foreach ($recentAnswers as $answer): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-comment"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4>
                                                    <a href="/qa/detail.php?id=<?php echo $answer['question_id']; ?>">
                                                        <?php echo h($answer['question_title']); ?>
                                                    </a>
                                                </h4>
                                                <p><?php echo h(truncate($answer['content'], 100)); ?></p>
                                                <div class="activity-meta">
                                                    <span class="time"><?php echo timeAgo($answer['created_at']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment"></i>
                                    <p>您还没有回答过问题</p>
                                    <a href="/qa/" class="btn btn-primary">去回答问题</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 编辑资料 -->
                <div class="tab-content" id="profile-edit">
                    <div class="content-header">
                        <h2>编辑资料</h2>
                        <p>完善您的个人信息，获得更好的服务体验</p>
                    </div>
                    
                    <?php if ($updateSuccess): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            资料更新成功！
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($updateError): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($updateError); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="profile-form" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="real_name" class="form-label">真实姓名</label>
                                <input type="text" name="real_name" id="real_name" class="form-control" 
                                       value="<?php echo h($user['real_name']); ?>" 
                                       placeholder="请输入您的真实姓名">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="form-label">性别</label>
                                <select name="gender" id="gender" class="form-control">
                                    <option value="">请选择性别</option>
                                    <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>男</option>
                                    <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>女</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="birthday" class="form-label">出生日期</label>
                                <input type="date" name="birthday" id="birthday" class="form-control" 
                                       value="<?php echo h($user['birthday']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">手机号码</label>
                                <input type="tel" name="phone" id="phone" class="form-control" 
                                       value="<?php echo h($user['phone']); ?>" 
                                       placeholder="请输入手机号码">
                            </div>
                            
                            <div class="form-group">
                                <label for="city" class="form-label">所在城市</label>
                                <input type="text" name="city" id="city" class="form-control" 
                                       value="<?php echo h($user['city']); ?>" 
                                       placeholder="请输入所在城市">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio" class="form-label">个人简介</label>
                            <textarea name="bio" id="bio" class="form-control" rows="4" 
                                      placeholder="简单介绍一下自己..."><?php echo h($user['bio']); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                保存资料
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- 我的提问 -->
                <div class="tab-content" id="my-questions">
                    <div class="content-header">
                        <h2>我的提问</h2>
                        <a href="/qa/ask.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            提出新问题
                        </a>
                    </div>
                    
                    <div class="questions-container">
                        <!-- 这里会通过AJAX加载问题列表 -->
                        <div class="loading-placeholder">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>加载中...</p>
                        </div>
                    </div>
                </div>
                
                <!-- 我的回答 -->
                <div class="tab-content" id="my-answers">
                    <div class="content-header">
                        <h2>我的回答</h2>
                        <a href="/qa/" class="btn btn-primary">
                            <i class="fas fa-reply"></i>
                            去回答问题
                        </a>
                    </div>
                    
                    <div class="answers-container">
                        <!-- 这里会通过AJAX加载回答列表 -->
                        <div class="loading-placeholder">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>加载中...</p>
                        </div>
                    </div>
                </div>
                
                <!-- 我的收藏 -->
                <div class="tab-content" id="favorites">
                    <div class="content-header">
                        <h2>我的收藏</h2>
                        <div class="filter-tabs">
                            <button class="filter-btn active" data-type="all">全部</button>
                            <button class="filter-btn" data-type="doctors">医生</button>
                            <button class="filter-btn" data-type="hospitals">医院</button>
                            <button class="filter-btn" data-type="articles">文章</button>
                        </div>
                    </div>
                    
                    <div class="favorites-container">
                        <div class="empty-state">
                            <i class="fas fa-heart"></i>
                            <p>您还没有收藏任何内容</p>
                            <a href="/" class="btn btn-primary">去逛逛</a>
                        </div>
                    </div>
                </div>
                
                <!-- 预约记录 -->
                <div class="tab-content" id="appointments">
                    <div class="content-header">
                        <h2>预约记录</h2>
                        <a href="/doctors/" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i>
                            预约医生
                        </a>
                    </div>
                    
                    <div class="appointments-container">
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p>您还没有预约记录</p>
                            <a href="/doctors/" class="btn btn-primary">立即预约</a>
                        </div>
                    </div>
                </div>
                
                <!-- 账户设置 -->
                <div class="tab-content" id="settings">
                    <div class="content-header">
                        <h2>账户设置</h2>
                        <p>管理您的账户安全和隐私设置</p>
                    </div>
                    
                    <div class="settings-sections">
                        <div class="setting-section">
                            <h3>账户信息</h3>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>用户名</strong>
                                    <span><?php echo h($user['username']); ?></span>
                                </div>
                                <button class="btn btn-outline">修改</button>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>邮箱地址</strong>
                                    <span><?php echo h($user['email']); ?></span>
                                </div>
                                <button class="btn btn-outline">修改</button>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>登录密码</strong>
                                    <span>••••••••</span>
                                </div>
                                <button class="btn btn-outline">修改</button>
                            </div>
                        </div>
                        
                        <div class="setting-section">
                            <h3>隐私设置</h3>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>个人资料可见性</strong>
                                    <span>公开</span>
                                </div>
                                <button class="btn btn-outline">设置</button>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>活动记录</strong>
                                    <span>允许显示</span>
                                </div>
                                <button class="btn btn-outline">设置</button>
                            </div>
                        </div>
                        
                        <div class="setting-section danger">
                            <h3>危险操作</h3>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <strong>注销账户</strong>
                                    <span>永久删除您的账户和所有数据</span>
                                </div>
                                <button class="btn btn-danger">注销账户</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 标签页切换
    $('.profile-nav .nav-item').on('click', function(e) {
        e.preventDefault();
        
        const targetTab = $(this).data('tab');
        
        // 更新导航状态
        $('.profile-nav .nav-item').removeClass('active');
        $(this).addClass('active');
        
        // 更新内容区域
        $('.tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');
        
        // 更新URL hash
        window.location.hash = targetTab;
        
        // 加载对应内容
        loadTabContent(targetTab);
    });
    
    // 卡片链接点击事件
    $('.card-link').on('click', function(e) {
        e.preventDefault();
        const targetTab = $(this).data('tab');
        $(`.nav-item[data-tab="${targetTab}"]`).click();
    });
    
    // 活动标签切换
    $('.tab-btn').on('click', function() {
        const activityType = $(this).data('activity');
        
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.activity-content').hide();
        $('#activity-' + activityType).show();
    });
    
    // 加载指定标签的内容
    function loadTabContent(tab) {
        if (tab === 'my-questions') {
            loadMyQuestions();
        } else if (tab === 'my-answers') {
            loadMyAnswers();
        } else if (tab === 'favorites') {
            loadFavorites();
        } else if (tab === 'appointments') {
            loadAppointments();
        }
    }
    
    // 加载我的提问
    function loadMyQuestions() {
        const container = $('.questions-container');
        container.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>');
        
        // 模拟AJAX请求
        setTimeout(() => {
            const hasQuestions = <?php echo $userStats['questions'] > 0 ? 'true' : 'false'; ?>;
            if (hasQuestions) {
                container.html('<div class="empty-state"><i class="fas fa-question-circle"></i><p>您还没有提问过</p><a href="/qa/ask.php" class="btn btn-primary">立即提问</a></div>');
            } else {
                container.html('<div class="empty-state"><i class="fas fa-question-circle"></i><p>您还没有提问过</p><a href="/qa/ask.php" class="btn btn-primary">立即提问</a></div>');
            }
        }, 1000);
    }
    
    // 加载我的回答
    function loadMyAnswers() {
        const container = $('.answers-container');
        container.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>');
        
        setTimeout(() => {
            container.html('<div class="empty-state"><i class="fas fa-comment"></i><p>您还没有回答过问题</p><a href="/qa/" class="btn btn-primary">去回答问题</a></div>');
        }, 1000);
    }
    
    // 加载收藏
    function loadFavorites(type = 'all') {
        const container = $('.favorites-container');
        container.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>');
        
        // 使用收藏管理器获取收藏列表
        if (window.favoritesManager) {
            window.favoritesManager.getFavorites(type, 1, 20).then(result => {
                if (result && result.data && result.data.length > 0) {
                    displayFavorites(result.data, container);
                } else {
                    showEmptyFavorites(container, type);
                }
            }).catch(error => {
                console.error('加载收藏失败:', error);
                container.html('<div class="empty-state"><i class="fas fa-heart"></i><p>加载失败，请刷新重试</p></div>');
            });
        } else {
            // 备用方案：直接AJAX请求
            $.get('/api/favorites.php', { type: type, limit: 20 })
                .done(function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        displayFavorites(response.data, container);
                    } else {
                        showEmptyFavorites(container, type);
                    }
                })
                .fail(function() {
                    container.html('<div class="empty-state"><i class="fas fa-heart"></i><p>加载失败，请刷新重试</p></div>');
                });
        }
    }
    
    // 显示收藏列表
    function displayFavorites(favorites, container) {
        let html = '<div class="favorites-list">';
        
        favorites.forEach(favorite => {
            if (!favorite.item_data) return;
            
            const item = favorite.item_data;
            const itemType = favorite.item_type;
            
            html += '<div class="favorite-item" data-type="' + itemType + '" data-id="' + favorite.item_id + '">';
            
            switch (itemType) {
                case 'doctor':
                    html += `
                        <div class="favorite-header">
                            <div class="item-avatar">
                                ${item.avatar ? 
                                    '<img src="' + item.avatar + '" alt="' + item.name + '">' :
                                    '<div class="avatar-placeholder"><i class="fas fa-user-md"></i></div>'
                                }
                            </div>
                            <div class="item-info">
                                <h4><a href="/doctors/detail.php?id=${item.id}">${item.name}</a></h4>
                                <p class="item-meta">${item.title} | ${item.category_name}</p>
                                <p class="item-desc">${item.hospital_name}</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-outline favorite-btn favorited" data-type="doctor" data-id="${item.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'hospital':
                    html += `
                        <div class="favorite-header">
                            <div class="item-avatar">
                                <div class="avatar-placeholder"><i class="fas fa-hospital"></i></div>
                            </div>
                            <div class="item-info">
                                <h4><a href="/hospitals/detail.php?id=${item.id}">${item.name}</a></h4>
                                <p class="item-meta">${item.type || '综合医院'} | ${item.level || '三级甲等'}</p>
                                <p class="item-desc">${item.address}</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-outline favorite-btn favorited" data-type="hospital" data-id="${item.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'article':
                    html += `
                        <div class="favorite-header">
                            <div class="item-avatar">
                                <div class="avatar-placeholder"><i class="fas fa-newspaper"></i></div>
                            </div>
                            <div class="item-info">
                                <h4><a href="/news/detail.php?id=${item.id}">${item.title}</a></h4>
                                <p class="item-meta">${item.category_name} | ${timeAgo(item.created_at)}</p>
                                <p class="item-desc">${item.summary || ''}</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-outline favorite-btn favorited" data-type="article" data-id="${item.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'disease':
                    html += `
                        <div class="favorite-header">
                            <div class="item-avatar">
                                <div class="avatar-placeholder"><i class="fas fa-procedures"></i></div>
                            </div>
                            <div class="item-info">
                                <h4><a href="/diseases/detail.php?id=${item.id}">${item.name}</a></h4>
                                <p class="item-meta">${item.category_name}</p>
                                <p class="item-desc">${item.summary || ''}</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-outline favorite-btn favorited" data-type="disease" data-id="${item.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'question':
                    html += `
                        <div class="favorite-header">
                            <div class="item-avatar">
                                <div class="avatar-placeholder"><i class="fas fa-question-circle"></i></div>
                            </div>
                            <div class="item-info">
                                <h4><a href="/qa/detail.php?id=${item.id}">${item.title}</a></h4>
                                <p class="item-meta">${item.category_name} | 提问者: ${item.username}</p>
                                <p class="item-desc">${item.content ? item.content.substring(0, 100) + '...' : ''}</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-outline favorite-btn favorited" data-type="question" data-id="${item.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            html += '<div class="favorite-footer">';
            html += '<span class="favorite-time">收藏于 ' + timeAgo(favorite.created_at) + '</span>';
            html += '</div></div>';
        });
        
        html += '</div>';
        container.html(html);
    }
    
    // 显示空收藏状态
    function showEmptyFavorites(container, type) {
        const typeNames = {
            'all': '内容',
            'doctors': '医生',
            'hospitals': '医院',
            'articles': '文章',
            'diseases': '疾病',
            'questions': '问题'
        };
        
        const typeName = typeNames[type] || '内容';
        
        container.html(`
            <div class="empty-state">
                <i class="fas fa-heart"></i>
                <p>您还没有收藏任何${typeName}</p>
                <a href="/" class="btn btn-primary">去逛逛</a>
            </div>
        `);
    }
    
    // 筛选收藏类型
    $('.filter-btn').on('click', function() {
        const type = $(this).data('type');
        
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        loadFavorites(type);
    });
    
    // 时间格式化函数
    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) {
            return '1天前';
        } else if (diffDays < 7) {
            return diffDays + '天前';
        } else if (diffDays < 30) {
            return Math.ceil(diffDays / 7) + '周前';
        } else {
            return Math.ceil(diffDays / 30) + '个月前';
        }
    }
    
    // 加载预约记录
    function loadAppointments() {
        const container = $('.appointments-container');
        container.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>');
        
        $.get('/api/appointments.php')
            .done(function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    displayAppointments(response.data, container);
                } else {
                    showEmptyAppointments(container);
                }
            })
            .fail(function() {
                container.html('<div class="empty-state"><i class="fas fa-calendar-check"></i><p>加载失败，请刷新重试</p></div>');
            });
    }
    
    // 显示预约记录
    function displayAppointments(appointments, container) {
        let html = '<div class="appointments-list">';
        
        appointments.forEach(appointment => {
            const statusClass = appointment.status;
            const statusText = {
                'pending': '待确认',
                'confirmed': '已确认', 
                'cancelled': '已取消',
                'completed': '已完成'
            }[appointment.status] || '未知状态';
            
            html += `
                <div class="appointment-item" data-id="${appointment.id}">
                    <div class="appointment-header">
                        <div class="appointment-info">
                            <h4>${appointment.doctor_name}</h4>
                            <p class="appointment-meta">${appointment.doctor_title} | ${appointment.category_name}</p>
                            <p class="appointment-hospital">${appointment.hospital_name}</p>
                        </div>
                        <div class="appointment-status">
                            <span class="status-badge status-${statusClass}">${statusText}</span>
                            <div class="appointment-number">预约号：${appointment.appointment_number}</div>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <div class="detail-row">
                            <span class="label">预约时间：</span>
                            <span class="value">${formatDate(appointment.appointment_date)} ${appointment.appointment_time}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">患者姓名：</span>
                            <span class="value">${appointment.patient_name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">联系电话：</span>
                            <span class="value">${appointment.patient_phone}</span>
                        </div>
                        ${appointment.symptoms ? `
                        <div class="detail-row">
                            <span class="label">症状描述：</span>
                            <span class="value">${appointment.symptoms}</span>
                        </div>` : ''}
                    </div>
                    <div class="appointment-actions">
                        <span class="appointment-time">预约于 ${timeAgo(appointment.created_at)}</span>
                        <div class="action-buttons">
                            ${appointment.status === 'pending' ? 
                                '<button class="btn btn-sm btn-danger cancel-appointment" data-id="' + appointment.id + '">取消预约</button>' : 
                                ''}
                            <a href="/user/appointment-success.php?id=${appointment.id}" class="btn btn-sm btn-outline">查看详情</a>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
    }
    
    // 显示空预约状态
    function showEmptyAppointments(container) {
        container.html(`
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>您还没有预约记录</p>
                <a href="/doctors/" class="btn btn-primary">立即预约</a>
            </div>
        `);
    }
    
    // 格式化日期
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.getFullYear() + '年' + (date.getMonth() + 1) + '月' + date.getDate() + '日';
    }
    
    // 根据URL hash初始化标签
    const hash = window.location.hash.substring(1);
    if (hash && $(`.nav-item[data-tab="${hash}"]`).length) {
        $(`.nav-item[data-tab="${hash}"]`).click();
    }
    
    // 头像上传
    $('.avatar-upload-btn').on('click', function() {
        showMessage('头像上传功能开发中', 'info');
    });
});

// 显示消息提示
function showMessage(message, type = 'info') {
    const toast = $('<div class="message-toast message-' + type + '">' + message + '</div>');
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php include '../templates/footer.php'; ?>