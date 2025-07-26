<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "问答管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $qaId = intval($_POST['qa_id'] ?? 0);
    $answerId = intval($_POST['answer_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_qa_status':
            $currentStatus = $db->fetch("SELECT status FROM qa_questions WHERE id = ?", [$qaId])['status'];
            $newStatus = $currentStatus === 'published' ? 'closed' : 'published';
            $db->update('qa_questions', ['status' => $newStatus], 'id = ?', [$qaId]);
            
            jsonResponse([
                'success' => true,
                'message' => '问题状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete_question':
            $db->delete('qa_questions', 'id = ?', [$qaId]);
            jsonResponse([
                'success' => true,
                'message' => '问题已删除',
                'reload' => true
            ]);
            break;
            
        case 'toggle_answer_status':
            $currentStatus = $db->fetch("SELECT status FROM qa_answers WHERE id = ?", [$answerId])['status'];
            $newStatus = $currentStatus === 'published' ? 'hidden' : 'published';
            $db->update('qa_answers', ['status' => $newStatus], 'id = ?', [$answerId]);
            
            jsonResponse([
                'success' => true,
                'message' => '回答状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete_answer':
            $db->delete('qa_answers', 'id = ?', [$answerId]);
            jsonResponse([
                'success' => true,
                'message' => '回答已删除',
                'reload' => true
            ]);
            break;
            
        case 'set_best_answer':
            // 清除同一问题的其他最佳回答
            $questionId = $db->fetch("SELECT question_id FROM qa_answers WHERE id = ?", [$answerId])['question_id'];
            $db->update('qa_answers', ['is_best' => 0], 'question_id = ?', [$questionId]);
            
            // 设置当前回答为最佳
            $db->update('qa_answers', ['is_best' => 1], 'id = ?', [$answerId]);
            
            jsonResponse([
                'success' => true,
                'message' => '已设置为最佳回答',
                'reload' => true
            ]);
            break;
    }
}

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? 'questions'; // questions 或 answers

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($type === 'questions') {
    if ($search) {
        $whereConditions[] = "(q.title LIKE ? OR q.content LIKE ?)";
        $searchTerm = "%{$search}%";
        $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
    }
    
    if ($status) {
        $whereConditions[] = "q.status = ?";
        $queryParams[] = $status;
    }
    
    if ($category) {
        $whereConditions[] = "q.category_id = ?";
        $queryParams[] = $category;
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 获取问题总数
    $totalItems = $db->fetch("SELECT COUNT(*) as count FROM qa_questions q {$whereClause}", $queryParams)['count'];
    $totalPages = ceil($totalItems / $pageSize);
    
    // 获取问题列表
    $offset = ($page - 1) * $pageSize;
    $listParams = array_merge($queryParams, [$pageSize, $offset]);
    
    $items = $db->fetchAll("
        SELECT q.*, u.username, u.real_name, c.name as category_name,
               (SELECT COUNT(*) FROM qa_answers WHERE question_id = q.id) as answer_count,
               (SELECT COUNT(*) FROM qa_answers WHERE question_id = q.id AND is_best = 1) as has_best_answer
        FROM qa_questions q 
        LEFT JOIN users u ON q.user_id = u.id
        LEFT JOIN categories c ON q.category_id = c.id
        {$whereClause}
        ORDER BY q.created_at DESC
        LIMIT ? OFFSET ?
    ", $listParams);
} else {
    if ($search) {
        $whereConditions[] = "(a.content LIKE ? OR u.username LIKE ?)";
        $searchTerm = "%{$search}%";
        $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
    }
    
    if ($status) {
        $whereConditions[] = "a.status = ?";
        $queryParams[] = $status;
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 获取回答总数
    $totalItems = $db->fetch("SELECT COUNT(*) as count FROM qa_answers a LEFT JOIN users u ON a.user_id = u.id {$whereClause}", $queryParams)['count'];
    $totalPages = ceil($totalItems / $pageSize);
    
    // 获取回答列表
    $offset = ($page - 1) * $pageSize;
    $listParams = array_merge($queryParams, [$pageSize, $offset]);
    
    $items = $db->fetchAll("
        SELECT a.*, u.username, u.real_name, q.title as question_title,
               d.name as doctor_name, d.title as doctor_title
        FROM qa_answers a 
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN qa_questions q ON a.question_id = q.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        {$whereClause}
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ", $listParams);
}

// 统计数据
$stats = [
    'total_questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions")['count'],
    'published_questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions WHERE status = 'published'")['count'],
    'total_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers")['count'],
    'published_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers WHERE status = 'published'")['count'],
    'best_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers WHERE is_best = 1")['count'],
    'today_questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions WHERE DATE(created_at) = CURDATE()")['count'],
    'today_answers' => $db->fetch("SELECT COUNT(*) as count FROM qa_answers WHERE DATE(created_at) = CURDATE()")['count']
];

// 获取分类列表
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>问答管理</h2>
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="exportData('qa')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总问题数</span>
            <span class="stat-value"><?php echo number_format($stats['total_questions']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已发布问题</span>
            <span class="stat-value"><?php echo number_format($stats['published_questions']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">总回答数</span>
            <span class="stat-value"><?php echo number_format($stats['total_answers']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已发布回答</span>
            <span class="stat-value"><?php echo number_format($stats['published_answers']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">最佳回答</span>
            <span class="stat-value"><?php echo number_format($stats['best_answers']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">今日新增</span>
            <span class="stat-value"><?php echo number_format($stats['today_questions'] + $stats['today_answers']); ?></span>
        </div>
    </div>
    
    <!-- 筛选和搜索 -->
    <div class="filters-section">
        <div class="type-tabs">
            <a href="?type=questions" class="tab-btn <?php echo $type === 'questions' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                问题管理
            </a>
            <a href="?type=answers" class="tab-btn <?php echo $type === 'answers' ? 'active' : ''; ?>">
                <i class="fas fa-reply"></i>
                回答管理
            </a>
        </div>
        
        <form method="GET" class="filters-form">
            <input type="hidden" name="type" value="<?php echo h($type); ?>">
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="<?php echo $type === 'questions' ? '搜索问题标题或内容...' : '搜索回答内容或用户...'; ?>" 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <?php if ($type === 'questions'): ?>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>已关闭</option>
                    <?php else: ?>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                        <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>已隐藏</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <?php if ($type === 'questions'): ?>
                <div class="filter-group">
                    <select name="category" class="filter-select">
                        <option value="">全部分类</option>
                        <?php foreach ($categories as $categoryItem): ?>
                            <option value="<?php echo $categoryItem['id']; ?>" <?php echo $category == $categoryItem['id'] ? 'selected' : ''; ?>>
                                <?php echo h($categoryItem['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/qa.php?type=<?php echo h($type); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 数据列表 -->
    <div class="table-section">
        <?php if ($items): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <?php if ($type === 'questions'): ?>
                                <th>问题信息</th>
                                <th>分类</th>
                                <th>提问者</th>
                                <th>回答数</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            <?php else: ?>
                                <th>回答内容</th>
                                <th>相关问题</th>
                                <th>回答者</th>
                                <th>最佳回答</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>">
                                </td>
                                
                                <?php if ($type === 'questions'): ?>
                                    <td>
                                        <div class="question-info">
                                            <h4>
                                                <a href="/qa/detail.php?id=<?php echo $item['id']; ?>" target="_blank">
                                                    <?php echo h($item['title']); ?>
                                                </a>
                                            </h4>
                                            <p class="content-preview">
                                                <?php echo h(truncate($item['content'], 100)); ?>
                                            </p>
                                            <div class="question-meta">
                                                <span class="views">
                                                    <i class="fas fa-eye"></i>
                                                    <?php echo formatNumber($item['view_count']); ?>
                                                </span>
                                                <?php if ($item['has_best_answer']): ?>
                                                    <span class="has-best-answer">
                                                        <i class="fas fa-check-circle"></i>
                                                        已解决
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo h($item['category_name'] ?? '未分类'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span class="username"><?php echo h($item['real_name'] ?: $item['username']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="answer-count">
                                            <i class="fas fa-reply"></i>
                                            <?php echo $item['answer_count']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $item['status']; ?>">
                                            <?php echo $item['status'] === 'published' ? '已发布' : '已关闭'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-time">
                                            <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_qa_status">
                                                <input type="hidden" name="qa_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn-action btn-toggle" 
                                                        title="<?php echo $item['status'] === 'published' ? '关闭' : '发布'; ?>">
                                                    <i class="fas fa-<?php echo $item['status'] === 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="delete_question">
                                                <input type="hidden" name="qa_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete delete-btn" 
                                                        data-confirm="确定要删除问题《<?php echo h($item['title']); ?>》吗？" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <div class="answer-info">
                                            <div class="content-preview">
                                                <?php echo h(truncate($item['content'], 150)); ?>
                                            </div>
                                            <div class="answer-meta">
                                                <span class="likes">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <?php echo $item['likes']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="question-link">
                                            <a href="/qa/detail.php?id=<?php echo $item['question_id']; ?>" target="_blank">
                                                <?php echo h(truncate($item['question_title'], 50)); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="answerer-info">
                                            <?php if ($item['doctor_id']): ?>
                                                <div class="doctor-badge">
                                                    <i class="fas fa-user-md"></i>
                                                    <?php echo h($item['doctor_name']); ?>
                                                </div>
                                                <div class="doctor-title">
                                                    <?php echo h($item['doctor_title']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="username"><?php echo h($item['real_name'] ?: $item['username']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['is_best']): ?>
                                            <span class="best-answer-badge">
                                                <i class="fas fa-crown"></i>
                                                最佳回答
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="set_best_answer">
                                                <input type="hidden" name="answer_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" title="设为最佳回答">
                                                    <i class="fas fa-crown"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $item['status']; ?>">
                                            <?php echo $item['status'] === 'published' ? '已发布' : '已隐藏'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-time">
                                            <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_answer_status">
                                                <input type="hidden" name="answer_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn-action btn-toggle" 
                                                        title="<?php echo $item['status'] === 'published' ? '隐藏' : '发布'; ?>">
                                                    <i class="fas fa-<?php echo $item['status'] === 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="delete_answer">
                                                <input type="hidden" name="answer_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete delete-btn" 
                                                        data-confirm="确定要删除这个回答吗？" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-section">
                    <?php
                    $params = $_GET;
                    unset($params['page']);
                    echo generatePagination($page, $totalPages, '/admin/qa.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-<?php echo $type === 'questions' ? 'question-circle' : 'reply'; ?>"></i>
                </div>
                <h3>暂无<?php echo $type === 'questions' ? '问题' : '回答'; ?>数据</h3>
                <p>没有找到符合条件的<?php echo $type === 'questions' ? '问题' : '回答'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 问答管理页面样式 */
.type-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-btn {
    padding: 10px 20px;
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.tab-btn.active {
    background: #007bff;
    color: white;
}

.question-info h4 {
    margin: 0 0 6px 0;
    font-size: 14px;
    color: #2c3e50;
}

.question-info h4 a {
    color: inherit;
    text-decoration: none;
}

.question-info h4 a:hover {
    color: #3498db;
}

.content-preview {
    margin: 0 0 8px 0;
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.4;
}

.question-meta,
.answer-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    color: #95a5a6;
}

.question-meta i,
.answer-meta i {
    margin-right: 3px;
}

.has-best-answer {
    color: #27ae60;
    font-weight: 500;
}

.user-info .username {
    font-size: 13px;
    color: #2c3e50;
}

.answer-count,
.likes {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #7f8c8d;
}

.answer-info .content-preview {
    margin-bottom: 8px;
}

.question-link a {
    color: #3498db;
    text-decoration: none;
    font-size: 13px;
}

.question-link a:hover {
    text-decoration: underline;
}

.answerer-info .doctor-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #27ae60;
    font-weight: 500;
    margin-bottom: 2px;
}

.answerer-info .doctor-title {
    font-size: 11px;
    color: #7f8c8d;
}

.best-answer-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #f39c12;
    font-size: 12px;
    font-weight: 500;
}

.btn-outline {
    background: transparent;
    border: 1px solid #ddd;
    color: #6c757d;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}

.btn-outline:hover {
    background: #f8f9fa;
    border-color: #bbb;
}

.status-published { background: #d4edda; color: #155724; }
.status-closed { background: #f8d7da; color: #721c24; }
.status-hidden { background: #e2e3e5; color: #383d41; }
</style>

<?php include 'templates/footer.php'; ?>