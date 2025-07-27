<?php
// 获取当前页面用于高亮菜单项
$currentScript = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="user-sidebar">
    <div class="sidebar-header">
        <div class="user-avatar">
            <?php if ($currentUser['avatar']): ?>
                <img src="<?php echo h($currentUser['avatar']); ?>" alt="<?php echo h($currentUser['username']); ?>">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h3><?php echo h($currentUser['username']); ?></h3>
            <p><?php echo h($currentUser['email'] ?? ''); ?></p>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="/user/profile.php" class="nav-link <?php echo $currentScript === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>个人资料</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/appointments.php" class="nav-link <?php echo $currentScript === 'appointments' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>我的预约</span>
                    <?php
                    // 获取待确认预约数量
                    $pendingCount = $db->fetch("
                        SELECT COUNT(*) as count 
                        FROM appointments 
                        WHERE user_id = ? AND status = 'pending'
                    ", [$currentUser['id']])['count'];
                    
                    if ($pendingCount > 0):
                    ?>
                        <span class="nav-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/favorites.php" class="nav-link <?php echo $currentScript === 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i>
                    <span>我的收藏</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/medical-records.php" class="nav-link <?php echo $currentScript === 'medical-records' ? 'active' : ''; ?>">
                    <i class="fas fa-file-medical"></i>
                    <span>就诊记录</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/notifications.php" class="nav-link <?php echo $currentScript === 'notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>消息通知</span>
                    <?php
                    // 获取未读通知数量（如果有通知系统的话）
                    // $unreadCount = getUnreadNotificationCount($currentUser['id']);
                    $unreadCount = 0; // 暂时设为0
                    
                    if ($unreadCount > 0):
                    ?>
                        <span class="nav-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/questions.php" class="nav-link <?php echo $currentScript === 'questions' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>我的提问</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/reviews.php" class="nav-link <?php echo $currentScript === 'reviews' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span>我的评价</span>
                </a>
            </li>
            
            <li class="nav-divider"></li>
            
            <li class="nav-item">
                <a href="/user/settings.php" class="nav-link <?php echo $currentScript === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>账号设置</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/user/privacy.php" class="nav-link <?php echo $currentScript === 'privacy' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>隐私设置</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/help/" class="nav-link">
                    <i class="fas fa-question-circle"></i>
                    <span>帮助中心</span>
                </a>
            </li>
            
            <li class="nav-item logout">
                <a href="/user/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>退出登录</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="quick-actions">
            <a href="/doctors/" class="btn btn-primary btn-block">
                <i class="fas fa-plus"></i>
                快速预约
            </a>
            <a href="/qa/ask.php" class="btn btn-outline btn-block">
                <i class="fas fa-question"></i>
                在线咨询
            </a>
        </div>
    </div>
</aside>