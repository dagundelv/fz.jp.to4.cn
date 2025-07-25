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
                        <ul class="main menu">
                            <li class="<?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                                <a href="/">
                                    <i class="fas fa-home"></i>
                                    <span>首页</span>
                                </a>
                            </li>
                            
                            <li class="dropdown <?php echo $currentPage == 'news' ? 'active' : ''; ?>">
                                <a href="/news/">
                                    <i class="fas fa-newspaper"></i>
                                    <span>健康头条</span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </a>
                                <div class="dropdown-menu">
                                    <a href="/news/">最新资讯</a>
                                    <a href="/news/?category=policy">医疗政策</a>
                                    <a href="/news/?category=research">科研进展</a>
                                    <a href="/news/?category=health">健康科普</a>
                                </div>
                            </li>
                            
                            <li class="dropdown <?php echo $currentPage == 'hospitals' ? 'active' : ''; ?>">
                                <a href="/hospitals/">
                                    <i class="fas fa-hospital"></i>
                                    <span>医院频道</span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </a>
                                <div class="dropdown-menu mega-menu">
                                    <div class="menu-section">
                                        <h4>按科室查找</h4>
                                        <?php
                                        $mainCategories = getCategories(0);
                                        foreach (array_slice($mainCategories, 0, 8) as $category):
                                        ?>
                                        <a href="/hospitals/?category=<?php echo $category['id']; ?>">
                                            <?php echo h($category['name']); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="menu-section">
                                        <h4>按地区查找</h4>
                                        <a href="/hospitals/?city=北京">北京</a>
                                        <a href="/hospitals/?city=上海">上海</a>
                                        <a href="/hospitals/?city=广州">广州</a>
                                        <a href="/hospitals/?city=深圳">深圳</a>
                                    </div>
                                    <div class="menu-section">
                                        <h4>按等级查找</h4>
                                        <a href="/hospitals/?level=三甲">三甲医院</a>
                                        <a href="/hospitals/?level=三乙">三乙医院</a>
                                        <a href="/hospitals/?level=专科">专科医院</a>
                                    </div>
                                </div>
                            </li>
                            
                            <li class="dropdown <?php echo $currentPage == 'doctors' ? 'active' : ''; ?>">
                                <a href="/doctors/">
                                    <i class="fas fa-user-md"></i>
                                    <span>医生频道</span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </a>
                                <div class="dropdown-menu mega-menu">
                                    <div class="menu-section">
                                        <h4>按科室查找</h4>
                                        <?php foreach (array_slice($mainCategories, 0, 8) as $category): ?>
                                        <a href="/doctors/?category=<?php echo $category['id']; ?>">
                                            <?php echo h($category['name']); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="menu-section">
                                        <h4>按职称查找</h4>
                                        <a href="/doctors/?title=主任医师">主任医师</a>
                                        <a href="/doctors/?title=副主任医师">副主任医师</a>
                                        <a href="/doctors/?title=主治医师">主治医师</a>
                                    </div>
                                </div>
                            </li>
                            
                            <li class="dropdown <?php echo $currentPage == 'diseases' ? 'active' : ''; ?>">
                                <a href="/diseases/">
                                    <i class="fas fa-book-medical"></i>
                                    <span>疾病百科</span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </a>
                                <div class="dropdown-menu mega-menu">
                                    <div class="menu-section">
                                        <h4>常见疾病</h4>
                                        <a href="/diseases/?category=11">心血管疾病</a>
                                        <a href="/diseases/?category=12">呼吸系统</a>
                                        <a href="/diseases/?category=13">消化系统</a>
                                        <a href="/diseases/?category=14">内分泌科</a>
                                    </div>
                                    <div class="menu-section">
                                        <h4>专科疾病</h4>
                                        <a href="/diseases/?category=3">妇科疾病</a>
                                        <a href="/diseases/?category=4">儿科疾病</a>
                                        <a href="/diseases/?category=5">骨科疾病</a>
                                        <a href="/diseases/?category=6">皮肤疾病</a>
                                    </div>
                                </div>
                            </li>
                            
                            <li class="dropdown <?php echo $currentPage == 'qa' ? 'active' : ''; ?>">
                                <a href="/qa/">
                                    <i class="fas fa-question-circle"></i>
                                    <span>健康问答</span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </a>
                                <div class="dropdown-menu">
                                    <a href="/qa/">最新问题</a>
                                    <a href="/qa/hot.php">热门问题</a>
                                    <a href="/qa/ask.php">我要提问</a>
                                    <a href="/qa/experts.php">专家解答</a>
                                </div>
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
                <a href="/news/" class="menu-item">
                    <i class="fas fa-newspaper"></i> 健康头条
                </a>
                <a href="/hospitals/" class="menu-item">
                    <i class="fas fa-hospital"></i> 医院频道
                </a>
                <a href="/doctors/" class="menu-item">
                    <i class="fas fa-user-md"></i> 医生频道
                </a>
                <a href="/diseases/" class="menu-item">
                    <i class="fas fa-book-medical"></i> 疾病百科
                </a>
                <a href="/qa/" class="menu-item">
                    <i class="fas fa-question-circle"></i> 健康问答
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-content"><?php // 主要内容区域开始 ?>