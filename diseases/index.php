<?php
require_once '../includes/init.php';

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$difficulty = $_GET['difficulty'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = "疾病百科 - " . SITE_NAME;
$pageDescription = "疾病百科提供全面的疾病信息，包括症状、病因、治疗方法、预防措施等专业医学知识";
$pageKeywords = "疾病百科,疾病查询,症状查询,医学百科,健康知识";
$currentPage = 'diseases';

// 构建查询条件
$whereConditions = ["d.status = 'active'"];
$queryParams = [];

if ($category) {
    $whereConditions[] = "d.category_id = ?";
    $queryParams[] = $category;
}

if ($keyword) {
    $whereConditions[] = "(d.name LIKE ? OR d.symptoms LIKE ? OR d.description LIKE ?)";
    $searchKeyword = "%{$keyword}%";
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
}

if ($difficulty) {
    $whereConditions[] = "d.difficulty = ?";
    $queryParams[] = $difficulty;
}

$whereClause = implode(' AND ', $whereConditions);

// 设置排序
$orderBy = "d.name ASC";
switch ($sort) {
    case 'views':
        $orderBy = "d.view_count DESC, d.name ASC";
        break;
    case 'updated':
        $orderBy = "d.updated_at DESC, d.name ASC";
        break;
    case 'difficulty':
        $orderBy = "FIELD(d.difficulty, '轻微', '一般', '严重', '危重'), d.name ASC";
        break;
    default:
        $orderBy = "d.name ASC";
}

// 获取疾病列表
$offset = ($page - 1) * $pageSize;
$countParams = $queryParams;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$totalDiseases = $db->fetch("
    SELECT COUNT(*) as count 
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE {$whereClause}
", $countParams)['count'];

$diseases = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT ? OFFSET ?
", $listParams);

$totalPages = ceil($totalDiseases / $pageSize);

// 获取筛选选项数据
$categories = getCategories(0);

// 获取热门疾病
$popularDiseases = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.status = 'active'
    ORDER BY d.view_count DESC
    LIMIT 10
");

// 获取最新更新的疾病
$recentDiseases = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.status = 'active'
    ORDER BY d.updated_at DESC
    LIMIT 8
");

// 按字母分组
$diseasesByLetter = [];
foreach ($diseases as $disease) {
    $firstChar = mb_substr($disease['name'], 0, 1, 'UTF-8');
    if (preg_match('/[A-Za-z]/', $firstChar)) {
        $firstChar = strtoupper($firstChar);
    }
    $diseasesByLetter[$firstChar][] = $disease;
}

// 添加页面特定的CSS
$pageCSS = ['/assets/css/diseases.css'];

include '../templates/header.php';
?>

<div class="diseases-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [['title' => '疾病百科', 'url' => '/diseases/']];
            if ($category) {
                $categoryInfo = getCategoryById($category);
                if ($categoryInfo) {
                    $breadcrumbs[] = ['title' => $categoryInfo['name']];
                }
            }
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <!-- 搜索区域 -->
        <div class="disease-search-section">
            <div class="search-header">
                <h1>疾病百科</h1>
                <p>提供全面的疾病信息，帮助您了解疾病症状、治疗和预防</p>
            </div>
            
            <form class="disease-search-form" method="GET">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="搜索疾病名称、症状..." 
                           value="<?php echo h($keyword); ?>" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        搜索疾病
                    </button>
                </div>
                
                <!-- 保持其他筛选条件 -->
                <?php if ($category): ?><input type="hidden" name="category" value="<?php echo h($category); ?>"><?php endif; ?>
                <?php if ($difficulty): ?><input type="hidden" name="difficulty" value="<?php echo h($difficulty); ?>"><?php endif; ?>
                <?php if ($sort): ?><input type="hidden" name="sort" value="<?php echo h($sort); ?>"><?php endif; ?>
            </form>
            
            <!-- 快速导航 -->
            <div class="quick-nav">
                <div class="nav-title">常见疾病分类：</div>
                <div class="nav-links">
                    <a href="/diseases/?category=1">内科疾病</a>
                    <a href="/diseases/?category=2">外科疾病</a>
                    <a href="/diseases/?category=3">妇科疾病</a>
                    <a href="/diseases/?category=4">儿科疾病</a>
                    <a href="/diseases/?category=5">骨科疾病</a>
                    <a href="/diseases/?category=6">皮肤科疾病</a>
                </div>
            </div>
        </div>
        
        <div class="diseases-layout">
            <!-- 筛选侧边栏 -->
            <aside class="diseases-filters">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        筛选条件
                    </h3>
                    <a href="/diseases/" class="clear-filters">清除筛选</a>
                </div>
                
                <!-- 按科室筛选 -->
                <div class="filter-group">
                    <h4>按科室查找</h4>
                    <div class="filter-options">
                        <?php foreach ($categories as $cat): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['id'], 'page' => 1])); ?>" 
                               class="filter-option <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo h($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 按严重程度筛选 -->
                <div class="filter-group">
                    <h4>按严重程度</h4>
                    <div class="filter-options">
                        <?php 
                        $difficulties = ['轻微', '一般', '严重', '危重'];
                        foreach ($difficulties as $diff): 
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['difficulty' => $diff, 'page' => 1])); ?>" 
                               class="filter-option <?php echo $difficulty == $diff ? 'active' : ''; ?>">
                                <span class="difficulty-badge difficulty-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $diff); ?>">
                                    <?php echo $diff; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 热门疾病 -->
                <div class="filter-group">
                    <h4>热门疾病</h4>
                    <div class="popular-diseases">
                        <?php foreach (array_slice($popularDiseases, 0, 8) as $popular): ?>
                            <div class="popular-item">
                                <h5>
                                    <a href="/diseases/detail.php?id=<?php echo $popular['id']; ?>">
                                        <?php echo h($popular['name']); ?>
                                    </a>
                                </h5>
                                <div class="disease-meta">
                                    <span class="category"><?php echo h($popular['category_name']); ?></span>
                                    <span class="views">
                                        <i class="fas fa-eye"></i>
                                        <?php echo number_format($popular['view_count']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 字母索引 -->
                <div class="filter-group">
                    <h4>按首字母查找</h4>
                    <div class="alphabet-nav">
                        <?php
                        $alphabet = range('A', 'Z');
                        $chinese_common = ['按', '便', '肠', '胆', '发', '肺', '肝', '高', '骨', '过', '急', '甲', '结', '颈', '口', '类', '慢', '脑', '皮', '气', '乳', '神', '肾', '糖', '胃', '心', '血', '牙', '眼', '腰'];
                        foreach (array_merge($alphabet, $chinese_common) as $letter):
                        ?>
                            <a href="#letter-<?php echo $letter; ?>" class="alphabet-link"><?php echo $letter; ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            
            <!-- 主要内容区 -->
            <main class="diseases-main">
                <!-- 搜索结果统计 -->
                <div class="results-header">
                    <div class="results-info">
                        <h2>
                            <?php if ($keyword): ?>
                                搜索"<?php echo h($keyword); ?>"的结果
                            <?php elseif ($category): ?>
                                <?php 
                                $categoryInfo = getCategoryById($category);
                                echo h($categoryInfo['name'] ?? '未知分类');
                                ?>疾病
                            <?php else: ?>
                                全部疾病
                            <?php endif; ?>
                        </h2>
                        <p>共找到 <strong><?php echo number_format($totalDiseases); ?></strong> 个疾病</p>
                    </div>
                    
                    <div class="results-sort">
                        <select class="sort-select" onchange="location.href=this.value">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" 
                                    <?php echo $sort == 'name' ? 'selected' : ''; ?>>
                                按名称排序
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'views'])); ?>" 
                                    <?php echo $sort == 'views' ? 'selected' : ''; ?>>
                                按热度排序
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'updated'])); ?>" 
                                    <?php echo $sort == 'updated' ? 'selected' : ''; ?>>
                                最新更新
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'difficulty'])); ?>" 
                                    <?php echo $sort == 'difficulty' ? 'selected' : ''; ?>>
                                按严重程度
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if ($diseases): ?>
                    <!-- 疾病列表 -->
                    <div class="diseases-list">
                        <?php if ($sort == 'name' && !$keyword): ?>
                            <!-- 按字母分组显示 -->
                            <?php foreach ($diseasesByLetter as $letter => $letterDiseases): ?>
                                <div class="letter-group" id="letter-<?php echo $letter; ?>">
                                    <h3 class="letter-header"><?php echo $letter; ?></h3>
                                    <div class="letter-diseases">
                                        <?php foreach ($letterDiseases as $disease): ?>
                                            <div class="disease-card">
                                                <div class="disease-content">
                                                    <div class="disease-header">
                                                        <h4 class="disease-name">
                                                            <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>">
                                                                <?php echo h($disease['name']); ?>
                                                            </a>
                                                        </h4>
                                                        
                                                        <div class="disease-badges">
                                                            <span class="category-badge">
                                                                <?php echo h($disease['category_name']); ?>
                                                            </span>
                                                            <?php if ($disease['difficulty']): ?>
                                                                <span class="difficulty-badge difficulty-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $disease['difficulty']); ?>">
                                                                    <?php echo h($disease['difficulty']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($disease['symptoms']): ?>
                                                        <div class="disease-symptoms">
                                                            <strong>主要症状：</strong>
                                                            <?php echo h(truncate($disease['symptoms'], 100)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($disease['description']): ?>
                                                        <div class="disease-description">
                                                            <?php echo h(truncate(strip_tags($disease['description']), 150)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="disease-meta">
                                                        <span class="views">
                                                            <i class="fas fa-eye"></i>
                                                            <?php echo number_format($disease['view_count']); ?>次查看
                                                        </span>
                                                        
                                                        <?php if ($disease['updated_at']): ?>
                                                            <span class="updated">
                                                                <i class="fas fa-clock"></i>
                                                                更新于 <?php echo date('Y-m-d', strtotime($disease['updated_at'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="disease-actions">
                                                    <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>" 
                                                       class="btn btn-primary">
                                                        详细了解
                                                    </a>
                                                    
                                                    <button class="btn btn-outline favorite-btn" 
                                                            data-type="disease" 
                                                            data-id="<?php echo $disease['id']; ?>">
                                                        <i class="far fa-bookmark"></i>
                                                        收藏
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- 普通列表显示 -->
                            <?php foreach ($diseases as $disease): ?>
                                <div class="disease-card">
                                    <div class="disease-content">
                                        <div class="disease-header">
                                            <h4 class="disease-name">
                                                <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>">
                                                    <?php echo h($disease['name']); ?>
                                                </a>
                                            </h4>
                                            
                                            <div class="disease-badges">
                                                <span class="category-badge">
                                                    <?php echo h($disease['category_name']); ?>
                                                </span>
                                                <?php if ($disease['difficulty']): ?>
                                                    <span class="difficulty-badge difficulty-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $disease['difficulty']); ?>">
                                                        <?php echo h($disease['difficulty']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($disease['symptoms']): ?>
                                            <div class="disease-symptoms">
                                                <strong>主要症状：</strong>
                                                <?php echo h(truncate($disease['symptoms'], 100)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($disease['description']): ?>
                                            <div class="disease-description">
                                                <?php echo h(truncate(strip_tags($disease['description']), 150)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="disease-meta">
                                            <span class="views">
                                                <i class="fas fa-eye"></i>
                                                <?php echo number_format($disease['view_count']); ?>次查看
                                            </span>
                                            
                                            <?php if ($disease['updated_at']): ?>
                                                <span class="updated">
                                                    <i class="fas fa-clock"></i>
                                                    更新于 <?php echo date('Y-m-d', strtotime($disease['updated_at'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="disease-actions">
                                        <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>" 
                                           class="btn btn-primary">
                                            详细了解
                                        </a>
                                        
                                        <button class="btn btn-outline favorite-btn" 
                                                data-type="disease" 
                                                data-id="<?php echo $disease['id']; ?>">
                                            <i class="far fa-bookmark"></i>
                                            收藏
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-section">
                            <?php
                            $paginationParams = $_GET;
                            unset($paginationParams['page']);
                            echo generatePagination($page, $totalPages, '/diseases/', $paginationParams);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 无结果提示 -->
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-book-medical"></i>
                        </div>
                        <h3>没有找到相关疾病</h3>
                        <p>
                            <?php if ($keyword): ?>
                                抱歉，没有找到与"<strong><?php echo h($keyword); ?></strong>"相关的疾病
                            <?php else: ?>
                                当前筛选条件下没有找到相关疾病
                            <?php endif; ?>
                        </p>
                        
                        <div class="no-results-suggestions">
                            <h4>建议您：</h4>
                            <ul>
                                <li>检查输入的关键词是否正确</li>
                                <li>尝试使用更通用的搜索词</li>
                                <li>浏览热门疾病分类</li>
                                <li><a href="/diseases/">查看全部疾病</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 最新更新疾病 -->
                <?php if (!$keyword && $recentDiseases): ?>
                    <div class="recent-updates-section">
                        <h3>最新更新</h3>
                        <div class="recent-diseases">
                            <?php foreach ($recentDiseases as $recent): ?>
                                <div class="recent-item">
                                    <h5>
                                        <a href="/diseases/detail.php?id=<?php echo $recent['id']; ?>">
                                            <?php echo h($recent['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="recent-meta">
                                        <span class="category"><?php echo h($recent['category_name']); ?></span>
                                        <span class="date"><?php echo date('m-d', strtotime($recent['updated_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 字母索引导航
    $('.alphabet-link').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        if ($(target).length) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 300);
        }
    });
    
    // 收藏功能
    $('.favorite-btn').on('click', function() {
        const $btn = $(this);
        const type = $btn.data('type');
        const id = $btn.data('id');
        
        // 这里可以添加AJAX收藏逻辑
        if ($btn.hasClass('favorited')) {
            $btn.removeClass('favorited');
            $btn.find('i').removeClass('fas').addClass('far');
            $btn.find('i').removeClass('fa-bookmark').addClass('fa-bookmark');
            showMessage('已取消收藏', 'info');
        } else {
            $btn.addClass('favorited');
            $btn.find('i').removeClass('far').addClass('fas');
            showMessage('收藏成功', 'success');
        }
    });
    
    // 搜索建议
    $('#searchInput').on('input', function() {
        const keyword = $(this).val().trim();
        if (keyword.length >= 2) {
            // 这里可以添加搜索建议的AJAX逻辑
        }
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