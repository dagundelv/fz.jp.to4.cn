<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "预约管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? '';
            $db->update('appointments', ['status' => $status], 'id = ?', [$appointmentId]);
            
            jsonResponse([
                'success' => true,
                'message' => '预约状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('appointments', 'id = ?', [$appointmentId]);
            jsonResponse([
                'success' => true,
                'message' => '预约记录已删除',
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
$date = $_GET['date'] ?? '';
$doctor = $_GET['doctor'] ?? '';
$hospital = $_GET['hospital'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(u.username LIKE ? OR u.real_name LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $whereConditions[] = "a.status = ?";
    $queryParams[] = $status;
}

if ($date) {
    $whereConditions[] = "DATE(a.appointment_time) = ?";
    $queryParams[] = $date;
}

if ($doctor) {
    $whereConditions[] = "a.doctor_id = ?";
    $queryParams[] = $doctor;
}

if ($hospital) {
    $whereConditions[] = "a.hospital_id = ?";
    $queryParams[] = $hospital;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalAppointments = $db->fetch("
    SELECT COUNT(*) as count 
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id 
    {$whereClause}
", $queryParams)['count'];

$totalPages = ceil($totalAppointments / $pageSize);

// 获取预约列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$appointments = $db->fetchAll("
    SELECT a.*, u.username, u.real_name, u.phone, u.email,
           d.name as doctor_name, d.title as doctor_title,
           h.name as hospital_name
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN hospitals h ON a.hospital_id = h.id
    {$whereClause}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM appointments")['count'],
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")['count'],
    'confirmed' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'")['count'],
    'completed' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")['count'],
    'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'cancelled'")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_time) = CURDATE()")['count']
];

// 获取医生和医院列表
$doctors = $db->fetchAll("SELECT id, name FROM doctors WHERE status = 'active' ORDER BY name LIMIT 100");
$hospitals = $db->fetchAll("SELECT id, name FROM hospitals WHERE status = 'active' ORDER BY name LIMIT 100");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>预约管理</h2>
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="exportData('appointments')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总预约数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">待确认</span>
            <span class="stat-value"><?php echo number_format($stats['pending']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已确认</span>
            <span class="stat-value"><?php echo number_format($stats['confirmed']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已完成</span>
            <span class="stat-value"><?php echo number_format($stats['completed']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已取消</span>
            <span class="stat-value"><?php echo number_format($stats['cancelled']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">今日预约</span>
            <span class="stat-value"><?php echo number_format($stats['today']); ?></span>
        </div>
    </div>
    
    <!-- 筛选和搜索 -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="搜索用户名、姓名或手机号..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待确认</option>
                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>已确认</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="date" name="date" value="<?php echo h($date); ?>" class="filter-select">
            </div>
            
            <div class="filter-group">
                <select name="doctor" class="filter-select">
                    <option value="">全部医生</option>
                    <?php foreach ($doctors as $doctorItem): ?>
                        <option value="<?php echo $doctorItem['id']; ?>" <?php echo $doctor == $doctorItem['id'] ? 'selected' : ''; ?>>
                            <?php echo h($doctorItem['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/appointments.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 预约列表 -->
    <div class="table-section">
        <?php if ($appointments): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>预约信息</th>
                            <th>患者信息</th>
                            <th>医生信息</th>
                            <th>预约时间</th>
                            <th>联系方式</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $appointment['id']; ?>">
                                </td>
                                <td>
                                    <div class="appointment-info">
                                        <h4>预约编号：<?php echo h($appointment['appointment_number']); ?></h4>
                                        <p class="hospital">
                                            <i class="fas fa-hospital"></i>
                                            <?php echo h($appointment['hospital_name']); ?>
                                        </p>
                                        <?php if ($appointment['symptoms']): ?>
                                            <p class="symptoms">
                                                <i class="fas fa-notes-medical"></i>
                                                <?php echo h(truncate($appointment['symptoms'], 50)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="patient-info">
                                        <h4><?php echo h($appointment['real_name'] ?: $appointment['username']); ?></h4>
                                        <p>用户名：<?php echo h($appointment['username']); ?></p>
                                        <p>年龄：<?php echo h($appointment['age']); ?>岁</p>
                                        <p>性别：<?php echo $appointment['gender'] === 'male' ? '男' : ($appointment['gender'] === 'female' ? '女' : '未知'); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-info">
                                        <h4><?php echo h($appointment['doctor_name']); ?></h4>
                                        <p><?php echo h($appointment['doctor_title']); ?></p>
                                    </div>
                                </td>
                                <td>
                                    <div class="appointment-time">
                                        <div class="date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('Y-m-d', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                        <div class="time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <?php if ($appointment['phone']): ?>
                                            <p>
                                                <i class="fas fa-phone"></i>
                                                <?php echo h($appointment['phone']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($appointment['email']): ?>
                                            <p>
                                                <i class="fas fa-envelope"></i>
                                                <?php echo h($appointment['email']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php 
                                        $statusNames = [
                                            'pending' => '待确认',
                                            'confirmed' => '已确认',
                                            'completed' => '已完成',
                                            'cancelled' => '已取消'
                                        ];
                                        echo $statusNames[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($appointment['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="dropdown">
                                            <button class="btn-action btn-edit dropdown-toggle" title="更改状态">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <form method="POST" data-ajax>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="dropdown-item">确认预约</button>
                                                </form>
                                                <form method="POST" data-ajax>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="dropdown-item">标记完成</button>
                                                </form>
                                                <form method="POST" data-ajax>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="dropdown-item">取消预约</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除预约记录吗？" title="删除">
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
                    echo generatePagination($page, $totalPages, '/admin/appointments.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>暂无预约数据</h3>
                <p>没有找到符合条件的预约记录</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* 预约管理页面样式 */
.appointment-info h4 {
    margin: 0 0 6px 0;
    font-size: 13px;
    color: #2c3e50;
    font-weight: 600;
}

.appointment-info p {
    margin: 2px 0;
    font-size: 12px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.appointment-info i {
    width: 12px;
    text-align: center;
}

.patient-info h4,
.doctor-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #2c3e50;
}

.patient-info p,
.doctor-info p {
    margin: 2px 0;
    font-size: 12px;
    color: #7f8c8d;
}

.appointment-time {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
}

.appointment-time .date,
.appointment-time .time {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #2c3e50;
}

.appointment-time i {
    width: 12px;
    text-align: center;
    color: #7f8c8d;
}

.contact-info p {
    margin: 2px 0;
    font-size: 12px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.contact-info i {
    width: 12px;
    text-align: center;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle::after {
    content: '';
    width: 0;
    height: 0;
    border-left: 3px solid transparent;
    border-right: 3px solid transparent;
    border-top: 3px solid currentColor;
    margin-left: 4px;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 1000;
    min-width: 120px;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 8px 12px;
    background: none;
    border: none;
    text-align: left;
    font-size: 12px;
    color: #2c3e50;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item:last-child {
    border-bottom: none;
}
</style>

<?php include 'templates/footer.php'; ?>