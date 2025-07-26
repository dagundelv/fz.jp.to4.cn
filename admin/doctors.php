<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "医生管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $doctorId = intval($_POST['doctor_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM doctors WHERE id = ?", [$doctorId])['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $db->update('doctors', ['status' => $newStatus], 'id = ?', [$doctorId]);
            
            jsonResponse([
                'success' => true,
                'message' => '医生状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('doctors', 'id = ?', [$doctorId]);
            jsonResponse([
                'success' => true,
                'message' => '医生已删除',
                'reload' => true
            ]);
            break;
    }
}

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$search = trim($_GET['search'] ?? '');
$hospital = $_GET['hospital'] ?? '';
$category = $_GET['category'] ?? '';
$title = $_GET['title'] ?? '';
$status = $_GET['status'] ?? '';

// 构建查询条件
$whereConditions = ["d.status != 'deleted'"];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(d.name LIKE ? OR d.specialties LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
}

if ($hospital) {
    $whereConditions[] = "d.hospital_id = ?";
    $queryParams[] = $hospital;
}

if ($category) {
    $whereConditions[] = "d.category_id = ?";
    $queryParams[] = $category;
}

if ($title) {
    $whereConditions[] = "d.title = ?";
    $queryParams[] = $title;
}

if ($status) {
    $whereConditions[] = "d.status = ?";
    $queryParams[] = $status;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// 获取总数
$totalDoctors = $db->fetch("
    SELECT COUNT(*) as count 
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id 
    {$whereClause}
", $queryParams)['count'];

$totalPages = ceil($totalDoctors / $pageSize);

// 获取医生列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$doctors = $db->fetchAll("
    SELECT d.*, h.name as hospital_name, c.name as category_name,
           (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id) as appointment_count,
           (SELECT COUNT(*) FROM qa_answers WHERE doctor_id = d.id) as answer_count
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    {$whereClause}
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE status != 'deleted'")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")['count'],
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE status = 'inactive'")['count'],
    'chief' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE title = '主任医师'")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE DATE(created_at) = CURDATE()")['count']
];

