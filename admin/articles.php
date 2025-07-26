<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "文章管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $articleId = intval($_POST['article_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM articles WHERE id = ?", [$articleId])['status'];
            $newStatus = $currentStatus === 'published' ? 'draft' : 'published';
            $db->update('articles', ['status' => $newStatus], 'id = ?', [$articleId]);
            
            jsonResponse([
                'success' => true,
                'message' => '文章状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('articles', 'id = ?', [$articleId]);
            jsonResponse([
                'success' => true,
                'message' => '文章已删除',
                'reload' => true
            ]);
            break;
            
        case 'toggle_featured':
            $currentFeatured = $db->fetch("SELECT is_featured FROM articles WHERE id = ?", [$articleId])['is_featured'];
            $newFeatured = $currentFeatured ? 0 : 1;
            $db->update('articles', ['is_featured' => $newFeatured], 'id = ?', [$articleId]);
            
            jsonResponse([
                'success' => true,
                'message' => '推荐状态已更新',
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

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.author LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $whereConditions[] = "a.status = ?";
    $queryParams[] = $status;
}

if ($category) {
    $whereConditions[] = "a.category_id = ?";
    $queryParams[] = $category;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalArticles = $db->fetch("SELECT COUNT(*) as count FROM articles a {$whereClause}", $queryParams)['count'];
$totalPages = ceil($totalArticles / $pageSize);

// 获取文章列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name,
           (SELECT COUNT(*) FROM comments WHERE target_type = 'article' AND target_id = a.id) as comment_count
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    {$whereClause}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC");

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM articles")['count'],
    'published' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
    'draft' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'],
    'featured' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE is_featured = 1")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE DATE(created_at) = CURDATE()")['count']
];

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>文章管理</h2>
        <div class="page-actions">
            <a href="/admin/article-edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                新建文章
            </a>
            <button class="btn btn-secondary" onclick="exportData('articles')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总文章数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已发布</span>
            <span class="stat-value"><?php echo number_format($stats['published']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">草稿</span>
            <span class="stat-value"><?php echo number_format($stats['draft']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">推荐文章</span>
            <span class="stat-value"><?php echo number_format($stats['featured']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">今日新建</span>
            <span class="stat-value"><?php echo number_format($stats['today']); ?></span>
        </div>
    </div>
    
    <!-- 筛选和搜索 -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="搜索文章标题、内容或作者..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="category" class="filter-select">
                    <option value="">全部分类</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo h($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/articles.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 文章列表 -->
    <div class="table-section">
        <?php if ($articles): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>文章信息</th>
                            <th>分类</th>
                            <th>状态</th>
                            <th>统计</th>
                            <th>发布时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $article['id']; ?>">
                                </td>
                                <td>
                                    <div class="article-info">
                                        <?php if ($article['featured_image']): ?>
                                            <div class="article-thumb">
                                                <img src="<?php echo h($article['featured_image']); ?>" alt="<?php echo h($article['title']); ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="article-details">
                                            <h4>
                                                <a href="/news/detail.php?id=<?php echo $article['id']; ?>" target="_blank">
                                                    <?php echo h($article['title']); ?>
                                                </a>
                                                <?php if ($article['is_featured']): ?>
                                                    <span class="featured-badge">推荐</span>
                                                <?php endif; ?>
                                            </h4>
                                            <?php if ($article['subtitle']): ?>
                                                <p class="subtitle"><?php echo h($article['subtitle']); ?></p>
                                            <?php endif; ?>
                                            <div class="article-meta">
                                                <span class="author">作者：<?php echo h($article['author']); ?></span>
                                                <?php if ($article['source']): ?>
                                                    <span class="source">来源：<?php echo h($article['source']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge">
                                        <?php echo h($article['category_name'] ?? '未分类'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $article['status']; ?>">
                                        <?php echo $article['status'] === 'published' ? '已发布' : '草稿'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="article-stats-compact">
                                        <span class="stat-compact">
                                            <i class="fas fa-eye"></i>
                                            <?php echo formatNumber($article['view_count']); ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-heart"></i>
                                            <?php echo formatNumber($article['like_count']); ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-comments"></i>
                                            <?php echo $article['comment_count']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($article['publish_time'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/admin/article-edit.php?id=<?php echo $article['id']; ?>" 
                                           class="btn-action btn-edit" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn-action btn-star <?php echo $article['is_featured'] ? 'active' : ''; ?>" 
                                                    title="<?php echo $article['is_featured'] ? '取消推荐' : '设为推荐'; ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $article['status'] === 'published' ? '设为草稿' : '发布'; ?>">
                                                <i class="fas fa-<?php echo $article['status'] === 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除文章《<?php echo h($article['title']); ?>》吗？" title="删除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
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
                    echo generatePagination($page, $totalPages, '/admin/articles.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h3>暂无文章数据</h3>
                <p>没有找到符合条件的文章</p>
                <a href="/admin/article-edit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    新建文章
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 文章管理页面样式 */
.article-info {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.article-thumb {
    flex-shrink: 0;
}

.article-thumb img {
    width: 60px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
}

.article-details {
    flex: 1;
    min-width: 0;
}

.article-details h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 8px;
}

.article-details h4 a {
    color: inherit;
    text-decoration: none;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.article-details h4 a:hover {
    color: #3498db;
}

.featured-badge {
    background: #f39c12;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 500;
    flex-shrink: 0;
}

.subtitle {
    margin: 0 0 6px 0;
    font-size: 12px;
    color: #7f8c8d;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.article-meta {
    display: flex;
    gap: 15px;
    font-size: 11px;
    color: #95a5a6;
}

.category-badge {
    background: #e3f2fd;
    color: #1565c0;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.article-stats {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
}

.article-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #7f8c8d;
}

.article-stats .stat-item i {
    width: 12px;
    text-align: center;
}

.btn-star {
    background: #fff3e0;
    color: #ef6c00;
}

.btn-star.active {
    background: #f39c12;
    color: white;
}

.btn-star:hover {
    background: #f39c12;
    color: white;
}

@media (max-width: 768px) {
    .article-info {
        flex-direction: column;
    }
    
    .article-thumb {
        align-self: center;
    }
    
    .article-meta {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<?php include 'templates/footer.php'; ?>