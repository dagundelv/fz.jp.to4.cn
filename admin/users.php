<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "用户管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM users WHERE id = ?", [$userId])['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $db->update('users', ['status' => $newStatus], 'id = ?', [$userId]);
            
            jsonResponse([
                'success' => true,
                'message' => '用户状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('users', 'id = ?', [$userId]);
            jsonResponse([
                'success' => true,
                'message' => '用户已删除',
                'reload' => true
            ]);
            break;
            
        case 'update_role':
            $role = $_POST['role'] ?? 'user';
            $db->update('users', ['role' => $role], 'id = ?', [$userId]);
            
            jsonResponse([
                'success' => true,
                'message' => '用户角色已更新',
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
$role = $_GET['role'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(username LIKE ? OR email LIKE ? OR real_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $whereConditions[] = "status = ?";
    $queryParams[] = $status;
}

if ($role) {
    $whereConditions[] = "role = ?";
    $queryParams[] = $role;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users {$whereClause}", $queryParams)['count'];
$totalPages = ceil($totalUsers / $pageSize);

// 获取用户列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$users = $db->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM appointments WHERE user_id = u.id) as appointment_count,
           (SELECT COUNT(*) FROM qa_questions WHERE user_id = u.id) as question_count,
           (SELECT COUNT(*) FROM user_favorites WHERE user_id = u.id) as favorite_count
    FROM users u 
    {$whereClause}
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")['count'],
    'admin' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count']
];

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>用户管理</h2>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="exportData('users')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总用户数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">正常用户</span>
            <span class="stat-value"><?php echo number_format($stats['active']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">禁用用户</span>
            <span class="stat-value"><?php echo number_format($stats['inactive']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">管理员</span>
            <span class="stat-value"><?php echo number_format($stats['admin']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">今日新增</span>
            <span class="stat-value"><?php echo number_format($stats['today']); ?></span>
        </div>
    </div>
    
    <!-- 筛选和搜索 -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="搜索用户名、邮箱或姓名..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="role" class="filter-select">
                    <option value="">全部角色</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>普通用户</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 用户列表 -->
    <div class="table-section">
        <div class="table-header">
            <div class="batch-actions" style="display: none;">
                <span>已选择 <span class="selected-count">0</span> 项</span>
                <button class="btn btn-danger btn-sm" onclick="batchDeleteUsers()">批量删除</button>
                <button class="btn btn-secondary btn-sm" onclick="batchToggleStatus()">批量切换状态</button>
            </div>
        </div>
        
        <?php if ($users): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>用户信息</th>
                            <th>角色</th>
                            <th>状态</th>
                            <th>统计</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $user['id']; ?>">
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php if ($user['avatar']): ?>
                                                <img src="<?php echo h($user['avatar']); ?>" alt="<?php echo h($user['username']); ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo h($user['username']); ?></h4>
                                            <p><?php echo h($user['email']); ?></p>
                                            <?php if ($user['real_name']): ?>
                                                <span class="real-name"><?php echo h($user['real_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo $user['status'] === 'active' ? '正常' : '禁用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="user-stats-compact">
                                        <span class="stat-compact">
                                            <i class="fas fa-calendar-check"></i>
                                            <?php echo $user['appointment_count']; ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-question-circle"></i>
                                            <?php echo $user['question_count']; ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-heart"></i>
                                            <?php echo $user['favorite_count']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="date-time"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $user['status'] === 'active' ? '禁用' : '启用'; ?>">
                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if ($user['role'] !== 'admin' || $user['id'] != getCurrentUser()['id']): ?>
                                            <form method="POST" data-ajax style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete delete-btn" 
                                                        data-confirm="确定要删除用户 <?php echo h($user['username']); ?> 吗？" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                    echo generatePagination($page, $totalPages, '/admin/users.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>暂无用户数据</h3>
                <p>没有找到符合条件的用户</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 编辑用户模态框 -->
<div id="editUserModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>编辑用户</h3>
            <button class="modal-close" onclick="hideEditUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editUserForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" id="editUsername" required>
                </div>
                
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" name="email" id="editEmail" required>
                </div>
                
                <div class="form-group">
                    <label>真实姓名</label>
                    <input type="text" name="real_name" id="editRealName">
                </div>
                
                <div class="form-group">
                    <label>角色</label>
                    <select name="role" id="editRole">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <select name="status" id="editStatus">
                        <option value="active">正常</option>
                        <option value="inactive">禁用</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideEditUserModal()">取消</button>
            <button class="btn btn-primary" onclick="saveUser()">保存</button>
        </div>
    </div>
</div>

<style>
/* 用户管理页面特定样式 */
.admin-page {
    max-width: 1400px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h2 {
    margin: 0;
    color: #2c3e50;
}

.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.stat-item {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 8px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    gap: 10px;
}

.search-input {
    width: 300px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    min-width: 120px;
}

.table-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.batch-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
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
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #2c3e50;
}

.user-details p {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
}

.real-name {
    font-size: 11px;
    color: #95a5a6;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.role-admin {
    background: #e8f5e8;
    color: #2e7d32;
}

.role-user {
    background: #e3f2fd;
    color: #1565c0;
}

.user-stats {
    font-size: 12px;
}

.user-stats .stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2px;
    padding: 0;
    background: none;
    box-shadow: none;
    text-align: left;
}

.user-stats .label {
    color: #7f8c8d;
}

.user-stats .value {
    color: #2c3e50;
    font-weight: 500;
}

.date-time {
    font-size: 12px;
    color: #7f8c8d;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s ease;
}

.btn-edit {
    background: #e3f2fd;
    color: #1565c0;
}

.btn-edit:hover {
    background: #1565c0;
    color: white;
}

.btn-toggle {
    background: #fff3e0;
    color: #ef6c00;
}

.btn-toggle:hover {
    background: #ef6c00;
    color: white;
}

.btn-delete {
    background: #ffebee;
    color: #c62828;
}

.btn-delete:hover {
    background: #c62828;
    color: white;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.no-data-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.no-data h3 {
    margin: 0 0 8px 0;
    color: #2c3e50;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #2c3e50;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
</style>

<script>
function editUser(userId) {
    // 这里应该通过AJAX获取用户详细信息并填充表单
    // 为了演示，直接显示模态框
    document.getElementById('editUserModal').style.display = 'block';
    document.getElementById('editUserId').value = userId;
}

function hideEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

function saveUser() {
    const form = document.getElementById('editUserForm');
    submitAjaxForm(form, function(data) {
        if (data.success) {
            hideEditUserModal();
        }
    });
}

function batchDeleteUsers() {
    const checkedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (checkedIds.length === 0) {
        showAdminMessage('请选择要删除的用户', 'error');
        return;
    }
    
    showConfirmModal(`确定要删除选中的 ${checkedIds.length} 个用户吗？此操作不可撤销。`, function() {
        batchOperation('delete_users', checkedIds);
    });
}

function batchToggleStatus() {
    const checkedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (checkedIds.length === 0) {
        showAdminMessage('请选择要操作的用户', 'error');
        return;
    }
    
    batchOperation('toggle_user_status', checkedIds);
}
</script>

<?php include 'templates/footer.php'; ?>