<?php
require_once '../includes/init.php';

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$hospital_id = intval($_GET['hospital_id'] ?? 0);
$city = $_GET['city'] ?? '';
$title = $_GET['title'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$disease = trim($_GET['disease'] ?? '');
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = "医生查询 - " . SITE_NAME;
$pageDescription = "查找全国各地优秀医生，包括主任医师、副主任医师等专家，提供医生介绍、擅长领域、出诊时间等详细信息";
$pageKeywords = "医生查询,专家预约,主任医师,副主任医师,医生介绍";

// 构建查询条件
$whereConditions = ["d.status = 'active'", "h.status = 'active'"];
$queryParams = [];

if ($category) {
    $whereConditions[] = "d.category_id = ?";
    $queryParams[] = $category;
}

if ($hospital_id) {
    $whereConditions[] = "d.hospital_id = ?";
    $queryParams[] = $hospital_id;
}

if ($city) {
    $whereConditions[] = "h.city = ?";
    $queryParams[] = $city;
}

if ($title) {
    $whereConditions[] = "d.title = ?";
    $queryParams[] = $title;
}

if ($keyword) {
    $whereConditions[] = "(d.name LIKE ? OR d.specialties LIKE ? OR h.name LIKE ?)";
    $searchKeyword = "%{$keyword}%";
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
}

if ($disease) {
    $whereConditions[] = "d.specialties LIKE ?";
    $queryParams[] = "%{$disease}%";
}

$whereClause = implode(' AND ', $whereConditions);

// 获取医生列表
$offset = ($page - 1) * $pageSize;
$countParams = $queryParams;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$totalDoctors = $db->fetch("
    SELECT COUNT(*) as count 
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE {$whereClause}
", $countParams)['count'];

$doctors = $db->fetchAll("
    SELECT d.*, h.name as hospital_name, h.city as hospital_city, h.level as hospital_level,
           c.name as category_name
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE {$whereClause}
    ORDER BY d.rating DESC, d.title DESC, d.view_count DESC
    LIMIT ? OFFSET ?
", $listParams);

$totalPages = ceil($totalDoctors / $pageSize);

// 获取筛选选项数据
$cities = $db->fetchAll("
    SELECT h.city, COUNT(d.id) as count 
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE d.status = 'active' AND h.status = 'active'
    GROUP BY h.city 
    ORDER BY count DESC, h.city ASC 
    LIMIT 20
");

$titles = $db->fetchAll("
    SELECT title, COUNT(*) as count 
    FROM doctors 
    WHERE status = 'active' 
    GROUP BY title 
    ORDER BY FIELD(title, '主任医师', '副主任医师', '主治医师', '住院医师', '医师')
");

$categories = getCategories(0);

// 获取当前筛选的医院信息
$currentHospital = null;
if ($hospital_id) {
    $currentHospital = $db->fetch("SELECT * FROM hospitals WHERE id = ?", [$hospital_id]);
}

// 获取推荐医生
$featuredDoctors = $db->fetchAll("
    SELECT d.*, h.name as hospital_name, h.city as hospital_city, 
           c.name as category_name
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.status = 'active' AND h.status = 'active'
    ORDER BY d.rating DESC, d.view_count DESC
    LIMIT 8
");

// 添加页面特定的CSS和JS
$pageCSS = ['/assets/css/doctors.css', '/assets/css/doctor-buttons-fix.css'];
$pageJS = ['/assets/js/favorites.js'];

include '../templates/header.php';
?>

<div class="doctors-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [['title' => '医生查询', 'url' => '/doctors/']];
            if ($currentHospital) {
                $breadcrumbs[] = ['title' => $currentHospital['name'] . '医生'];
            } elseif ($city) {
                $breadcrumbs[] = ['title' => $city . '医生'];
            }
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
        <!-- 搜索栏 -->
        <div class="doctor-search-section">
            <div class="search-header">
                <h1>医生查询</h1>
                <p>查找全国各地优秀医生，在线预约挂号</p>
            </div>
            
            <form class="doctor-search-form" method="GET">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="搜索医生姓名、医院名称、擅长疾病..." 
                           value="<?php echo h($keyword); ?>" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        搜索医生
                    </button>
                </div>
                
                <!-- 保持其他筛选条件 -->
                <?php if ($category): ?><input type="hidden" name="category" value="<?php echo h($category); ?>"><?php endif; ?>
                <?php if ($hospital_id): ?><input type="hidden" name="hospital_id" value="<?php echo $hospital_id; ?>"><?php endif; ?>
                <?php if ($city): ?><input type="hidden" name="city" value="<?php echo h($city); ?>"><?php endif; ?>
                <?php if ($title): ?><input type="hidden" name="title" value="<?php echo h($title); ?>"><?php endif; ?>
                <?php if ($disease): ?><input type="hidden" name="disease" value="<?php echo h($disease); ?>"><?php endif; ?>
            </form>
        </div>
        
        <div class="doctors-layout">
            <!-- 筛选侧边栏 -->
            <aside class="doctors-filters">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        筛选条件
                    </h3>
                    <a href="/doctors/" class="clear-filters">清除筛选</a>
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
                
                <!-- 按职称筛选 -->
                <div class="filter-group">
                    <h4>按医生职称</h4>
                    <div class="filter-options">
                        <?php foreach ($titles as $titleItem): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['title' => $titleItem['title'], 'page' => 1])); ?>" 
                               class="filter-option <?php echo $title == $titleItem['title'] ? 'active' : ''; ?>">
                                <?php echo h($titleItem['title']); ?>
                                <span class="count">(<?php echo $titleItem['count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 推荐医生 -->
                <div class="filter-group">
                    <h4>推荐医生</h4>
                    <div class="recommended-doctors">
                        <?php foreach (array_slice($featuredDoctors, 0, 5) as $featured): ?>
                            <div class="recommended-item">
                                <div class="doctor-avatar">
                                    <?php if ($featured['avatar']): ?>
                                        <img src="<?php echo h($featured['avatar']); ?>" 
                                             alt="<?php echo h($featured['name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="doctor-info">
                                    <h5>
                                        <a href="/doctors/detail.php?id=<?php echo $featured['id']; ?>">
                                            <?php echo h($featured['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="doctor-meta">
                                        <span class="title"><?php echo h($featured['title']); ?></span>
                                        <span class="hospital"><?php echo h(truncate($featured['hospital_name'], 15)); ?></span>
                                    </div>
                                    <div class="doctor-rating">
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
            <main class="doctors-main">
                <!-- 搜索结果统计 -->
                <div class="results-header">
                    <div class="results-info">
                        <h2>
                            <?php if ($currentHospital): ?>
                                <?php echo h($currentHospital['name']); ?>医生
                            <?php elseif ($keyword): ?>
                                搜索"<?php echo h($keyword); ?>"的结果
                            <?php elseif ($city): ?>
                                <?php echo h($city); ?>医生
                            <?php elseif ($title): ?>
                                <?php echo h($title); ?>
                            <?php else: ?>
                                全部医生
                            <?php endif; ?>
                        </h2>
                        <p>共找到 <strong><?php echo number_format($totalDoctors); ?></strong> 位医生</p>
                    </div>
                    
                    <div class="results-sort">
                        <select class="sort-select" onchange="location.href=this.value">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'default'])); ?>">默认排序</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'rating'])); ?>" 
                                    <?php echo ($_GET['sort'] ?? '') == 'rating' ? 'selected' : ''; ?>>
                                评分从高到低
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'views'])); ?>" 
                                    <?php echo ($_GET['sort'] ?? '') == 'views' ? 'selected' : ''; ?>>
                                查看次数最多
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if ($doctors): ?>
                    <!-- 医生列表 -->
                    <div class="doctors-list">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="doctor-card">
                                <div class="doctor-avatar">
                                    <?php if ($doctor['avatar']): ?>
                                        <img src="<?php echo h($doctor['avatar']); ?>" 
                                             alt="<?php echo h($doctor['name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- 在线状态 -->
                                    <div class="online-status" title="在线咨询">
                                        <i class="fas fa-circle"></i>
                                    </div>
                                </div>
                                
                                <div class="doctor-content">
                                    <div class="doctor-header">
                                        <h3 class="doctor-name">
                                            <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                                                <?php echo h($doctor['name']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="doctor-badges">
                                            <span class="title-badge title-<?php echo str_replace(['主任', '副主任', '主治', '住院', '医师'], ['zr', 'fzr', 'zz', 'zy', 'ys'], $doctor['title']); ?>">
                                                <?php echo h($doctor['title']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="doctor-rating">
                                            <?php
                                            $rating = floatval($doctor['rating']);
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
                                    
                                    <div class="doctor-info">
                                        <div class="info-row">
                                            <i class="fas fa-hospital"></i>
                                            <span>
                                                <a href="/hospitals/detail.php?id=<?php echo $doctor['hospital_id']; ?>">
                                                    <?php echo h($doctor['hospital_name']); ?>
                                                </a>
                                            </span>
                                        </div>
                                        
                                        <div class="info-row">
                                            <i class="fas fa-stethoscope"></i>
                                            <span><?php echo h($doctor['category_name']); ?></span>
                                        </div>
                                        
                                        <div class="info-row">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo h($doctor['hospital_city']); ?></span>
                                        </div>
                                        
                                        <?php if ($doctor['education']): ?>
                                            <div class="info-row">
                                                <i class="fas fa-graduation-cap"></i>
                                                <span><?php echo h($doctor['education']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($doctor['specialties']): ?>
                                        <div class="doctor-specialties">
                                            <strong>擅长：</strong>
                                            <?php echo h(truncate($doctor['specialties'], 120)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($doctor['experience']): ?>
                                        <div class="doctor-experience">
                                            <strong>经验：</strong>
                                            <?php echo h(truncate(strip_tags($doctor['experience']), 100)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="doctor-stats">
                                        <span class="views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($doctor['view_count']); ?>次查看
                                        </span>
                                        
                                        <?php if ($doctor['consultation_fee']): ?>
                                            <span class="fee">
                                                <i class="fas fa-money-bill"></i>
                                                ¥<?php echo number_format($doctor['consultation_fee'], 0); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="doctor-actions">
                                    <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-user"></i>
                                        查看详情
                                    </a>
                                    <a href="/appointment/book.php?doctor_id=<?php echo $doctor['id']; ?>" 
                                       class="btn btn-secondary">
                                        <i class="fas fa-calendar-check"></i>
                                        立即预约
                                    </a>
                                    <button class="btn btn-outline favorite-btn" 
                                            data-type="doctor" 
                                            data-id="<?php echo $doctor['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                        收藏
                                    </button>
                                    
                                    <!-- 在线咨询按钮 -->
                                    <button class="btn btn-outline consultation-btn" 
                                            data-doctor-id="<?php echo $doctor['id']; ?>"
                                            data-doctor-name="<?php echo h($doctor['name']); ?>">
                                        <i class="fas fa-comments"></i>
                                        在线咨询
                                    </button>
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
                            echo generatePagination($page, $totalPages, '/doctors/', $paginationParams);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 无结果提示 -->
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3>没有找到相关医生</h3>
                        <p>
                            <?php if ($keyword): ?>
                                抱歉，没有找到与"<strong><?php echo h($keyword); ?></strong>"相关的医生
                            <?php else: ?>
                                当前筛选条件下没有找到相关医生
                            <?php endif; ?>
                        </p>
                        
                        <div class="no-results-suggestions">
                            <h4>建议您：</h4>
                            <ul>
                                <li>检查输入的关键词是否正确</li>
                                <li>尝试使用更通用的搜索词</li>
                                <li>调整筛选条件</li>
                                <li><a href="/doctors/">查看全部医生</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<!-- 在线咨询弹窗 -->
<div id="consultationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="consultationTitle">在线咨询</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="consultation-content">
                <div class="doctor-consultation-info">
                    <div class="consultation-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="consultation-details">
                        <h4 id="consultationDoctorName">医生姓名</h4>
                        <p>在线咨询服务</p>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form class="consultation-form" data-ajax>
                        <input type="hidden" id="consultationDoctorId" name="doctor_id">
                        
                        <div class="form-group">
                            <label>咨询内容：</label>
                            <textarea name="content" placeholder="请详细描述您的问题..." rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_anonymous" value="1">
                                匿名咨询
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            发送咨询
                        </button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>
                            <i class="fas fa-sign-in-alt"></i>
                            <a href="/user/login.php">登录</a> 后才能使用在线咨询服务
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 调试：检查按钮点击事件
    $('.doctor-actions a').on('click', function(e) {
        console.log('Button clicked:', $(this).attr('href'));
        // 不阻止默认行为，让链接正常工作
    });
    
    // 确保按钮可点击
    $('.doctor-actions .btn').css({
        'pointer-events': 'auto',
        'position': 'relative',
        'z-index': '10'
    });
    
    // 在线咨询弹窗
    $('.consultation-btn').on('click', function() {
        const doctorId = $(this).data('doctor-id');
        const doctorName = $(this).data('doctor-name');
        
        $('#consultationDoctorId').val(doctorId);
        $('#consultationDoctorName').text(doctorName);
        $('#consultationTitle').text('咨询 ' + doctorName);
        
        $('#consultationModal').fadeIn(300);
    });
    
    // 关闭咨询弹窗
    $('.modal-close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#consultationModal').fadeOut(300);
        }
    });
    
    // 阻止弹窗内容区域点击关闭
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // 咨询表单提交
    $('.consultation-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        // 禁用提交按钮
        $submitBtn.prop('disabled', true).text('发送中...');
        
        // 获取表单数据
        const formData = {
            doctor_id: $('#consultationDoctorId').val(),
            content: $form.find('textarea[name="content"]').val(),
            is_anonymous: $form.find('input[name="is_anonymous"]').is(':checked') ? 1 : 0
        };
        
        // 提交到API
        $.ajax({
            url: '/api/consultations.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    $('#consultationModal').fadeOut(300);
                    $form[0].reset();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    showMessage('请先登录', 'error');
                    setTimeout(() => {
                        window.location.href = '/user/login.php';
                    }, 1500);
                } else {
                    showMessage('发送失败，请稍后重试', 'error');
                }
            },
            complete: function() {
                // 恢复提交按钮
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<?php include '../templates/footer.php'; ?>