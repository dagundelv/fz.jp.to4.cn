<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "分类管理 - 管理员后台";

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $categoryId = intval($_POST['category_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $currentStatus = $db->fetch("SELECT status FROM categories WHERE id = ?", [$categoryId])['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $db->update('categories', ['status' => $newStatus], 'id = ?', [$categoryId]);
            
            jsonResponse([
                'success' => true,
                'message' => '分类状态已更新',
                'reload' => true
            ]);
            break;
            
        case 'delete':
            // 检查是否有子分类
            $hasChildren = $db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?", [$categoryId])['count'];
            if ($hasChildren > 0) {
                jsonResponse([
                    'success' => false,
                    'message' => '该分类下还有子分类，无法删除'
                ]);
                break;
            }
            
            // 检查是否有关联内容
            $hasContent = false;
            $tables = ['articles', 'doctors', 'diseases', 'qa_questions'];
            foreach ($tables as $table) {
                $count = $db->fetch("SELECT COUNT(*) as count FROM {$table} WHERE category_id = ?", [$categoryId])['count'];
                if ($count > 0) {
                    $hasContent = true;
                    break;
                }
            }
            
            if ($hasContent) {
                jsonResponse([
                    'success' => false,
                    'message' => '该分类下还有关联内容，无法删除'
                ]);
                break;
            }
            
            $db->delete('categories', 'id = ?', [$categoryId]);
            jsonResponse([
                'success' => true,
                'message' => '分类已删除',
                'reload' => true
            ]);
            break;
            
        case 'update_category':
            $data = [
                'name' => trim($_POST['name']),
                'slug' => trim($_POST['slug']),
                'parent_id' => intval($_POST['parent_id'] ?? 0),
                'description' => trim($_POST['description']),
                'sort_order' => intval($_POST['sort_order']),
                'status' => $_POST['status']
            ];
            
            // 验证slug唯一性
            $existingCategory = $db->fetch("SELECT id FROM categories WHERE slug = ? AND id != ?", [$data['slug'], $categoryId]);
            if ($existingCategory) {
                jsonResponse([
                    'success' => false,
                    'message' => '分类别名已存在，请使用其他别名'
                ]);
                break;
            }
            
            if ($categoryId) {
                // 检查父分类循环引用
                if ($data['parent_id'] > 0) {
                    $parentCategory = $db->fetch("SELECT parent_id FROM categories WHERE id = ?", [$data['parent_id']]);
                    if ($parentCategory && $parentCategory['parent_id'] == $categoryId) {
                        jsonResponse([
                            'success' => false,
                            'message' => '不能设置为循环父分类'
                        ]);
                        break;
                    }
                }
                
                $db->update('categories', $data, 'id = ?', [$categoryId]);
                $message = '分类信息已更新';
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('categories', $data);
                $message = '分类已添加';
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
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$parent = $_GET['parent'] ?? '';

// 构建查询条件
$whereConditions = [];
$queryParams = [];

if ($search) {
    $whereConditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm]);
}

if ($status) {
    $whereConditions[] = "c.status = ?";
    $queryParams[] = $status;
}

if ($parent !== '') {
    if ($parent === '0') {
        $whereConditions[] = "c.parent_id = 0";
    } else {
        $whereConditions[] = "c.parent_id = ?";
        $queryParams[] = $parent;
    }
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取分类列表（树形结构）
$categories = $db->fetchAll("
    SELECT c.*, p.name as parent_name,
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
           (SELECT COUNT(*) FROM articles WHERE category_id = c.id) as articles_count,
           (SELECT COUNT(*) FROM doctors WHERE category_id = c.id) as doctors_count,
           (SELECT COUNT(*) FROM diseases WHERE category_id = c.id) as diseases_count,
           (SELECT COUNT(*) FROM qa_questions WHERE category_id = c.id) as questions_count
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id
    {$whereClause}
    ORDER BY c.parent_id, c.sort_order, c.name
", $queryParams);

// 统计数据
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM categories")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
    'inactive' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE status = 'inactive'")['count'],
    'parent' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id = 0")['count'],
    'children' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id > 0")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM categories WHERE DATE(created_at) = CURDATE()")['count']
];

// 获取父分类列表（用于筛选）
$parentCategories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order, name");

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>分类管理</h2>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showAddCategoryModal()">
                <i class="fas fa-plus"></i>
                添加分类
            </button>
            <button class="btn btn-secondary" onclick="exportData('categories')">
                <i class="fas fa-download"></i>
                导出数据
            </button>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-label">总分类数</span>
            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">启用分类</span>
            <span class="stat-value"><?php echo number_format($stats['active']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">禁用分类</span>
            <span class="stat-value"><?php echo number_format($stats['inactive']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">父分类</span>
            <span class="stat-value"><?php echo number_format($stats['parent']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">子分类</span>
            <span class="stat-value"><?php echo number_format($stats['children']); ?></span>
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
                <input type="text" name="search" placeholder="搜索分类名称或描述..." 
                       value="<?php echo h($search); ?>" class="search-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>启用</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="parent" class="filter-select">
                    <option value="">全部分类</option>
                    <option value="0" <?php echo $parent === '0' ? 'selected' : ''; ?>>顶级分类</option>
                    <?php foreach ($parentCategories as $parentCategory): ?>
                        <option value="<?php echo $parentCategory['id']; ?>" <?php echo $parent == $parentCategory['id'] ? 'selected' : ''; ?>>
                            <?php echo h($parentCategory['name']); ?> 的子分类
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    搜索
                </button>
                <a href="/admin/categories.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    清除
                </a>
            </div>
        </form>
    </div>
    
    <!-- 分类列表 -->
    <div class="table-section">
        <?php if ($categories): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>分类信息</th>
                            <th>层级关系</th>
                            <th>内容统计</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?php echo $category['id']; ?>">
                                </td>
                                <td>
                                    <div class="category-info">
                                        <div class="category-header">
                                            <?php if ($category['parent_id'] > 0): ?>
                                                <span class="category-indent">└─</span>
                                            <?php endif; ?>
                                            <h4><?php echo h($category['name']); ?></h4>
                                            <span class="category-slug"><?php echo h($category['slug']); ?></span>
                                        </div>
                                        <?php if ($category['description']): ?>
                                            <p class="description">
                                                <?php echo h(truncate($category['description'], 80)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hierarchy-info">
                                        <?php if ($category['parent_id'] > 0): ?>
                                            <div class="parent-category">
                                                <i class="fas fa-folder"></i>
                                                <?php echo h($category['parent_name']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="root-category">
                                                <i class="fas fa-folder-open"></i>
                                                顶级分类
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($category['children_count'] > 0): ?>
                                            <div class="children-count">
                                                <i class="fas fa-sitemap"></i>
                                                <?php echo $category['children_count']; ?> 个子分类
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="content-stats">
                                        <?php if ($category['articles_count'] > 0): ?>
                                            <div class="stat-item">
                                                <i class="fas fa-newspaper"></i>
                                                <?php echo $category['articles_count']; ?> 文章
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($category['doctors_count'] > 0): ?>
                                            <div class="stat-item">
                                                <i class="fas fa-user-md"></i>
                                                <?php echo $category['doctors_count']; ?> 医生
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($category['diseases_count'] > 0): ?>
                                            <div class="stat-item">
                                                <i class="fas fa-virus"></i>
                                                <?php echo $category['diseases_count']; ?> 疾病
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($category['questions_count'] > 0): ?>
                                            <div class="stat-item">
                                                <i class="fas fa-question-circle"></i>
                                                <?php echo $category['questions_count']; ?> 问题
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($category['articles_count'] == 0 && $category['doctors_count'] == 0 && $category['diseases_count'] == 0 && $category['questions_count'] == 0): ?>
                                            <span class="no-content">暂无内容</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="sort-order">
                                        <span class="sort-number"><?php echo $category['sort_order']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $category['status']; ?>">
                                        <?php echo $category['status'] === 'active' ? '启用' : '禁用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date('Y-m-d H:i', strtotime($category['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editCategory(<?php echo $category['id']; ?>)" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn-action btn-toggle" 
                                                    title="<?php echo $category['status'] === 'active' ? '禁用' : '启用'; ?>">
                                                <i class="fas fa-<?php echo $category['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" data-ajax style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete delete-btn" 
                                                    data-confirm="确定要删除分类《<?php echo h($category['name']); ?>》吗？" title="删除">
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
            
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="fas fa-folder"></i>
                </div>
                <h3>暂无分类数据</h3>
                <p>没有找到符合条件的分类</p>
                <button class="btn btn-primary" onclick="showAddCategoryModal()">
                    <i class="fas fa-plus"></i>
                    添加分类
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加/编辑分类模态框 -->
<div id="categoryModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="modalTitle">添加分类</h3>
            <button class="modal-close" onclick="hideCategoryModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="categoryForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>分类名称 *</label>
                        <input type="text" name="name" id="categoryName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>分类别名 *</label>
                        <input type="text" name="slug" id="categorySlug" required placeholder="英文字母、数字、短横线">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>父分类</label>
                        <select name="parent_id" id="categoryParent">
                            <option value="0">无（顶级分类）</option>
                            <?php foreach ($parentCategories as $parentCategory): ?>
                                <option value="<?php echo $parentCategory['id']; ?>">
                                    <?php echo h($parentCategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort_order" id="categorySortOrder" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>分类描述</label>
                    <textarea name="description" id="categoryDescription" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <select name="status" id="categoryStatus">
                        <option value="active">启用</option>
                        <option value="inactive">禁用</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideCategoryModal()">取消</button>
            <button class="btn btn-primary" onclick="saveCategory()">保存</button>
        </div>
    </div>
</div>

<style>
/* 分类管理页面样式 */
.category-info .category-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.category-indent {
    color: #bdc3c7;
    font-family: monospace;
    font-size: 14px;
}

.category-info h4 {
    margin: 0;
    font-size: 14px;
    color: #2c3e50;
}

.category-slug {
    padding: 2px 6px;
    background: #e9ecef;
    color: #6c757d;
    border-radius: 3px;
    font-size: 11px;
    font-family: monospace;
}

.category-info .description {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
    line-height: 1.4;
}

.hierarchy-info {
    font-size: 12px;
}

.parent-category,
.root-category,
.children-count {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 4px;
    color: #7f8c8d;
}

.parent-category {
    color: #3498db;
}

.root-category {
    color: #27ae60;
}

.children-count {
    color: #95a5a6;
}

.hierarchy-info i {
    width: 12px;
    text-align: center;
}

.content-stats {
    font-size: 10px;
}

.content-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-bottom: 2px;
    color: #9ca3af;
    line-height: 1.2;
}

.content-stats i {
    width: 10px;
    text-align: center;
    font-size: 9px;
}

.no-content {
    color: #bdc3c7;
    font-style: italic;
}

.sort-order {
    text-align: center;
}

.sort-number {
    display: inline-block;
    padding: 4px 8px;
    background: #f8f9fa;
    border-radius: 4px;
    font-weight: 500;
    color: #495057;
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
    font-family: inherit;
}
</style>

<script>
function showAddCategoryModal() {
    document.getElementById('modalTitle').textContent = '添加分类';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(categoryId) {
    document.getElementById('modalTitle').textContent = '编辑分类';
    document.getElementById('categoryId').value = categoryId;
    document.getElementById('categoryModal').style.display = 'block';
    
    // 这里应该通过AJAX获取分类详细信息并填充表单
}

function hideCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    const name = document.getElementById('categoryName').value.trim();
    const slug = document.getElementById('categorySlug').value.trim();
    
    if (!name) {
        showAdminMessage('请输入分类名称', 'error');
        return;
    }
    
    if (!slug) {
        showAdminMessage('请输入分类别名', 'error');
        return;
    }
    
    // 验证slug格式
    if (!/^[a-zA-Z0-9-_]+$/.test(slug)) {
        showAdminMessage('分类别名只能包含字母、数字、短横线和下划线', 'error');
        return;
    }
    
    submitAjaxForm(form, function(data) {
        if (data.success) {
            hideCategoryModal();
        }
    });
}

// 自动生成slug
document.getElementById('categoryName').addEventListener('input', function() {
    const name = this.value.trim();
    const slugInput = document.getElementById('categorySlug');
    
    if (name && !slugInput.value.trim()) {
        // 简单的拼音转换规则（实际项目中可能需要更完善的转换）
        let slug = name.replace(/\s+/g, '-').toLowerCase();
        slug = slug.replace(/[^a-zA-Z0-9-_\u4e00-\u9fa5]/g, '');
        slugInput.value = slug;
    }
});
</script>

<?php include 'templates/footer.php'; ?>