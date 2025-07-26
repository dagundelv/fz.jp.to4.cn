<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "医院管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $hospitalId = intval($_POST['hospital_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM hospitals WHERE id = ?", [$hospitalId])['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $db->update('hospitals', ['status' => $newStatus], 'id = ?', [$hospitalId]);
            
            jsonResponse([
                'success' => true,
                'message' => '医院状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('hospitals', 'id = ?', [$hospitalId]);
            jsonResponse([
                'success' => true,
                'message' => '医院已删除',
                'reload' => true
            ]);
            break;
            
        case 'update_hospital':
            $data = [
                'name' => trim($_POST['name']),
                'level' => $_POST['level'],
                'type' => $_POST['type'],
                'address' => trim($_POST['address']),
                'city' => trim($_POST['city']),
                'phone' => trim($_POST['phone']),
                'website' => trim($_POST['website']),
                'introduction' => trim($_POST['introduction']),
                'status' => $_POST['status']
            ];
            
            if ($hospitalId) {
                $db->update('hospitals', $data, 'id = ?', [$hospitalId]);
                $message = '医院信息已更新';
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('hospitals', $data);
                $message = '医院已添加';
            }
            
            jsonResponse([
                'success' => true,
                'message' => $message,
                'reload' => true
            ]);
            break;
    }
}

// 获取筛选参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$search = trim($_GET['search'] ?? '');
$level = $_GET['level'] ?? '';
$city = $_GET['city'] ?? '';
$status = $_GET['status'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(name LIKE ? OR address LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
}

if ($level) {
    $whereConditions[] = "level = ?";
    $queryParams[] = $level;
}

if ($city) {
    $whereConditions[] = "city = ?";
    $queryParams[] = $city;
}

if ($status) {
    $whereConditions[] = "status = ?";
    $queryParams[] = $status;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalHospitals = $db->fetch("SELECT COUNT(*) as count FROM hospitals {$whereClause}", $queryParams)['count'];
$totalPages = ceil($totalHospitals / $pageSize);

// 获取医院列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$hospitals = $db->fetchAll("
    SELECT h.*,
           (SELECT COUNT(*) FROM doctors WHERE hospital_id = h.id) as doctor_count,
           (SELECT COUNT(*) FROM appointments WHERE hospital_id = h.id) as appointment_count
    FROM hospitals h 
    {$whereClause}
    ORDER BY h.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM hospitals")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE status = 'active'")['count'],
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE status = 'inactive'")['count'],
    'tertiary' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE level = '三甲'")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE DATE(created_at) = CURDATE()")['count']
];

