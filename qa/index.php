<?php
require_once '../includes/init.php';

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$keyword = trim($_GET['keyword'] ?? '');
$pageSize = PAGE_SIZE;

// 设置页面信息
$pageTitle = "健康问答 - " . SITE_NAME;
$pageDescription = "健康问答社区，专业医生在线解答各种健康问题，分享医学知识和健康经验";
$pageKeywords = "健康问答,在线咨询,医学问答,健康社区,专家解答";
$currentPage = 'qa';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($category) {
    $whereConditions[] = "q.category_id = ?";
    $queryParams[] = $category;
}

if ($status) {
    switch ($status) {
        case 'answered':
            $whereConditions[] = "q.status = 'answered'";
            break;
        case 'unanswered':
            $whereConditions[] = "q.status = 'pending'";
            break;
        case 'expert':
            $whereConditions[] = "EXISTS (SELECT 1 FROM qa_answers a LEFT JOIN doctors d ON a.doctor_id = d.id WHERE a.question_id = q.id AND d.id IS NOT NULL)";
            break;
    }
}

if ($keyword) {
    $whereConditions[] = "(q.title LIKE ? OR q.content LIKE ?)";
    $searchKeyword = "%{$keyword}%";
    $queryParams[] = $searchKeyword;
    $queryParams[] = $searchKeyword;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 设置排序
$orderBy = "q.created_at DESC";
switch ($sort) {
    case 'hot':
        $orderBy = "q.view_count DESC, q.created_at DESC";
        break;
    case 'answers':
        $orderBy = "q.answer_count DESC, q.created_at DESC";
        break;
    case 'latest':
    default:
        $orderBy = "q.created_at DESC";
}

// 获取问题列表
$offset = ($page - 1) * $pageSize;
$countParams = $queryParams;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$totalQuestions = $db->fetch("
    SELECT COUNT(*) as count 
    FROM qa_questions q 
    {$whereClause}
", $countParams)['count'];

$questions = $db->fetchAll("
    SELECT q.*, c.name as category_name, u.username, u.avatar as user_avatar,
           (SELECT COUNT(*) FROM qa_answers a WHERE a.question_id = q.id) as answer_count,
           (SELECT COUNT(*) FROM qa_answers a LEFT JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.question_id = q.id AND d.id IS NOT NULL) as expert_answer_count
    FROM qa_questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    LEFT JOIN users u ON q.user_id = u.id
    {$whereClause}
    ORDER BY {$orderBy}
    LIMIT ? OFFSET ?
", $listParams);

$totalPages = ceil($totalQuestions / $pageSize);

// 获取分类数据
$categories = getCategories(0);

// 获取热门问题
$hotQuestions = $db->fetchAll("
    SELECT q.*, c.name as category_name
    FROM qa_questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    ORDER BY q.view_count DESC, q.answer_count DESC
    LIMIT 8
");

// 获取最新回答
$recentAnswers = $db->fetchAll("
    SELECT a.*, q.title as question_title, q.id as question_id,
           d.name as doctor_name, d.title as doctor_title, d.avatar as doctor_avatar,
           u.username, u.avatar as user_avatar
    FROM qa_answers a 
    LEFT JOIN qa_questions q ON a.question_id = q.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
");

// 统计数据
$stats = [
    'total_questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions")['count'],
    'total_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers")['count'],
    'expert_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers WHERE doctor_id IS NOT NULL")['count'],
    'today_questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions WHERE DATE(created_at) = CURDATE()")['count']
];

// 添加页面特定的CSS
$pageCSS = ['/assets/css/qa.css'];

include '../templates/header.php';
?>

<div class="qa-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [['title' => '健康问答', 'url' => '/qa/']];
            if ($category) {
                $categoryInfo = getCategoryById($category);
                if ($categoryInfo) {
                    $breadcrumbs[] = ['title' => $categoryInfo['name'] . '问答'];
                }
            }
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <!-- 头部统计和操作区 -->
        <div class="qa-header">
            <div class="qa-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['total_questions']); ?></span>
                    <span class="stat-label">个问题</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['total_answers']); ?></span>
                    <span class="stat-label">个回答</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['expert_answers']); ?></span>
                    <span class="stat-label">专家回答</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['today_questions']); ?></span>
                    <span class="stat-label">今日新问题</span>
                </div>
            </div>
            
            <div class="qa-actions">
                <a href="/qa/ask.php" class="btn btn-primary ask-btn">
                    <i class="fas fa-plus"></i>
                    我要提问
                </a>
            </div>
        </div>
        
        <!-- 搜索区域 -->
        <div class="qa-search-section">
            <form class="qa-search-form" method="GET">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="搜索健康问题..." 
                           value="<?php echo h($keyword); ?>" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        搜索问题
                    </button>
                </div>
                
                <!-- 保持筛选条件 -->
                <?php if ($category): ?><input type="hidden" name="category" value="<?php echo h($category); ?>"><?php endif; ?>
                <?php if ($status): ?><input type="hidden" name="status" value="<?php echo h($status); ?>"><?php endif; ?>
                <?php if ($sort): ?><input type="hidden" name="sort" value="<?php echo h($sort); ?>"><?php endif; ?>
            </form>
            
            <!-- 快速导航 -->
            <div class="quick-nav">
                <div class="nav-title">热门分类：</div>
                <div class="nav-links">
                    <a href="/qa/?category=1">内科问答</a>
                    <a href="/qa/?category=2">外科问答</a>
                    <a href="/qa/?category=3">妇科问答</a>
                    <a href="/qa/?category=4">儿科问答</a>
                    <a href="/qa/?status=expert">专家解答</a>
                    <a href="/qa/?status=unanswered">待解答</a>
                </div>
            </div>
        </div>
        
        <div class="qa-layout">
            <!-- 筛选侧边栏 -->
            <aside class="qa-filters">
                <div class="filters-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        筛选条件
                    </h3>
                    <a href="/qa/" class="clear-filters">清除筛选</a>
                </div>
                
                <!-- 按状态筛选 -->
                <div class="filter-group">
                    <h4>按状态查找</h4>
                    <div class="filter-options">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])); ?>" 
                           class="filter-option <?php echo !$status ? 'active' : ''; ?>">
                            全部问题
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'answered', 'page' => 1])); ?>" 
                           class="filter-option <?php echo $status == 'answered' ? 'active' : ''; ?>">
                            <span class="status-badge status-answered">已解答</span>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'unanswered', 'page' => 1])); ?>" 
                           class="filter-option <?php echo $status == 'unanswered' ? 'active' : ''; ?>">
                            <span class="status-badge status-pending">待解答</span>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'expert', 'page' => 1])); ?>" 
                           class="filter-option <?php echo $status == 'expert' ? 'active' : ''; ?>">
                            <span class="status-badge status-expert">专家解答</span>
                        </a>
                    </div>
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
                
                <!-- 热门问题 -->
                <div class="filter-group">
                    <h4>热门问题</h4>
                    <div class="hot-questions">
                        <?php foreach (array_slice($hotQuestions, 0, 6) as $hot): ?>
                            <div class="hot-item">
                                <h5>
                                    <a href="/qa/detail.php?id=<?php echo $hot['id']; ?>">
                                        <?php echo h(truncate($hot['title'], 50)); ?>
                                    </a>
                                </h5>
                                <div class="hot-meta">
                                    <span class="category"><?php echo h($hot['category_name']); ?></span>
                                    <span class="views">
                                        <i class="fas fa-eye"></i>
                                        <?php echo number_format($hot['view_count']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 最新回答 -->
                <div class="filter-group">
                    <h4>最新回答</h4>
                    <div class="recent-answers">
                        <?php foreach ($recentAnswers as $answer): ?>
                            <div class="answer-item">
                                <div class="answerer-info">
                                    <div class="answerer-avatar">
                                        <?php if ($answer['doctor_avatar']): ?>
                                            <img src="<?php echo h($answer['doctor_avatar']); ?>" 
                                                 alt="<?php echo h($answer['doctor_name']); ?>">
                                        <?php elseif ($answer['user_avatar']): ?>
                                            <img src="<?php echo h($answer['user_avatar']); ?>" 
                                                 alt="<?php echo h($answer['username']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="answerer-details">
                                        <h6>
                                            <?php if ($answer['doctor_name']): ?>
                                                <?php echo h($answer['doctor_name']); ?>
                                                <span class="doctor-badge">医生</span>
                                            <?php else: ?>
                                                <?php echo h($answer['username']); ?>
                                            <?php endif; ?>
                                        </h6>
                                        <p>回答了：<a href="/qa/detail.php?id=<?php echo $answer['question_id']; ?>">
                                            <?php echo h(truncate($answer['question_title'], 30)); ?>
                                        </a></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            
            <!-- 主要内容区 -->
            <main class="qa-main">
                <!-- 搜索结果统计和排序 -->
                <div class="results-header">
                    <div class="results-info">
                        <h2>
                            <?php if ($keyword): ?>
                                搜索"<?php echo h($keyword); ?>"的结果
                            <?php elseif ($category): ?>
                                <?php 
                                $categoryInfo = getCategoryById($category);
                                echo h($categoryInfo['name'] ?? '未知分类');
                                ?>问答
                            <?php elseif ($status == 'answered'): ?>
                                已解答问题
                            <?php elseif ($status == 'unanswered'): ?>
                                待解答问题
                            <?php elseif ($status == 'expert'): ?>
                                专家解答
                            <?php else: ?>
                                全部问题
                            <?php endif; ?>
                        </h2>
                        <p>共找到 <strong><?php echo number_format($totalQuestions); ?></strong> 个问题</p>
                    </div>
                    
                    <div class="results-sort">
                        <select class="sort-select" onchange="location.href=this.value">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'latest'])); ?>" 
                                    <?php echo $sort == 'latest' ? 'selected' : ''; ?>>
                                最新发布
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'hot'])); ?>" 
                                    <?php echo $sort == 'hot' ? 'selected' : ''; ?>>
                                热度排序
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'answers'])); ?>" 
                                    <?php echo $sort == 'answers' ? 'selected' : ''; ?>>
                                回答最多
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if ($questions): ?>
                    <!-- 问题列表 -->
                    <div class="questions-list">
                        <?php foreach ($questions as $question): ?>
                            <div class="question-card">
                                <div class="question-stats">
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo $question['answer_count']; ?></span>
                                        <span class="stat-label">回答</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo number_format($question['view_count']); ?></span>
                                        <span class="stat-label">浏览</span>
                                    </div>
                                </div>
                                
                                <div class="question-content">
                                    <div class="question-header">
                                        <h3 class="question-title">
                                            <a href="/qa/detail.php?id=<?php echo $question['id']; ?>">
                                                <?php echo h($question['title']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="question-badges">
                                            <?php if ($question['category_name']): ?>
                                                <span class="category-badge">
                                                    <?php echo h($question['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($question['expert_answer_count'] > 0): ?>
                                                <span class="status-badge status-expert">专家解答</span>
                                            <?php elseif ($question['answer_count'] > 0): ?>
                                                <span class="status-badge status-answered">已解答</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">待解答</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($question['is_urgent']): ?>
                                                <span class="urgent-badge">紧急</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($question['content']): ?>
                                        <div class="question-excerpt">
                                            <?php echo h(truncate(strip_tags($question['content']), 150)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="question-footer">
                                        <div class="question-author">
                                            <div class="author-avatar">
                                                <?php if ($question['user_avatar']): ?>
                                                    <img src="<?php echo h($question['user_avatar']); ?>" 
                                                         alt="<?php echo h($question['username']); ?>">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="author-info">
                                                <span class="author-name">
                                                    <?php echo h($question['username'] ?? '匿名用户'); ?>
                                                </span>
                                                <span class="question-time">
                                                    <?php echo timeAgo($question['created_at']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="question-actions">
                                            <a href="/qa/detail.php?id=<?php echo $question['id']; ?>" class="action-btn">
                                                <i class="fas fa-eye"></i>
                                                查看详情
                                            </a>
                                            
                                            <?php if ($question['answer_count'] == 0): ?>
                                                <a href="/qa/detail.php?id=<?php echo $question['id']; ?>#answer-form" 
                                                   class="action-btn answer-btn">
                                                    <i class="fas fa-reply"></i>
                                                    我来回答
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
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
                            echo generatePagination($page, $totalPages, '/qa/', $paginationParams);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- 无结果提示 -->
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3>没有找到相关问题</h3>
                        <p>
                            <?php if ($keyword): ?>
                                抱歉，没有找到与"<strong><?php echo h($keyword); ?></strong>"相关的问题
                            <?php else: ?>
                                当前筛选条件下没有找到相关问题
                            <?php endif; ?>
                        </p>
                        
                        <div class="no-results-actions">
                            <a href="/qa/ask.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                提出新问题
                            </a>
                            <a href="/qa/" class="btn btn-outline">
                                <i class="fas fa-list"></i>
                                查看全部问题
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 搜索建议
    let searchTimeout;
    $('#searchInput').on('input', function() {
        const keyword = $(this).val().trim();
        clearTimeout(searchTimeout);
        
        if (keyword.length >= 2) {
            searchTimeout = setTimeout(() => {
                // 这里可以添加搜索建议的AJAX逻辑
            }, 300);
        }
    });
    
    // 问题操作
    $('.action-btn').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && href.includes('#')) {
            e.preventDefault();
            window.location.href = href;
        }
    });
    
    // 回答按钮效果
    $('.answer-btn').on('mouseenter', function() {
        $(this).html('<i class="fas fa-reply"></i> 快速回答');
    }).on('mouseleave', function() {
        $(this).html('<i class="fas fa-reply"></i> 我来回答');
    });
});
</script>

<?php include '../templates/footer.php'; ?>