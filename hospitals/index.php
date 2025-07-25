<?php
require_once '../includes/init.php';

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$city = $_GET['city'] ?? '';
$level = $_GET['level'] ?? '';
$type = $_GET['type'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = "医院查询 - " . SITE_NAME;
$pageDescription = "查找全国各地医院信息，包括三甲医院、专科医院等，提供医院介绍、科室信息、专家团队等详细资料";
$pageKeywords = "医院查询,三甲医院,专科医院,医院信息,医院科室";

// 构建查询条件
$whereConditions = ["h.status = 'active'"];
$queryParams = [];

if ($category) {
    // 通过科室查找相关医院（通过医生表关联）
    $whereConditions[] = "EXISTS (SELECT 1 FROM doctors d WHERE d.hospital_id = h.id AND d.category_id = ? AND d.status = 'active')";
    $queryParams[] = $category;
}

if ($city) {
    $whereConditions[] = "h.city = ?";
    $queryParams[] = $city;
}

if ($level) {
    $whereConditions[] = "h.level = ?";
    $queryParams[] = $level;
}

if ($type) {
    $whereConditions[] = "h.type = ?";
    $queryParams[] = $type;
}

if ($keyword) {
    $whereConditions[] = "(h.name LIKE ? OR h.address LIKE ? OR h.specialties LIKE ?)";
    $searchKeyword = "%{$keyword}%";
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
}

$whereClause = implode(' AND ', $whereConditions);

// 获取医院列表
$offset = ($page - 1) * $pageSize;
$countParams = $queryParams;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$totalHospitals = $db->fetch("
    SELECT COUNT(*) as count 
    FROM hospitals h 
    WHERE {$whereClause}
", $countParams)['count'];

$hospitals = $db->fetchAll("
    SELECT h.*, 
           (SELECT COUNT(*) FROM doctors d WHERE d.hospital_id = h.id AND d.status = 'active') as doctor_count
    FROM hospitals h 
    WHERE {$whereClause}
    ORDER BY h.level DESC, h.rating DESC, h.name ASC
    LIMIT ? OFFSET ?
", $listParams);

$totalPages = ceil($totalHospitals / $pageSize);

// 获取筛选选项数据
$cities = $db->fetchAll("
    SELECT city, COUNT(*) as count 
    FROM hospitals 
    WHERE status = 'active' 
    GROUP BY city 
    ORDER BY count DESC, city ASC 
    LIMIT 20
");

$levels = $db->fetchAll("
    SELECT level, COUNT(*) as count 
    FROM hospitals 
    WHERE status = 'active' 
    GROUP BY level 
    ORDER BY FIELD(level, '三甲', '三乙', '二甲', '二乙', '一甲', '一乙', '专科')
");

$types = $db->fetchAll("
    SELECT type, COUNT(*) as count 
    FROM hospitals 
    WHERE status = 'active' 
    GROUP BY type 
    ORDER BY count DESC
");

$categories = getCategories(0);

// 获取推荐医院
$featuredHospitals = $db->fetchAll("
    SELECT h.*, 
           (SELECT COUNT(*) FROM doctors d WHERE d.hospital_id = h.id AND d.status = 'active') as doctor_count
    FROM hospitals h 
    WHERE h.status = 'active'
    ORDER BY h.rating DESC, h.level DESC
    LIMIT 6
");

// 添加页面特定的CSS
$pageCSS = ['/assets/css/hospitals.css'];

include '../templates/header.php';
?>

<div class="hospitals-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [['title' => '医院查询', 'url' => '/hospitals/']];
            if ($city) {
                $breadcrumbs[] = ['title' => $city . '医院'];
            }
            if ($level) {
                $breadcrumbs[] = ['title' => $level];
            }
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <!-- 搜索栏 -->
        <div class="hospital-search-section">
            <div class="search-header">
                <h1>医院查询</h1>
                <p>查找全国各地优质医院，获取详细医院信息</p>
            </div>
            
            <form class="hospital-search-form" method="GET">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="搜索医院名称、地址、特色科室..." 
                           value="<?php echo h($keyword); ?>" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        搜索医院
                    </button>
                </div>
                
                <!-- 保持其他筛选条件 -->
                <?php if ($category): ?><input type="hidden" name="category" value="<?php echo h($category); ?>"><?php endif; ?>
                <?php if ($city): ?><input type="hidden" name="city" value="<?php echo h($city); ?>"><?php endif; ?>
                <?php if ($level): ?><input type="hidden" name="level" value="<?php echo h($level); ?>"><?php endif; ?>
                <?php if ($type): ?><input type="hidden" name="type" value="<?php echo h($type); ?>"><?php endif; ?>
            </form>
        </div>
        
        <div class="hospitals-layout">
            <!-- 筛选侧边栏 -->
            <aside class="hospitals-filters">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        筛选条件
                    </h3>
                    <a href="/hospitals/" class="clear-filters">清除筛选</a>
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
                
                <!-- 按城市筛选 -->
                <div class="filter-group">
                    <h4>按城市查找</h4>
                    <div class="filter-options">
                        <?php foreach ($cities as $cityItem): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['city' => $cityItem['city'], 'page' => 1])); ?>" 
                               class="filter-option <?php echo $city == $cityItem['city'] ? 'active' : ''; ?>">
                                <?php echo h($cityItem['city']); ?>
                                <span class="count">(<?php echo $cityItem['count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 按等级筛选 -->
                <div class="filter-group">
                    <h4>按医院等级</h4>
                    <div class="filter-options">
                        <?php foreach ($levels as $levelItem): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['level' => $levelItem['level'], 'page' => 1])); ?>" 
                               class="filter-option <?php echo $level == $levelItem['level'] ? 'active' : ''; ?>">
                                <?php echo h($levelItem['level']); ?>
                                <span class="count">(<?php echo $levelItem['count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 按类型筛选 -->
                <div class="filter-group">
                    <h4>按医院类型</h4>
                    <div class="filter-options">
                        <?php foreach ($types as $typeItem): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => $typeItem['type'], 'page' => 1])); ?>" 
                               class="filter-option <?php echo $type == $typeItem['type'] ? 'active' : ''; ?>">
                                <?php echo h($typeItem['type']); ?>
                                <span class="count">(<?php echo $typeItem['count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 推荐医院 -->
                <div class="filter-group">
                    <h4>推荐医院</h4>
                    <div class="recommended-hospitals">
                        <?php foreach (array_slice($featuredHospitals, 0, 5) as $featured): ?>
                            <div class="recommended-item">
                                <div class="hospital-info">
                                    <h5>
                                        <a href="/hospitals/detail.php?id=<?php echo $featured['id']; ?>">
                                            <?php echo h(truncate($featured['name'], 30)); ?>
                                        </a>
                                    </h5>
                                    <div class="hospital-meta">
                                        <span class="level"><?php echo h($featured['level']); ?></span>
                                        <span class="location"><?php echo h($featured['city']); ?></span>
                                    </div>
                                    <div class="hospital-rating">
                                        <?php
                                        $rating = floatval($featured['rating']);
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating):
                                        ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; endfor; ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            
            <!-- 主要内容区 -->
            <main class="hospitals-main">
                <!-- 搜索结果统计 -->
                <div class="results-header">
                    <div class="results-info">
                        <h2>
                            <?php if ($keyword): ?>
                                搜索"<?php echo h($keyword); ?>"的结果
                            <?php elseif ($city): ?>
                                <?php echo h($city); ?>医院
                            <?php elseif ($level): ?>
                                <?php echo h($level); ?>医院
                            <?php else: ?>
                                全部医院
                            <?php endif; ?>
                        </h2>
                        <p>共找到 <strong><?php echo number_format($totalHospitals); ?></strong> 家医院</p>
                    </div>
                    
                    <div class="results-sort">
                        <select class="sort-select" onchange="location.href=this.value">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'default'])); ?>">默认排序</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'rating'])); ?>" 
                                    <?php echo ($_GET['sort'] ?? '') == 'rating' ? 'selected' : ''; ?>>
                                评分从高到低
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" 
                                    <?php echo ($_GET['sort'] ?? '') == 'name' ? 'selected' : ''; ?>>
                                按名称排序
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if ($hospitals): ?>
                    <!-- 医院列表 -->
                    <div class="hospitals-list">
                        <?php foreach ($hospitals as $hospital): ?>
                            <div class="hospital-card">
                                <div class="hospital-header">
                                    <div class="hospital-basic-info">
                                        <h3 class="hospital-name">
                                            <a href="/hospitals/detail.php?id=<?php echo $hospital['id']; ?>">
                                                <?php echo h($hospital['name']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="hospital-badges">
                                            <span class="level-badge level-<?php echo str_replace(['甲', '乙'], ['a', 'b'], $hospital['level']); ?>">
                                                <?php echo h($hospital['level']); ?>
                                            </span>
                                            <span class="type-badge">
                                                <?php echo h($hospital['type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="hospital-rating">
                                        <?php
                                        $rating = floatval($hospital['rating']);
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating):
                                        ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; endfor; ?>
                                        <span class="rating-score"><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                </div>
                                
                                <div class="hospital-content">
                                    <div class="hospital-info">
                                        <div class="info-row">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo h($hospital['address']); ?></span>
                                        </div>
                                        
                                        <?php if ($hospital['phone']): ?>
                                            <div class="info-row">
                                                <i class="fas fa-phone"></i>
                                                <span><?php echo h($hospital['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="info-row">
                                            <i class="fas fa-user-md"></i>
                                            <span><?php echo $hospital['doctor_count']; ?> 位医生</span>
                                        </div>
                                        
                                        <?php if ($hospital['website']): ?>
                                            <div class="info-row">
                                                <i class="fas fa-globe"></i>
                                                <a href="<?php echo h($hospital['website']); ?>" target="_blank" rel="noopener">
                                                    官方网站
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($hospital['introduction']): ?>
                                        <div class="hospital-description">
                                            <p><?php echo h(truncate(strip_tags($hospital['introduction']), 200)); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($hospital['specialties']): ?>
                                        <div class="hospital-specialties">
                                            <strong>特色科室：</strong>
                                            <?php echo h(truncate($hospital['specialties'], 150)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="hospital-actions">
                                    <a href="/hospitals/detail.php?id=<?php echo $hospital['id']; ?>" 
                                       class="btn btn-primary">
                                        查看详情
                                    </a>
                                    <a href="/doctors/?hospital_id=<?php echo $hospital['id']; ?>" 
                                       class="btn btn-secondary">
                                        查看医生
                                    </a>
                                    <button class="btn btn-outline favorite-btn" 
                                            data-type="hospital" 
                                            data-id="<?php echo $hospital['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                        收藏
                                    </button>
                                    
                                    <?php if ($hospital['latitude'] && $hospital['longitude']): ?>
                                        <button class="btn btn-outline map-btn" 
                                                data-lat="<?php echo $hospital['latitude']; ?>" 
                                                data-lng="<?php echo $hospital['longitude']; ?>"
                                                data-name="<?php echo h($hospital['name']); ?>">
                                            <i class="fas fa-map"></i>
                                            地图
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-section">
                            <?php
                            $paginationParams = $_GET;
                            unset($paginationParams['page']);
                            echo generatePagination($page, $totalPages, '/hospitals/', $paginationParams);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 无结果提示 -->
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h3>没有找到相关医院</h3>
                        <p>
                            <?php if ($keyword): ?>
                                抱歉，没有找到与"<strong><?php echo h($keyword); ?></strong>"相关的医院
                            <?php else: ?>
                                当前筛选条件下没有找到相关医院
                            <?php endif; ?>
                        </p>
                        
                        <div class="no-results-suggestions">
                            <h4>建议您：</h4>
                            <ul>
                                <li>检查输入的关键词是否正确</li>
                                <li>尝试使用更通用的搜索词</li>
                                <li>调整筛选条件</li>
                                <li><a href="/hospitals/">查看全部医院</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<!-- 地图弹窗 -->
<div id="mapModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="mapTitle">医院位置</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="hospitalMap" style="height: 400px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                <div>
                    <i class="fas fa-map-marker-alt" style="font-size: 48px; margin-bottom: 15px; display: block; text-align: center;"></i>
                    <p>地图功能需要集成第三方地图服务</p>
                    <p style="font-size: 12px; margin-top: 10px;">如百度地图、高德地图等</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 地图按钮点击事件
    $('.map-btn').on('click', function() {
        const lat = $(this).data('lat');
        const lng = $(this).data('lng');
        const name = $(this).data('name');
        
        $('#mapTitle').text(name + ' - 位置地图');
        $('#mapModal').fadeIn(300);
        
        // 这里可以集成具体的地图API
    });
    
    // 关闭地图弹窗
    $('.modal-close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#mapModal').fadeOut(300);
        }
    });
    
    // 阻止弹窗内容区域点击关闭
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<?php include '../templates/footer.php'; ?>