// 获取城市列表
$cities = $db->fetchAll("SELECT DISTINCT city FROM hospitals WHERE city IS NOT NULL AND city != '' ORDER BY city");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>医院管理</h2>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showAddHospitalModal()">
                <i class="fas fa-plus"></i>
                添加医院
            </button>
            <button class="btn btn-secondary" onclick="exportData('hospitals')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总医院数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">正常营业</span>
            <span class="stat-value"><?php echo number_format($stats['active']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">暂停服务</span>
            <span class="stat-value"><?php echo number_format($stats['inactive']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">三甲医院</span>
            <span class="stat-value"><?php echo number_format($stats['tertiary']); ?></span>
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
                <input type="text" name="search" placeholder="搜索医院名称或地址..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="level" class="filter-select">
                    <option value="">全部等级</option>
                    <option value="三甲" <?php echo $level === '三甲' ? 'selected' : ''; ?>>三甲</option>
                    <option value="三乙" <?php echo $level === '三乙' ? 'selected' : ''; ?>>三乙</option>
                    <option value="二甲" <?php echo $level === '二甲' ? 'selected' : ''; ?>>二甲</option>
                    <option value="二乙" <?php echo $level === '二乙' ? 'selected' : ''; ?>>二乙</option>
                    <option value="一甲" <?php echo $level === '一甲' ? 'selected' : ''; ?>>一甲</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="city" class="filter-select">
                    <option value="">全部城市</option>
                    <?php foreach ($cities as $cityItem): ?>
                        <option value="<?php echo h($cityItem['city']); ?>" <?php echo $city === $cityItem['city'] ? 'selected' : ''; ?>>
                            <?php echo h($cityItem['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>停用</option>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/hospitals.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 医院列表 -->
    <div class="table-section">
        <?php if ($hospitals): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>医院信息</th>
                            <th>等级/类型</th>
                            <th>联系方式</th>
                            <th>统计</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hospitals as $hospital): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $hospital['id']; ?>">
                                </td>
                                <td>
                                    <div class="hospital-info">
                                        <h4><?php echo h($hospital['name']); ?></h4>
                                        <p class="address">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo h($hospital['address']); ?>
                                        </p>
                                        <p class="city">
                                            <i class="fas fa-city"></i>
                                            <?php echo h($hospital['city']); ?>
                                        </p>
                                    </div>
                                </td>
                                <td>
                                    <div class="hospital-level">
                                        <span class="level-badge level-<?php echo strtolower($hospital['level']); ?>">
                                            <?php echo h($hospital['level']); ?>
                                        </span>
                                        <span class="type-badge">
                                            <?php echo h($hospital['type']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <?php if ($hospital['phone']): ?>
                                            <p>
                                                <i class="fas fa-phone"></i>
                                                <?php echo h($hospital['phone']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($hospital['website']): ?>
                                            <p>
                                                <i class="fas fa-globe"></i>
                                                <a href="<?php echo h($hospital['website']); ?>" target="_blank">
                                                    <?php echo h(parse_url($hospital['website'], PHP_URL_HOST)); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hospital-stats-compact">
                                        <span class="stat-compact">
                                            <i class="fas fa-user-md"></i>
                                            <?php echo $hospital['doctor_count']; ?>
                                        </span>
                                        <span class="stat-compact">
                                            <i class="fas fa-calendar-check"></i>
                                            <?php echo $hospital['appointment_count']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $hospital['status']; ?>">
                                        <?php echo $hospital['status'] === 'active' ? '正常' : '停用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($hospital['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editHospital(<?php echo $hospital['id']; ?>)" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $hospital['status'] === 'active' ? '停用' : '启用'; ?>">
                                                <i class="fas fa-<?php echo $hospital['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="hospital_id" value="<?php echo $hospital['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除医院 <?php echo h($hospital['name']); ?> 吗？" title="删除">
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
                    echo generatePagination($page, $totalPages, '/admin/hospitals.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3>暂无医院数据</h3>
                <p>没有找到符合条件的医院</p>
                <button class="btn btn-primary" onclick="showAddHospitalModal()">
                    <i class="fas fa-plus"></i>
                    添加医院
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加/编辑医院模态框 -->
<div id="hospitalModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="modalTitle">添加医院</h3>
            <button class="modal-close" onclick="hideHospitalModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="hospitalForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_hospital">
                <input type="hidden" name="hospital_id" id="hospitalId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>医院名称 *</label>
                        <input type="text" name="name" id="hospitalName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>医院等级</label>
                        <select name="level" id="hospitalLevel" required>
                            <option value="">请选择</option>
                            <option value="三甲">三甲</option>
                            <option value="三乙">三乙</option>
                            <option value="二甲">二甲</option>
                            <option value="二乙">二乙</option>
                            <option value="一甲">一甲</option>
                            <option value="专科">专科</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>医院类型</label>
                        <select name="type" id="hospitalType" required>
                            <option value="">请选择</option>
                            <option value="综合医院">综合医院</option>
                            <option value="专科医院">专科医院</option>
                            <option value="中医院">中医院</option>
                            <option value="妇幼保健院">妇幼保健院</option>
                            <option value="精神病院">精神病院</option>
                            <option value="康复医院">康复医院</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>所在城市 *</label>
                        <input type="text" name="city" id="hospitalCity" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>详细地址 *</label>
                    <input type="text" name="address" id="hospitalAddress" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>联系电话</label>
                        <input type="text" name="phone" id="hospitalPhone">
                    </div>
                    
                    <div class="form-group">
                        <label>官方网站</label>
                        <input type="url" name="website" id="hospitalWebsite" placeholder="https://">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>医院简介</label>
                    <textarea name="introduction" id="hospitalIntroduction" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <select name="status" id="hospitalStatus">
                        <option value="active">正常</option>
                        <option value="inactive">停用</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideHospitalModal()">取消</button>
            <button class="btn btn-primary" onclick="saveHospital()">保存</button>
        </div>
    </div>
</div>

<style>
/* 医院管理页面样式 */
.hospital-info h4 {
    margin: 0 0 6px 0;
    font-size: 14px;
    color: #2c3e50;
}

.hospital-info p {
    margin: 2px 0;
    font-size: 12px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.hospital-info i {
    width: 12px;
    text-align: center;
}

.hospital-level {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.level-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-align: center;
}

.level-三甲 { background: #d4edda; color: #155724; }
.level-三乙 { background: #d1ecf1; color: #0c5460; }
.level-二甲 { background: #fff3cd; color: #856404; }
.level-二乙 { background: #f8d7da; color: #721c24; }
.level-一甲 { background: #e2e3e5; color: #383d41; }
.level-专科 { background: #e7e3ff; color: #6f42c1; }

.type-badge {
    padding: 2px 6px;
    background: #f8f9fa;
    color: #6c757d;
    border-radius: 3px;
    font-size: 10px;
    text-align: center;
}

.contact-info p {
    margin: 2px 0;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.contact-info a {
    color: #3498db;
    text-decoration: none;
}

.hospital-stats {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 10px;
}

.hospital-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #9ca3af;
    line-height: 1.2;
}

.hospital-stats i {
    width: 10px;
    text-align: center;
    font-size: 9px;
}

.modal-lg {
    max-width: 800px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
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
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}
</style>

<script>
function showAddHospitalModal() {
    document.getElementById('modalTitle').textContent = '添加医院';
    document.getElementById('hospitalForm').reset();
    document.getElementById('hospitalId').value = '';
    document.getElementById('hospitalModal').style.display = 'block';
}

function editHospital(hospitalId) {
    // 这里应该通过AJAX获取医院详细信息并填充表单
    // 为了演示，直接显示模态框
    document.getElementById('modalTitle').textContent = '编辑医院';
    document.getElementById('hospitalId').value = hospitalId;
    document.getElementById('hospitalModal').style.display = 'block';
}

function hideHospitalModal() {
    document.getElementById('hospitalModal').style.display = 'none';
}

function saveHospital() {
    const form = document.getElementById('hospitalForm');
    submitAjaxForm(form, function(data) {
        if (data.success) {
            hideHospitalModal();
        }
    });
}
</script>

<?php include 'templates/footer.php'; ?>