// 获取医院和分类列表
$hospitals = $db->fetchAll("SELECT id, name FROM hospitals WHERE status = 'active' ORDER BY name");
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>医生管理</h2>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showAddDoctorModal()">
                <i class="fas fa-plus"></i>
                添加医生
            </button>
            <button class="btn btn-secondary" onclick="exportData('doctors')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总医生数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">在职医生</span>
            <span class="stat-value"><?php echo number_format($stats['active']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">离职医生</span>
            <span class="stat-value"><?php echo number_format($stats['inactive']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">主任医师</span>
            <span class="stat-value"><?php echo number_format($stats['chief']); ?></span>
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
                <input type="text" name="search" placeholder="搜索医生姓名或专长..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="hospital" class="filter-select">
                    <option value="">全部医院</option>
                    <?php foreach ($hospitals as $hospitalItem): ?>
                        <option value="<?php echo $hospitalItem['id']; ?>" <?php echo $hospital == $hospitalItem['id'] ? 'selected' : ''; ?>>
                            <?php echo h($hospitalItem['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="category" class="filter-select">
                    <option value="">全部科室</option>
                    <?php foreach ($categories as $categoryItem): ?>
                        <option value="<?php echo $categoryItem['id']; ?>" <?php echo $category == $categoryItem['id'] ? 'selected' : ''; ?>>
                            <?php echo h($categoryItem['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="title" class="filter-select">
                    <option value="">全部职称</option>
                    <option value="主任医师" <?php echo $title === '主任医师' ? 'selected' : ''; ?>>主任医师</option>
                    <option value="副主任医师" <?php echo $title === '副主任医师' ? 'selected' : ''; ?>>副主任医师</option>
                    <option value="主治医师" <?php echo $title === '主治医师' ? 'selected' : ''; ?>>主治医师</option>
                    <option value="住院医师" <?php echo $title === '住院医师' ? 'selected' : ''; ?>>住院医师</option>
                    <option value="医师" <?php echo $title === '医师' ? 'selected' : ''; ?>>医师</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>在职</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>离职</option>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/doctors.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 医生列表 -->
    <div class="table-section">
        <?php if ($doctors): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>医生信息</th>
                            <th>职称/科室</th>
                            <th>所属医院</th>
                            <th>专长</th>
                            <th>统计</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $doctor['id']; ?>">
                                </td>
                                <td>
                                    <div class="doctor-info">
                                        <div class="doctor-avatar">
                                            <?php if ($doctor['avatar']): ?>
                                                <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="doctor-details">
                                            <h4><?php echo h($doctor['name']); ?></h4>
                                            <?php if ($doctor['education']): ?>
                                                <p class="education"><?php echo h($doctor['education']); ?></p>
                                            <?php endif; ?>
                                            <div class="rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $doctor['rating'] ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <span><?php echo number_format($doctor['rating'], 1); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-title">
                                        <span class="title-badge title-<?php 
                                            $titleClass = 'other';
                                            if (strpos($doctor['title'], '主任医师') !== false) $titleClass = 'chief';
                                            elseif (strpos($doctor['title'], '副主任医师') !== false) $titleClass = 'deputy';
                                            elseif (strpos($doctor['title'], '主治医师') !== false) $titleClass = 'attending';
                                            elseif (strpos($doctor['title'], '住院医师') !== false) $titleClass = 'resident';
                                            echo $titleClass;
                                        ?>">
                                            <?php echo h($doctor['title']); ?>
                                        </span>
                                        <?php if ($doctor['category_name']): ?>
                                            <span class="category-badge">
                                                <?php echo h($doctor['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hospital-info">
                                        <?php echo h($doctor['hospital_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="specialties">
                                        <?php echo h(truncate($doctor['specialties'] ?? '', 50)); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-stats-compact">
                                        <span class="stat-compact">
                                            <i class="fas fa-calendar-check"></i>
                                            <?php echo $doctor['appointment_count']; ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-reply"></i>
                                            <?php echo $doctor['answer_count']; ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-eye"></i>
                                            <?php echo formatNumber($doctor['view_count']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $doctor['status']; ?>">
                                        <?php echo $doctor['status'] === 'active' ? '在职' : '离职'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editDoctor(<?php echo $doctor['id']; ?>)" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $doctor['status'] === 'active' ? '离职' : '在职'; ?>">
                                                <i class="fas fa-<?php echo $doctor['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除医生 <?php echo h($doctor['name']); ?> 吗？" title="删除">
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
                    echo generatePagination($page, $totalPages, '/admin/doctors.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>暂无医生数据</h3>
                <p>没有找到符合条件的医生</p>
                <button class="btn btn-primary" onclick="showAddDoctorModal()">
                    <i class="fas fa-plus"></i>
                    添加医生
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 医生管理页面样式 */
.doctor-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.doctor-avatar img,
.avatar-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7f8c8d;
    font-size: 18px;
}

.doctor-details h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #2c3e50;
}

.doctor-details .education {
    margin: 0 0 4px 0;
    font-size: 11px;
    color: #7f8c8d;
}

.rating {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 12px;
}

.rating .fa-star {
    color: #ddd;
}

.rating .fa-star.active {
    color: #f39c12;
}

.rating span {
    margin-left: 4px;
    color: #7f8c8d;
}

.doctor-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.title-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-align: center;
}

.title-chief { background: #d4edda; color: #155724; }
.title-deputy { background: #d1ecf1; color: #0c5460; }
.title-attending { background: #fff3cd; color: #856404; }
.title-resident { background: #f8d7da; color: #721c24; }
.title-other { background: #e2e3e5; color: #383d41; }

.category-badge {
    padding: 2px 6px;
    background: #f8f9fa;
    color: #6c757d;
    border-radius: 3px;
    font-size: 10px;
    text-align: center;
}

.hospital-info {
    font-size: 13px;
    color: #2c3e50;
}

.specialties {
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.4;
}

.doctor-stats {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 10px;
}

.doctor-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #9ca3af;
    line-height: 1.2;
}

.doctor-stats i {
    width: 10px;
    text-align: center;
    font-size: 9px;
}

.doctor-stats-compact {
    display: flex;
    gap: 8px;
    align-items: center;
    font-size: 11px;
    color: #6b7280;
}

.stat-compact {
    display: flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
}

.stat-compact i {
    font-size: 10px;
    width: 12px;
    text-align: center;
    color: #9ca3af;
}
</style>

<script>
function showAddDoctorModal() {
    // 实际项目中这里应该显示添加医生的模态框
    showAdminMessage('添加医生功能正在开发中', 'info');
}

function editDoctor(doctorId) {
    // 实际项目中这里应该显示编辑医生的模态框
    showAdminMessage('编辑医生功能正在开发中', 'info');
}
</script>

<?php include 'templates/footer.php'; ?>