<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <meta name="keywords" content="<?php echo h($pageKeywords); ?>">
    <title><?php echo h($pageTitle); ?></title>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- 页面特定CSS -->
    <?php if (isset($pageCSS) && is_array($pageCSS)): ?>
        <?php foreach ($pageCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="main-header">
        <!-- 顶部工具栏 -->
        <div class="top-bar">
            <div class="container">
                <div class="top-left">
                    <span><i class="fas fa-phone"></i> 客服热线：400-123-4567</span>
                    <span><i class="fas fa-envelope"></i> support@health.com</span>
                </div>
                <div class="top-right">
                    <?php if ($currentUser): ?>
                        <div class="user-menu">
                            <span class="username">
                                <i class="fas fa-user"></i>
                                <?php echo h($currentUser['username']); ?>
                            </span>
                            <div class="dropdown">
                                <a href="/user/profile.php">个人中心</a>
                                <a href="/user/favorites.php">我的收藏</a>
                                <a href="/user/appointments.php">预约记录</a>
                                <a href="/user/logout.php">退出登录</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/user/login.php" class="login-btn">登录</a>
                        <a href="/user/register.php" class="register-btn">注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 主导航栏 -->
        <nav class="main-nav">
            <div class="container">
                <div class="nav-left">
                    <div class="logo">
                        <a href="/">
                            <img src="/assets/images/logo.png" alt="<?php echo h(SITE_NAME); ?>" onerror="this.style.display='none'">
                            <span class="logo-text"><?php echo h(SITE_NAME); ?></span>
                        </a>
                    </div>
                </div>
                
                <div class="nav-center">
                    <!-- 全局搜索框 -->
                    <div class="search-box">
                        <form action="/search.php" method="GET" class="search-form">
                            <div class="search-input-wrapper">
                                <input type="text" name="q" placeholder="搜索医院、医生、疾病..." 
                                       value="<?php echo h($_GET['q'] ?? ''); ?>" 
                                       class="search-input" id="searchInput" autocomplete="off">
                                <div class="search-suggestions" id="searchSuggestions"></div>
                            </div>
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="nav-right">
                    <div class="nav-menu">
                        <ul class="main-menu">
                            <li class="<?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                                <a href="/">
                                    <i class="fas fa-home"></i>
                                    <span>首页</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- 移动端菜单按钮 -->
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </nav>
        
        <!-- 移动端导航菜单 -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <span class="logo-text"><?php echo h(SITE_NAME); ?></span>
                <button class="close-btn">×</button>
            </div>
            <div class="mobile-menu-content">
                <a href="/" class="menu-item">
                    <i class="fas fa-home"></i> 首页
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-content"><?php // 主要内容区域开始 ?>