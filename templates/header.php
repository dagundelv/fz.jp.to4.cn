<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <?php
    // 生成SEO优化的元数据
    $seoData = generatePageMeta([
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'image' => $pageImage ?? null
    ]);
    
    // 设置Open Graph数据
    seo()->setOpenGraph([
        'og:title' => $seoData['title'],
        'og:description' => $seoData['description'],
        'og:url' => $seoData['canonical'],
        'og:image' => $seoData['image'] ?? SITE_URL . '/assets/images/og-default.jpg'
    ]);
    
    // 设置Twitter Card数据
    seo()->setTwitterCard([
        'twitter:title' => $seoData['title'],
        'twitter:description' => $seoData['description'],
        'twitter:image' => $seoData['image'] ?? SITE_URL . '/assets/images/og-default.jpg'
    ]);
    
    // 生成网站组织架构化数据
    if (!isset($skipOrganizationSchema)) {
        seo()->generateOrganizationSchema();
        seo()->generateSearchBoxSchema();
    }
    
    // 输出元标签
    echo seo()->generateMetaTags($seoData);
    ?>
    <title><?php echo h($seoData['title']); ?></title>
    
    <!-- 预加载关键资源 -->
    <?php
    PerformanceOptimizer::preloadResource(asset_url('/assets/css/style.css'), 'css');
    PerformanceOptimizer::preloadResource(asset_url('/assets/js/main.js'), 'script');
    ?>
    
    <!-- 内联关键CSS -->
    <?php echo PerformanceOptimizer::inlineCriticalCSS('/assets/css/critical.css'); ?>
    
    <!-- 非关键CSS延迟加载 -->
    <link rel="stylesheet" href="<?php echo asset_url('/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/assets/css/responsive.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/assets/css/common.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/assets/css/homepage-new.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/assets/css/dropdown-fix.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" media="print" onload="this.media='all'">
    
    <!-- 页面特定CSS -->
    <?php if (isset($pageCSS) && is_array($pageCSS)): ?>
        <?php foreach ($pageCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset_url($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- 延迟加载非关键脚本 -->
    <?php echo PerformanceOptimizer::deferScript('https://code.jquery.com/jquery-3.6.0.min.js'); ?>
    <?php echo PerformanceOptimizer::deferScript(asset_url('/assets/js/main.js')); ?>
    
    <!-- 结构化数据 -->
    <?php echo seo()->generateStructuredData(); ?>
</head>
<body>
    <header class="main-header">
        <!-- 顶部信息栏 -->
        <div class="top-info-bar">
            <div class="container">
                <div class="top-info-content">
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>客服热线：400-123-4567</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@health.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>服务时间：8:00-22:00</span>
                        </div>
                    </div>
                    
                    <div class="top-info-actions">
                        <?php if ($currentUser): ?>
                            <div class="user-profile">
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
                                    <span class="welcome-text">欢迎您，</span>
                                    <span class="username"><?php echo h($currentUser['username']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="user-dropdown">
                                    <div class="dropdown-header">
                                        <div class="user-meta">
                                            <strong><?php echo h($currentUser['username']); ?></strong>
                                            <small><?php echo h($currentUser['email'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <div class="dropdown-menu-items">
                                        <a href="/user/profile.php" class="dropdown-item">
                                            <i class="fas fa-user-circle"></i>
                                            <span>个人中心</span>
                                        </a>
                                        <a href="/user/favorites.php" class="dropdown-item">
                                            <i class="fas fa-heart"></i>
                                            <span>我的收藏</span>
                                        </a>
                                        <a href="/user/appointments.php" class="dropdown-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>预约记录</span>
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="/user/logout.php" class="dropdown-item logout">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span>退出登录</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-buttons">
                                <a href="/user/login.php" class="auth-btn login-btn">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>登录</span>
                                </a>
                                <a href="/user/register.php" class="auth-btn register-btn">
                                    <i class="fas fa-user-plus"></i>
                                    <span>注册</span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="quick-links">
                            <a href="/help/" class="quick-link" title="帮助中心">
                                <i class="fas fa-question-circle"></i>
                            </a>
                            <a href="/feedback/" class="quick-link" title="意见反馈">
                                <i class="fas fa-comment-alt"></i>
                            </a>
                        </div>
                    </div>
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
                    <!-- 主导航菜单 -->
                    <div class="main-navigation">
                        <ul class="nav-menu">
                            <li class="nav-item dropdown-item <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                                <a href="/" class="nav-link">
                                    <i class="fas fa-home"></i>
                                    <span>首页</span>
                                </a>
                            </li>
                            <li class="nav-item dropdown-item <?php echo strpos($_SERVER['REQUEST_URI'], '/hospitals/') === 0 ? 'active' : ''; ?>">
                                <a href="/hospitals/" class="nav-link">
                                    <i class="fas fa-hospital"></i>
                                    <span>找医院</span>
                                </a>
                                <div class="dropdown-menu">
                                    <div class="dropdown-section">
                                        <h4>按等级查找</h4>
                                        <a href="/hospitals/?level=三甲">三甲医院</a>
                                        <a href="/hospitals/?level=三乙">三乙医院</a>
                                        <a href="/hospitals/?level=二甲">二甲医院</a>
                                    </div>
                                    <div class="dropdown-section">
                                        <h4>按专科查找</h4>
                                        <a href="/hospitals/?specialty=综合">综合医院</a>
                                        <a href="/hospitals/?specialty=专科">专科医院</a>
                                        <a href="/hospitals/?specialty=中医">中医医院</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown-item <?php echo strpos($_SERVER['REQUEST_URI'], '/doctors/') === 0 ? 'active' : ''; ?>">
                                <a href="/doctors/" class="nav-link">
                                    <i class="fas fa-user-md"></i>
                                    <span>找医生</span>
                                </a>
                                <div class="dropdown-menu">
                                    <div class="dropdown-section">
                                        <h4>热门科室</h4>
                                        <a href="/doctors/?department=内科">内科医生</a>
                                        <a href="/doctors/?department=外科">外科医生</a>
                                        <a href="/doctors/?department=妇科">妇科医生</a>
                                        <a href="/doctors/?department=儿科">儿科医生</a>
                                        <a href="/doctors/?department=骨科">骨科医生</a>
                                        <a href="/doctors/?department=皮肤科">皮肤科医生</a>
                                    </div>
                                    <div class="dropdown-section">
                                        <h4>医生职称</h4>
                                        <a href="/doctors/?title=主任医师">主任医师</a>
                                        <a href="/doctors/?title=副主任医师">副主任医师</a>
                                        <a href="/doctors/?title=主治医师">主治医师</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown-item <?php echo strpos($_SERVER['REQUEST_URI'], '/diseases/') === 0 ? 'active' : ''; ?>">
                                <a href="/diseases/" class="nav-link">
                                    <i class="fas fa-stethoscope"></i>
                                    <span>疾病百科</span>
                                </a>
                                <div class="dropdown-menu">
                                    <div class="dropdown-section">
                                        <h4>常见疾病</h4>
                                        <a href="/diseases/?category=常见病">常见病</a>
                                        <a href="/diseases/?category=慢性病">慢性病</a>
                                        <a href="/diseases/?category=传染病">传染病</a>
                                    </div>
                                    <div class="dropdown-section">
                                        <h4>按系统分类</h4>
                                        <a href="/diseases/?system=呼吸系统">呼吸系统</a>
                                        <a href="/diseases/?system=消化系统">消化系统</a>
                                        <a href="/diseases/?system=心血管系统">心血管系统</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown-item <?php echo strpos($_SERVER['REQUEST_URI'], '/news/') === 0 ? 'active' : ''; ?>">
                                <a href="/news/" class="nav-link">
                                    <i class="fas fa-newspaper"></i>
                                    <span>健康资讯</span>
                                </a>
                                <div class="dropdown-menu">
                                    <div class="dropdown-section">
                                        <h4>资讯分类</h4>
                                        <a href="/news/?category=1">医学前沿</a>
                                        <a href="/news/?category=2">健康科普</a>
                                        <a href="/news/?category=3">政策解读</a>
                                        <a href="/news/?category=4">行业动态</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown-item <?php echo strpos($_SERVER['REQUEST_URI'], '/qa/') === 0 ? 'active' : ''; ?>">
                                <a href="/qa/" class="nav-link">
                                    <i class="fas fa-question-circle"></i>
                                    <span>问答社区</span>
                                </a>
                                <div class="dropdown-menu">
                                    <div class="dropdown-section">
                                        <h4>热门问答</h4>
                                        <a href="/qa/?category=hot">热门问答</a>
                                        <a href="/qa/?category=latest">最新问答</a>
                                        <a href="/qa/?category=waiting">待回答</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- 搜索框 -->
                    <div class="nav-search">
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
                    <div class="nav-actions">
                        <?php if ($currentUser): ?>
                            <a href="/appointment/book.php" class="nav-btn book-btn">
                                <i class="fas fa-calendar-plus"></i>
                                <span>预约挂号</span>
                            </a>
                        <?php else: ?>
                            <a href="/user/login.php" class="nav-btn login-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>登录</span>
                            </a>
                        <?php endif; ?>
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
                <a href="/" class="menu-item <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> 首页
                </a>
                <a href="/hospitals/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/hospitals/') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-hospital"></i> 找医院
                </a>
                <a href="/doctors/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/doctors/') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i> 找医生
                </a>
                <a href="/diseases/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/diseases/') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-stethoscope"></i> 疾病百科
                </a>
                <a href="/news/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/news/') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i> 健康资讯
                </a>
                <a href="/qa/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/qa/') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i> 问答社区
                </a>
                <?php if ($currentUser): ?>
                    <div class="mobile-menu-divider"></div>
                    <a href="/appointment/book.php" class="menu-item">
                        <i class="fas fa-calendar-plus"></i> 预约挂号
                    </a>
                    <a href="/user/profile.php" class="menu-item">
                        <i class="fas fa-user"></i> 个人中心
                    </a>
                    <a href="/user/logout.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i> 退出登录
                    </a>
                <?php else: ?>
                    <div class="mobile-menu-divider"></div>
                    <a href="/user/login.php" class="menu-item">
                        <i class="fas fa-sign-in-alt"></i> 登录
                    </a>
                    <a href="/user/register.php" class="menu-item">
                        <i class="fas fa-user-plus"></i> 注册
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="main-content"><?php // 主要内容区域开始 ?>