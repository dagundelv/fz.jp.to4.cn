<?php
require_once 'includes/init.php';

// 获取搜索参数
$query = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = $query ? "搜索：{$query} - " . SITE_NAME : "搜索 - " . SITE_NAME;
$pageDescription = "在" . SITE_NAME . "搜索医院、医生、疾病、健康资讯等信息";
$pageKeywords = "搜索,医院搜索,医生搜索,疾病搜索,健康搜索";

// 记录搜索关键词
if ($query) {
    recordSearchKeyword($query, $category);
}

// 获取筛选参数
$filters = [
    'level' => $_GET['level'] ?? '',
    'city' => $_GET['city'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'hospital_id' => $_GET['hospital_id'] ?? '',
    'title' => $_GET['title'] ?? '',
    'sort' => $_GET['sort'] ?? 'relevance'
];

// 执行搜索
$searchResults = [];
$totalResults = 0;

if ($query) {
    // 使用智能搜索引擎
    require_once 'includes/SearchEngine.php';
    $searchEngine = new SearchEngine();
    
    $searchResults = $searchEngine->search($query, [
        'category' => $category,
        'page' => $page,
        'limit' => $pageSize,
        'sort' => $filters['sort'],
        'filters' => $filters
    ]);
    
    $totalResults = $searchResults['total'] ?? 0;
}

// 添加页面特定的CSS和JS
$pageCSS = ['/assets/css/search.css'];
$pageJS = ['/assets/js/search-enhanced.js'];

include 'templates/header.php';

// 搜索函数
function performSearch($query, $category, $page, $pageSize) {
    global $db;
    
    $offset = ($page - 1) * $pageSize;
    $results = [
        'hospitals' => [],
        'doctors' => [],
        'diseases' => [],
        'articles' => [],
        'total' => 0
    ];
    
    $searchQuery = "%{$query}%";
    
    // 搜索医院
    if (!$category || $category === 'hospitals') {
        $hospitalSql = "
            SELECT h.*, COUNT(*) OVER() as total_count
            FROM hospitals h 
            WHERE h.status = 'active' 
            AND (h.name LIKE ? OR h.address LIKE ? OR h.specialties LIKE ?)
            ORDER BY h.name LIKE ? DESC, h.rating DESC
            LIMIT ? OFFSET ?
        ";
        
        $hospitals = $db->fetchAll($hospitalSql, [
            $searchQuery, $searchQuery, $searchQuery, 
            "%{$query}%", $pageSize, $offset
        ]);
        
        $results['hospitals'] = $hospitals;
        if ($hospitals) {
            $results['total'] += $hospitals[0]['total_count'];
        }
    }
    
    // 搜索医生
    if (!$category || $category === 'doctors') {
        $doctorSql = "
            SELECT d.*, h.name as hospital_name, c.name as category_name,
                   COUNT(*) OVER() as total_count
            FROM doctors d 
            LEFT JOIN hospitals h ON d.hospital_id = h.id
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.status = 'active' AND h.status = 'active'
            AND (d.name LIKE ? OR d.specialties LIKE ? OR h.name LIKE ?)
            ORDER BY d.name LIKE ? DESC, d.rating DESC
            LIMIT ? OFFSET ?
        ";
        
        $doctors = $db->fetchAll($doctorSql, [
            $searchQuery, $searchQuery, $searchQuery,
            "%{$query}%", $pageSize, $offset
        ]);
        
        $results['doctors'] = $doctors;
        if ($doctors) {
            $results['total'] += $doctors[0]['total_count'];
        }
    }
    
    // 搜索疾病
    if (!$category || $category === 'diseases') {
        $diseaseSql = "
            SELECT d.*, c.name as category_name,
                   COUNT(*) OVER() as total_count
            FROM diseases d 
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.status = 'active'
            AND (d.name LIKE ? OR d.alias LIKE ? OR d.symptoms LIKE ? OR d.causes LIKE ?)
            ORDER BY d.name LIKE ? DESC, d.view_count DESC
            LIMIT ? OFFSET ?
        ";
        
        $diseases = $db->fetchAll($diseaseSql, [
            $searchQuery, $searchQuery, $searchQuery, $searchQuery,
            "%{$query}%", $pageSize, $offset
        ]);
        
        $results['diseases'] = $diseases;
        if ($diseases) {
            $results['total'] += $diseases[0]['total_count'];
        }
    }
    
    // 搜索文章
    if (!$category || $category === 'articles') {
        $articleSql = "
            SELECT a.*, c.name as category_name,
                   COUNT(*) OVER() as total_count
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            AND (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)
            ORDER BY a.title LIKE ? DESC, a.publish_time DESC
            LIMIT ? OFFSET ?
        ";
        
        $articles = $db->fetchAll($articleSql, [
            $searchQuery, $searchQuery, $searchQuery,
            "%{$query}%", $pageSize, $offset
        ]);
        
        $results['articles'] = $articles;
        if ($articles) {
            $results['total'] += $articles[0]['total_count'];
        }
    }
    
    return $results;
}
?>

<div class="search-page">
    <div class="container">
        <!-- 搜索框 -->
        <div class="search-header">
            <div class="search-box-large">
                <form action="/search.php" method="GET" class="search-form">
                    <div class="search-input-wrapper">
                        <input type="text" name="q" placeholder="搜索医院、医生、疾病..." 
                               value="<?php echo h($query); ?>" 
                               class="search-input" id="searchInput" autocomplete="off">
                        <div class="search-suggestions" id="searchSuggestions"></div>
                    </div>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        搜索
                    </button>
                </form>
            </div>
            
            <?php if ($query): ?>
                <div class="search-info">
                    <p>搜索"<strong><?php echo h($query); ?></strong>"的结果，共找到 <strong><?php echo number_format($totalResults); ?></strong> 条相关信息</p>
                    <?php if (empty($query)): ?>
                        <p style="color: red;">搜索查询为空</p>
                    <?php elseif ($totalResults === 0): ?>
                        <p style="color: orange;">没有找到匹配的结果</p>
                    <?php else: ?>
                        <p style="color: green;">找到结果：<?php echo count($searchResults['hospitals'] ?? []) + count($searchResults['doctors'] ?? []) + count($searchResults['diseases'] ?? []) + count($searchResults['articles'] ?? []) + count($searchResults['questions'] ?? []); ?> 项</p>
                        <!-- 调试信息 -->
                        <small style="color: gray;">
                            医院: <?php echo count($searchResults['hospitals'] ?? []); ?>, 
                            医生: <?php echo count($searchResults['doctors'] ?? []); ?>, 
                            疾病: <?php echo count($searchResults['diseases'] ?? []); ?>, 
                            文章: <?php echo count($searchResults['articles'] ?? []); ?>,
                            问答: <?php echo count($searchResults['questions'] ?? []); ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php elseif (isset($_GET['q'])): ?>
                <div class="search-info">
                    <p style="color: red;">请输入搜索关键词</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($query && $totalResults > 0): ?>
            <!-- 高级筛选 -->
            <div class="advanced-filters">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> 筛选条件</h3>
                    <button class="toggle-filters" id="toggleFilters">
                        <span>展开筛选</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="filter-content" id="filterContent" style="display: none;">
                    <form method="GET" action="/search.php" class="filter-form">
                        <input type="hidden" name="q" value="<?php echo h($query); ?>">
                        <?php if ($category): ?>
                            <input type="hidden" name="category" value="<?php echo h($category); ?>">
                        <?php endif; ?>
                        
                        <div class="filter-grid">
                            <!-- 排序方式 -->
                            <div class="filter-group">
                                <label>排序方式</label>
                                <select name="sort" class="filter-select">
                                    <option value="relevance" <?php echo $filters['sort'] === 'relevance' ? 'selected' : ''; ?>>相关性</option>
                                    <option value="rating" <?php echo $filters['sort'] === 'rating' ? 'selected' : ''; ?>>评分</option>
                                    <option value="time" <?php echo $filters['sort'] === 'time' ? 'selected' : ''; ?>>时间</option>
                                </select>
                            </div>
                            
                            <?php if (!$category || $category === 'hospitals'): ?>
                                <!-- 医院等级 -->
                                <div class="filter-group">
                                    <label>医院等级</label>
                                    <select name="level" class="filter-select">
                                        <option value="">不限</option>
                                        <option value="三甲" <?php echo $filters['level'] === '三甲' ? 'selected' : ''; ?>>三甲医院</option>
                                        <option value="三乙" <?php echo $filters['level'] === '三乙' ? 'selected' : ''; ?>>三乙医院</option>
                                        <option value="二甲" <?php echo $filters['level'] === '二甲' ? 'selected' : ''; ?>>二甲医院</option>
                                    </select>
                                </div>
                                
                                <!-- 城市 -->
                                <div class="filter-group">
                                    <label>城市</label>
                                    <select name="city" class="filter-select">
                                        <option value="">不限</option>
                                        <option value="北京" <?php echo $filters['city'] === '北京' ? 'selected' : ''; ?>>北京</option>
                                        <option value="上海" <?php echo $filters['city'] === '上海' ? 'selected' : ''; ?>>上海</option>
                                        <option value="广州" <?php echo $filters['city'] === '广州' ? 'selected' : ''; ?>>广州</option>
                                        <option value="深圳" <?php echo $filters['city'] === '深圳' ? 'selected' : ''; ?>>深圳</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$category || $category === 'doctors'): ?>
                                <!-- 医生职称 -->
                                <div class="filter-group">
                                    <label>医生职称</label>
                                    <select name="title" class="filter-select">
                                        <option value="">不限</option>
                                        <option value="主任医师" <?php echo $filters['title'] === '主任医师' ? 'selected' : ''; ?>>主任医师</option>
                                        <option value="副主任医师" <?php echo $filters['title'] === '副主任医师' ? 'selected' : ''; ?>>副主任医师</option>
                                        <option value="主治医师" <?php echo $filters['title'] === '主治医师' ? 'selected' : ''; ?>>主治医师</option>
                                    </select>
                                </div>
                                
                                <!-- 科室 -->
                                <div class="filter-group">
                                    <label>科室</label>
                                    <select name="category_id" class="filter-select">
                                        <option value="">不限</option>
                                        <?php 
                                        try {
                                            $categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order, name");
                                            foreach ($categories as $cat):
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo h($cat['name']); ?>
                                            </option>
                                        <?php 
                                            endforeach;
                                        } catch (Exception $e) {
                                            // 如果查询失败，显示错误但不中断页面
                                            echo '<option value="">数据加载失败</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 应用筛选
                            </button>
                            <a href="/search.php?q=<?php echo urlencode($query); ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" class="btn btn-outline">
                                <i class="fas fa-undo"></i> 重置
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 搜索结果筛选 -->
            <div class="search-filters">
                <div class="filter-tabs">
                    <a href="/search.php?q=<?php echo urlencode($query); ?><?php echo http_build_query(array_filter($filters)) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
                       class="filter-tab <?php echo !$category ? 'active' : ''; ?>">
                        全部 (<?php echo number_format($totalResults); ?>)
                    </a>
                    
                    <?php if (!empty($searchResults['hospitals'])): ?>
                        <a href="/search.php?q=<?php echo urlencode($query); ?>&category=hospitals" 
                           class="filter-tab <?php echo $category === 'hospitals' ? 'active' : ''; ?>">
                            医院 (<?php echo count($searchResults['hospitals']); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($searchResults['doctors'])): ?>
                        <a href="/search.php?q=<?php echo urlencode($query); ?>&category=doctors" 
                           class="filter-tab <?php echo $category === 'doctors' ? 'active' : ''; ?>">
                            医生 (<?php echo count($searchResults['doctors']); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($searchResults['diseases'])): ?>
                        <a href="/search.php?q=<?php echo urlencode($query); ?>&category=diseases" 
                           class="filter-tab <?php echo $category === 'diseases' ? 'active' : ''; ?>">
                            疾病 (<?php echo count($searchResults['diseases']); ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($searchResults['articles'])): ?>
                        <a href="/search.php?q=<?php echo urlencode($query); ?>&category=articles" 
                           class="filter-tab <?php echo $category === 'articles' ? 'active' : ''; ?>">
                            资讯 (<?php echo count($searchResults['articles']); ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 搜索结果 -->
            <div class="search-results">
                <!-- 医院结果 -->
                <?php if (!empty($searchResults['hospitals']) && (!$category || $category === 'hospitals')): ?>
                    <div class="result-section">
                        <h3 class="section-title">
                            <i class="fas fa-hospital"></i>
                            医院
                        </h3>
                        
                        <?php foreach ($searchResults['hospitals'] as $hospital): ?>
                            <div class="search-result-item hospital-item">
                                <div class="result-content">
                                    <h4>
                                        <a href="/hospitals/detail.php?id=<?php echo $hospital['id']; ?>" class="result-title">
                                            <?php echo h($hospital['name']); ?>
                                        </a>
                                        <span class="hospital-level"><?php echo h($hospital['level']); ?></span>
                                    </h4>
                                    
                                    <div class="result-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo h($hospital['address']); ?></span>
                                        <span><i class="fas fa-phone"></i> <?php echo h($hospital['phone'] ?: '暂无电话'); ?></span>
                                    </div>
                                    
                                    <?php if ($hospital['introduction']): ?>
                                        <p class="result-description">
                                            <?php echo h(truncate(strip_tags($hospital['introduction']), 150)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="result-actions">
                                        <a href="/hospitals/detail.php?id=<?php echo $hospital['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                                        <a href="/doctors/?hospital_id=<?php echo $hospital['id']; ?>" class="btn btn-secondary btn-sm">查看医生</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 医生结果 -->
                <?php if (!empty($searchResults['doctors']) && (!$category || $category === 'doctors')): ?>
                    <div class="result-section">
                        <h3 class="section-title">
                            <i class="fas fa-user-md"></i>
                            医生
                        </h3>
                        
                        <?php foreach ($searchResults['doctors'] as $doctor): ?>
                            <div class="search-result-item doctor-item">
                                <div class="doctor-avatar">
                                    <?php if ($doctor['avatar']): ?>
                                        <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="result-content">
                                    <h4>
                                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" class="result-title">
                                            <?php echo h($doctor['name']); ?>
                                        </a>
                                        <span class="doctor-title"><?php echo h($doctor['title']); ?></span>
                                    </h4>
                                    
                                    <div class="result-meta">
                                        <span><i class="fas fa-hospital"></i> <?php echo h($doctor['hospital_name']); ?></span>
                                        <span><i class="fas fa-stethoscope"></i> <?php echo h($doctor['category_name']); ?></span>
                                        <?php if ($doctor['rating'] > 0): ?>
                                            <span class="rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo number_format($doctor['rating'], 1); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($doctor['specialties']): ?>
                                        <p class="result-description">
                                            擅长：<?php echo h(truncate($doctor['specialties'], 120)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="result-actions">
                                        <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                                        <a href="/user/appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-secondary btn-sm">立即预约</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 疾病结果 -->
                <?php if (!empty($searchResults['diseases']) && (!$category || $category === 'diseases')): ?>
                    <div class="result-section">
                        <h3 class="section-title">
                            <i class="fas fa-book-medical"></i>
                            疾病
                        </h3>
                        
                        <?php foreach ($searchResults['diseases'] as $disease): ?>
                            <div class="search-result-item disease-item">
                                <div class="result-content">
                                    <h4>
                                        <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>" class="result-title">
                                            <?php echo h($disease['name']); ?>
                                        </a>
                                        <span class="disease-category"><?php echo h($disease['category_name']); ?></span>
                                    </h4>
                                    
                                    <?php if ($disease['symptoms']): ?>
                                        <p class="result-description">
                                            <strong>症状：</strong><?php echo h(truncate(strip_tags($disease['symptoms']), 120)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="result-meta">
                                        <span><i class="fas fa-eye"></i> <?php echo number_format($disease['view_count']); ?>次查看</span>
                                    </div>
                                    
                                    <div class="result-actions">
                                        <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                                        <a href="/doctors/?disease=<?php echo urlencode($disease['name']); ?>" class="btn btn-secondary btn-sm">找医生</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 文章结果 -->
                <?php if (!empty($searchResults['articles']) && (!$category || $category === 'articles')): ?>
                    <div class="result-section">
                        <h3 class="section-title">
                            <i class="fas fa-newspaper"></i>
                            健康资讯
                        </h3>
                        
                        <?php foreach ($searchResults['articles'] as $article): ?>
                            <div class="search-result-item article-item">
                                <?php if ($article['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo h($article['featured_image']); ?>" alt="<?php echo h($article['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="result-content">
                                    <h4>
                                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>" class="result-title">
                                            <?php echo h($article['title']); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if ($article['summary']): ?>
                                        <p class="result-description">
                                            <?php echo h(truncate($article['summary'], 150)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="result-meta">
                                        <?php if ($article['category_name']): ?>
                                            <span><i class="fas fa-tag"></i> <?php echo h($article['category_name']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-clock"></i> <?php echo formatTime($article['publish_time']); ?></span>
                                        <span><i class="fas fa-eye"></i> <?php echo number_format($article['view_count']); ?>次阅读</span>
                                    </div>
                                    
                                    <div class="result-actions">
                                        <a href="/news/detail.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm">阅读全文</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalResults > $pageSize): ?>
                <?php
                $totalPages = ceil($totalResults / $pageSize);
                $currentParams = $_GET;
                unset($currentParams['page']);
                echo generatePagination($page, $totalPages, '/search.php', $currentParams);
                ?>
            <?php endif; ?>
            
        <?php elseif ($query && $totalResults === 0): ?>
            <!-- 无搜索结果 -->
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>没有找到相关结果</h3>
                <p>抱歉，没有找到与"<strong><?php echo h($query); ?></strong>"相关的内容</p>
                
                <div class="search-suggestions-tips">
                    <h4>搜索建议：</h4>
                    <ul>
                        <li>检查关键词是否拼写正确</li>
                        <li>尝试使用更常见的关键词</li>
                        <li>减少关键词数量</li>
                        <li>尝试搜索疾病症状而不是具体疾病名</li>
                    </ul>
                </div>
                
                <!-- 热门搜索推荐 -->
                <div class="popular-searches">
                    <h4>热门搜索：</h4>
                    <div class="search-tags">
                        <?php
                        $popularSearches = getHotSearchKeywords(8);
                        foreach ($popularSearches as $search):
                        ?>
                            <a href="/search.php?q=<?php echo urlencode($search['keyword']); ?>" class="search-tag">
                                <?php echo h($search['keyword']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- 搜索提示页面 -->
            <div class="search-tips">
                <div class="tips-content">
                    <h3>搜索小贴士</h3>
                    <div class="tips-grid">
                        <div class="tip-item">
                            <i class="fas fa-hospital"></i>
                            <h4>查找医院</h4>
                            <p>输入医院名称、地区或医院等级</p>
                            <span class="example">如：北京协和医院、上海三甲医院</span>
                        </div>
                        
                        <div class="tip-item">
                            <i class="fas fa-user-md"></i>
                            <h4>查找医生</h4>
                            <p>输入医生姓名、科室或专长</p>
                            <span class="example">如：心内科专家、张医生</span>
                        </div>
                        
                        <div class="tip-item">
                            <i class="fas fa-book-medical"></i>
                            <h4>查找疾病</h4>
                            <p>输入疾病名称或症状描述</p>
                            <span class="example">如：高血压、头痛</span>
                        </div>
                        
                        <div class="tip-item">
                            <i class="fas fa-newspaper"></i>
                            <h4>查找资讯</h4>
                            <p>输入健康话题或医疗新闻关键词</p>
                            <span class="example">如：疫苗接种、健康饮食</span>
                        </div>
                    </div>
                </div>
                
                <!-- 热门搜索 -->
                <div class="popular-searches">
                    <h4>热门搜索：</h4>
                    <div class="search-tags">
                        <?php
                        $popularSearches = getHotSearchKeywords(12);
                        foreach ($popularSearches as $search):
                        ?>
                            <a href="/search.php?q=<?php echo urlencode($search['keyword']); ?>" class="search-tag">
                                <?php echo h($search['keyword']); ?>
                                <span class="search-count"><?php echo number_format($search['search_count']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 筛选功能
document.addEventListener('DOMContentLoaded', function() {
    const toggleFilters = document.getElementById('toggleFilters');
    const filterContent = document.getElementById('filterContent');
    
    if (toggleFilters && filterContent) {
        toggleFilters.addEventListener('click', function() {
            const isVisible = filterContent.style.display !== 'none';
            
            if (isVisible) {
                filterContent.style.display = 'none';
                toggleFilters.querySelector('span').textContent = '展开筛选';
                toggleFilters.querySelector('i').className = 'fas fa-chevron-down';
            } else {
                filterContent.style.display = 'block';
                toggleFilters.querySelector('span').textContent = '收起筛选';
                toggleFilters.querySelector('i').className = 'fas fa-chevron-up';
            }
        });
        
        // 如果有筛选条件，自动展开
        const hasFilters = <?php echo json_encode(array_filter($filters)); ?>;
        if (Object.keys(hasFilters).length > 1) { // 除了sort之外还有其他筛选
            toggleFilters.click();
        }
    }
    
    // 筛选表单自动提交
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // 可选：自动提交表单
            // this.form.submit();
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?>