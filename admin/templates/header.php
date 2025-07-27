<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '管理员后台 - ' . SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/admin.js"></script>
</head>
<body class="admin-body">
    <div class="admin-layout">
        <!-- 侧边栏 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h1><a href="/admin/">管理后台</a></h1>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="/admin/" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>控制台</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>用户管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/doctors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-md"></i>
                            <span>医生管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/hospitals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'hospitals.php' ? 'active' : ''; ?>">
                            <i class="fas fa-hospital"></i>
                            <span>医院管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/articles.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'articles.php' ? 'active' : ''; ?>">
                            <i class="fas fa-newspaper"></i>
                            <span>文章管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/qa.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'qa.php' ? 'active' : ''; ?>">
                            <i class="fas fa-question-circle"></i>
                            <span>问答管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/diseases.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'diseases.php' ? 'active' : ''; ?>">
                            <i class="fas fa-virus"></i>
                            <span>疾病管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/appointments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i>
                            <span>预约管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i>
                            <span>分类管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/comments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-comments"></i>
                            <span>评论管理</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/admin/settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-footer">
                    <a href="/" class="back-to-site" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>返回网站</span>
                    </a>
                    
                    <a href="/user/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>退出登录</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- 主要内容区 -->
        <main class="admin-main">
            <div class="admin-content">