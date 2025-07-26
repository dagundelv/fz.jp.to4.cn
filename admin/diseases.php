<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "疾病管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $diseaseId = intval($_POST['disease_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM diseases WHERE id = ?", [$diseaseId])['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $db->update('diseases', ['status' => $newStatus], 'id = ?', [$diseaseId]);
            
            jsonResponse([
                'success' => true,
                'message' => '疾病状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            $db->delete('diseases', 'id = ?', [$diseaseId]);
            jsonResponse([
                'success' => true,
                'message' => '疾病已删除',
                'reload' => true
            ]);
            break;
            
        case 'update_disease':
            $data = [
                'name' => trim($_POST['name']),
                'category_id' => intval($_POST['category_id']),
                'symptoms' => trim($_POST['symptoms']),
                'causes' => trim($_POST['causes']),
                'treatment' => trim($_POST['treatment']),
                'prevention' => trim($_POST['prevention']),
                'description' => trim($_POST['description']),
                'status' => $_POST['status']
            ];
            
            if ($diseaseId) {
                $db->update('diseases', $data, 'id = ?', [$diseaseId]);
                $message = '疾病信息已更新';
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('diseases', $data);
                $message = '疾病已添加';
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
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(d.name LIKE ? OR d.symptoms LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
}

if ($category) {
    $whereConditions[] = "d.category_id = ?";
    $queryParams[] = $category;
}

if ($status) {
    $whereConditions[] = "d.status = ?";
    $queryParams[] = $status;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$totalDiseases = $db->fetch("SELECT COUNT(*) as count FROM diseases d {$whereClause}", $queryParams)['count'];
$totalPages = ceil($totalDiseases / $pageSize);

// 获取疾病列表
$offset = ($page - 1) * $pageSize;
$listParams = array_merge($queryParams, [$pageSize, $offset]);

$diseases = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    {$whereClause}
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
", $listParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM diseases")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM diseases WHERE status = 'active'")['count'],
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM diseases WHERE status = 'inactive'")['count'],
    'views' => $db->fetch("SELECT SUM(view_count) as total FROM diseases")['total'] ?? 0,
    'today' => $db->fetch("SELECT COUNT(*) as count FROM diseases WHERE DATE(created_at) = CURDATE()")['count']
];

// 获取分类列表
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>疾病管理</h2>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showAddDiseaseModal()">
                <i class="fas fa-plus"></i>
                添加疾病
            </button>
            <button class="btn btn-secondary" onclick="exportData('diseases')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总疾病数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">已发布</span>
            <span class="stat-value"><?php echo number_format($stats['active']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">未发布</span>
            <span class="stat-value"><?php echo number_format($stats['inactive']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">总浏览量</span>
            <span class="stat-value"><?php echo formatNumber($stats['views']); ?></span>
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
                <input type="text" name="search" placeholder="搜索疾病名称或症状..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
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
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>已发布</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>未发布</option>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/diseases.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 疾病列表 -->
    <div class="table-section">
        <?php if ($diseases): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>疾病信息</th>
                            <th>分类</th>
                            <th>主要症状</th>
                            <th>浏览量</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diseases as $disease): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $disease['id']; ?>">
                                </td>
                                <td>
                                    <div class="disease-info">
                                        <h4>
                                            <a href="/diseases/detail.php?id=<?php echo $disease['id']; ?>" target="_blank">
                                                <?php echo h($disease['name']); ?>
                                            </a>
                                        </h4>
                                        <?php if ($disease['description']): ?>
                                            <p class="description">
                                                <?php echo h(truncate($disease['description'], 80)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge">
                                        <?php echo h($disease['category_name'] ?? '未分类'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="symptoms">
                                        <?php echo h(truncate($disease['symptoms'] ?? '', 60)); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="view-count">
                                        <i class="fas fa-eye"></i>
                                        <?php echo formatNumber($disease['view_count']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $disease['status']; ?>">
                                        <?php echo $disease['status'] === 'active' ? '已发布' : '未发布'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($disease['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editDisease(<?php echo $disease['id']; ?>)" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="disease_id" value="<?php echo $disease['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $disease['status'] === 'active' ? '取消发布' : '发布'; ?>">
                                                <i class="fas fa-<?php echo $disease['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="disease_id" value="<?php echo $disease['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除疾病《<?php echo h($disease['name']); ?>》吗？" title="删除">
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
                    echo generatePagination($page, $totalPages, '/admin/diseases.php', $params);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-virus"></i>
                </div>
                <h3>暂无疾病数据</h3>
                <p>没有找到符合条件的疾病</p>
                <button class="btn btn-primary" onclick="showAddDiseaseModal()">
                    <i class="fas fa-plus"></i>
                    添加疾病
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加/编辑疾病模态框 -->
<div id="diseaseModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h3 id="modalTitle">添加疾病</h3>
            <button class="modal-close" onclick="hideDiseaseModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="diseaseForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_disease">
                <input type="hidden" name="disease_id" id="diseaseId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>疾病名称 *</label>
                        <input type="text" name="name" id="diseaseName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>疾病分类</label>
                        <select name="category_id" id="diseaseCategory" required>
                            <option value="">请选择分类</option>
                            <?php foreach ($categories as $categoryItem): ?>
                                <option value="<?php echo $categoryItem['id']; ?>">
                                    <?php echo h($categoryItem['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>状态</label>
                        <select name="status" id="diseaseStatus">
                            <option value="active">已发布</option>
                            <option value="inactive">未发布</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>疾病描述</label>
                    <textarea name="description" id="diseaseDescription" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>主要症状</label>
                    <textarea name="symptoms" id="diseaseSymptoms" rows="4" placeholder="请详细描述疾病的主要症状..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>病因分析</label>
                    <textarea name="causes" id="diseaseCauses" rows="4" placeholder="请描述疾病的主要原因..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>治疗方法</label>
                    <textarea name="treatment" id="diseaseTreatment" rows="4" placeholder="请描述疾病的治疗方法..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>预防措施</label>
                    <textarea name="prevention" id="diseasePrevention" rows="4" placeholder="请描述疾病的预防措施..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideDiseaseModal()">取消</button>
            <button class="btn btn-primary" onclick="saveDisease()">保存</button>
        </div>
    </div>
</div>

<style>
/* 疾病管理页面样式 */
.disease-info h4 {
    margin: 0 0 6px 0;
    font-size: 14px;
    color: #2c3e50;
}

.disease-info h4 a {
    color: inherit;
    text-decoration: none;
}

.disease-info h4 a:hover {
    color: #3498db;
}

.disease-info .description {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.4;
}

.symptoms {
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.4;
}

.view-count {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #7f8c8d;
}

.modal-xl {
    max-width: 1000px;
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
    font-family: inherit;
}
</style>

<script>
function showAddDiseaseModal() {
    document.getElementById('modalTitle').textContent = '添加疾病';
    document.getElementById('diseaseForm').reset();
    document.getElementById('diseaseId').value = '';
    document.getElementById('diseaseModal').style.display = 'block';
}

function editDisease(diseaseId) {
    document.getElementById('modalTitle').textContent = '编辑疾病';
    document.getElementById('diseaseId').value = diseaseId;
    document.getElementById('diseaseModal').style.display = 'block';
    
    // 这里应该通过AJAX获取疾病详细信息并填充表单
}

function hideDiseaseModal() {
    document.getElementById('diseaseModal').style.display = 'none';
}

function saveDisease() {
    const form = document.getElementById('diseaseForm');
    submitAjaxForm(form, function(data) {
        if (data.success) {
            hideDiseaseModal();
        }
    });
}
</script>

<?php include 'templates/footer.php'; ?>