<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "评论管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $commentId = intval($_POST['comment_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM comments WHERE id = ?", [$commentId])['status'];
            $newStatus = $currentStatus === 'approved' ? 'hidden' : 'approved';
            $db->update('comments', ['status' => $newStatus], 'id = ?', [$commentId]);
            
            jsonResponse([
                'success' => true,
                'message' => '评论状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('comments', 'id = ?', [$commentId]);
            jsonResponse([
                'success' => true,
                'message' => '评论已删除',
                'reload' => true
            ]);
            break;
            
        case 'batch_approve':
            $commentIds = $_POST['comment_ids'] ?? [];
            if ($commentIds) {
                $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';
                $db->query("UPDATE comments SET status = 'approved' WHERE id IN ({$placeholders})", $commentIds);
            }
            
            jsonResponse([
                'success' => true,
                'message' => '批量审核完成',
                'reload' => true
            ]);
            break;
            
        case 'batch_hide':
            $commentIds = $_POST['comment_ids'] ?? [];
            if ($commentIds) {
                $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';
                $db->query("UPDATE comments SET status = 'hidden' WHERE id IN ({$placeholders})", $commentIds);
            }
            
            jsonResponse([
                'success' => true,
                'message' => '批量隐藏完成',
                'reload' => true
            ]);
            break;
            
        case 'batch_delete':
            $commentIds = $_POST['comment_ids'] ?? [];
            if ($commentIds) {
                $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';
                $db->query("DELETE FROM comments WHERE id IN ({$placeholders})", $commentIds);
            }
            
            jsonResponse([
                'success' => true,
                'message' => '批量删除完成',
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
$type = $_GET['type'] ?? '';
$date = $_GET['date'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(c.content LIKE ? OR u.username LIKE ? OR u.real_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $whereConditions[] = "c.status = ?";
    $queryParams[] = $status;
}

if ($type) {
    $whereConditions[] = "c.target_type = ?";
    $queryParams[] = $type;
}

if ($date) {
    $whereConditions[] = "DATE(c.created_at) = ?";
    $queryParams[] = $date;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalComments = $db->fetch("SELECT COUNT(*) as count FROM comments c LEFT JOIN users u ON c.user_id = u.id {$whereClause}", $queryParams)['count'];
$totalPages = ceil($totalComments / $pageSize);

// 获取评论列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$comments = $db->fetchAll("
    SELECT c.*, u.username, u.real_name, u.avatar,
           CASE 
               WHEN c.target_type = 'article' THEN a.title
               WHEN c.target_type = 'doctor' THEN d.name
               WHEN c.target_type = 'disease' THEN dis.name
               WHEN c.target_type = 'qa_question' THEN q.title
               ELSE '未知内容'
           END as target_title
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN articles a ON c.target_type = 'article' AND c.target_id = a.id
    LEFT JOIN doctors d ON c.target_type = 'doctor' AND c.target_id = d.id
    LEFT JOIN diseases dis ON c.target_type = 'disease' AND c.target_id = dis.id
    LEFT JOIN qa_questions q ON c.target_type = 'qa_question' AND c.target_id = q.id
    {$whereClause}
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM comments")['count'],
    'approved' => $db->fetch("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")['count'],
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count'],
    'hidden' => $db->fetch("SELECT COUNT(*) as count FROM comments WHERE status = 'hidden'")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM comments WHERE DATE(created_at) = CURDATE()")['count'],
    'this_week' => $db->fetch("SELECT COUNT(*) as count FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count']
];

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>评论管理</h2>
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="exportData('comments')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总评论数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已审核</span>
            <span class="stat-value"><?php echo number_format($stats['approved']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">待审核</span>
            <span class="stat-value"><?php echo number_format($stats['pending']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已隐藏</span>
            <span class="stat-value"><?php echo number_format($stats['hidden']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">今日新增</span>
            <span class="stat-value"><?php echo number_format($stats['today']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">本周新增</span>
            <span class="stat-value"><?php echo number_format($stats['this_week']); ?></span>
        </div>
    </div>
    
    <!-- 筛选和搜索 -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="搜索评论内容或用户..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已审核</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                    <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>已隐藏</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="type" class="filter-select">
                    <option value="">全部类型</option>
                    <option value="article" <?php echo $type === 'article' ? 'selected' : ''; ?>>文章评论</option>
                    <option value="doctor" <?php echo $type === 'doctor' ? 'selected' : ''; ?>>医生评论</option>
                    <option value="disease" <?php echo $type === 'disease' ? 'selected' : ''; ?>>疾病评论</option>
                    <option value="qa_question" <?php echo $type === 'qa_question' ? 'selected' : ''; ?>>问答评论</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="date" name="date" value="<?php echo h($date); ?>" class="filter-select">
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/comments.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 批量操作 -->
    <div class="batch-actions" style="display: none;">
        <div class="batch-actions-content">
            <span class="selected-count">已选择 <span id="selectedCount">0</span> 条评论</span>
            <div class="batch-buttons">
                <button class="btn btn-sm btn-success" onclick="batchApprove()">
                    <i class="fas fa-check"></i>
                    批量审核
                </button>
                <button class="btn btn-sm btn-warning" onclick="batchHide()">
                    <i class="fas fa-eye-slash"></i>
                    批量隐藏
                </button>
                <button class="btn btn-sm btn-danger" onclick="batchDelete()">
                    <i class="fas fa-trash"></i>
                    批量删除
                </button>
            </div>
        </div>
    </div>
    
    <!-- 评论列表 -->
    <div class="table-section">
        <?php if ($comments): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>评论信息</th>
                            <th>评论者</th>
                            <th>评论对象</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $comment['id']; ?>">
                                </td>
                                <td>
                                    <div class="comment-info">
                                        <div class="comment-content">
                                            <?php echo h($comment['content']); ?>
                                        </div>
                                        <?php if ($comment['parent_id']): ?>
                                            <div class="reply-indicator">
                                                <i class="fas fa-reply"></i>
                                                回复评论
                                            </div>
                                        <?php endif; ?>
                                        <div class="comment-meta">
                                            <span class="ip">IP: <?php echo h($comment['ip_address']); ?></span>
                                            <?php if ($comment['user_agent']): ?>
                                                <span class="user-agent" title="<?php echo h($comment['user_agent']); ?>">
                                                    <i class="fas fa-desktop"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="commenter-info">
                                        <div class="user-avatar">
                                            <?php if ($comment['avatar']): ?>
                                                <img src="<?php echo h($comment['avatar']); ?>" alt="<?php echo h($comment['username']); ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo h($comment['real_name'] ?: $comment['username']); ?></h4>
                                            <?php if ($comment['username'] !== ($comment['real_name'] ?: $comment['username'])): ?>
                                                <p class="username">@<?php echo h($comment['username']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="target-info">
                                        <div class="target-type">
                                            <?php
                                            $typeIcons = [
                                                'article' => 'fas fa-newspaper',
                                                'doctor' => 'fas fa-user-md',
                                                'disease' => 'fas fa-virus',
                                                'qa_question' => 'fas fa-question-circle'
                                            ];
                                            $typeNames = [
                                                'article' => '文章',
                                                'doctor' => '医生',
                                                'disease' => '疾病',
                                                'qa_question' => '问答'
                                            ];
                                            ?>
                                            <i class="<?php echo $typeIcons[$comment['target_type']] ?? 'fas fa-file'; ?>"></i>
                                            <?php echo $typeNames[$comment['target_type']] ?? '未知'; ?>
                                        </div>
                                        <div class="target-title">
                                            <?php echo h(truncate($comment['target_title'], 50)); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $comment['status']; ?>">
                                        <?php
                                        $statusNames = [
                                            'approved' => '已审核',
                                            'pending' => '待审核',
                                            'hidden' => '已隐藏'
                                        ];
                                        echo $statusNames[$comment['status']] ?? $comment['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $comment['status'] === 'approved' ? '隐藏' : '审核'; ?>">
                                                <i class="fas fa-<?php echo $comment['status'] === 'approved' ? 'eye-slash' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除这条评论吗？" title="删除">
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
                    echo generatePagination($page, $totalPages, '/admin/comments.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>暂无评论数据</h3>
                <p>没有找到符合条件的评论</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 评论管理页面样式 */
.comment-info {
    max-width: 300px;
}

.comment-content {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 6px;
    word-break: break-word;
}

.reply-indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #6c757d;
    margin-bottom: 4px;
}

.comment-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #95a5a6;
}

.comment-meta .ip {
    font-family: monospace;
}

.commenter-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-avatar img,
.avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7f8c8d;
}

.user-details h4 {
    margin: 0 0 2px 0;
    font-size: 13px;
    color: #2c3e50;
}

.user-details .username {
    margin: 0;
    font-size: 11px;
    color: #7f8c8d;
}

.target-info {
    font-size: 12px;
}

.target-type {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #6c757d;
    margin-bottom: 4px;
    font-weight: 500;
}

.target-title {
    color: #2c3e50;
    line-height: 1.3;
}

.batch-actions {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 20px;
}

.batch-actions-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.selected-count {
    font-weight: 500;
    color: #495057;
}

.batch-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 4px 12px;
    font-size: 12px;
}

.status-approved { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-hidden { background: #e2e3e5; color: #383d41; }
</style>

<script>
// 全选/取消全选
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBatchActions();
});

// 监听单个复选框变化
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-checkbox')) {
        updateBatchActions();
    }
});

function updateBatchActions() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const batchActions = document.querySelector('.batch-actions');
    
    if (checkedBoxes.length > 0) {
        batchActions.style.display = 'block';
        document.getElementById('selectedCount').textContent = checkedBoxes.length;
    } else {
        batchActions.style.display = 'none';
    }
}

function getSelectedIds() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    return Array.from(checkedBoxes).map(checkbox => checkbox.value);
}

function batchApprove() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showAdminMessage('请先选择要审核的评论', 'warning');
        return;
    }
    
    if (!confirm(`确定要审核选中的 ${ids.length} 条评论吗？`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'batch_approve';
    form.appendChild(actionInput);
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'comment_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        if (data.success) {
            document.body.removeChild(form);
        }
    });
}

function batchHide() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showAdminMessage('请先选择要隐藏的评论', 'warning');
        return;
    }
    
    if (!confirm(`确定要隐藏选中的 ${ids.length} 条评论吗？`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'batch_hide';
    form.appendChild(actionInput);
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'comment_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        if (data.success) {
            document.body.removeChild(form);
        }
    });
}

function batchDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showAdminMessage('请先选择要删除的评论', 'warning');
        return;
    }
    
    if (!confirm(`确定要删除选中的 ${ids.length} 条评论吗？此操作不可恢复！`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'batch_delete';
    form.appendChild(actionInput);
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'comment_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        if (data.success) {
            document.body.removeChild(form);
        }
    });
}
</script>

<?php include 'templates/footer.php'; ?>