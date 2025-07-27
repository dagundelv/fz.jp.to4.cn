<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/user/favorites.php'));
    exit;
}

// 设置页面信息
$pageTitle = "我的收藏 - " . SITE_NAME;
$pageDescription = "管理您收藏的医生、医院、文章等内容";
$pageKeywords = "收藏夹,我的收藏,医生收藏,医院收藏";

// 添加页面特定的CSS
$pageCSS = ['/assets/css/user.css'];

include '../templates/header.php';
?>

<div class="favorites-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '个人中心', 'url' => '/user/profile.php'],
                ['title' => '我的收藏']
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="user-layout">
            <!-- 侧边导航 -->
            <?php include '../templates/user-sidebar.php'; ?>
            
            <!-- 主要内容 -->
            <main class="user-main">
                <div class="page-header">
                    <h2>
                        <i class="fas fa-heart"></i>
                        我的收藏
                    </h2>
                    <div class="favorites-stats">
                        <span class="stat-item">
                            <span class="stat-number" id="totalCount">-</span>
                            <span class="stat-label">项收藏</span>
                        </span>
                    </div>
                </div>
                
                <!-- 收藏类型筛选 -->
                <div class="favorites-filter">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-type="all">
                            <i class="fas fa-th-large"></i>
                            全部收藏
                            <span class="count" id="count-all">0</span>
                        </button>
                        <button class="filter-tab" data-type="doctor">
                            <i class="fas fa-user-md"></i>
                            医生
                            <span class="count" id="count-doctor">0</span>
                        </button>
                        <button class="filter-tab" data-type="hospital">
                            <i class="fas fa-hospital"></i>
                            医院
                            <span class="count" id="count-hospital">0</span>
                        </button>
                        <button class="filter-tab" data-type="article">
                            <i class="fas fa-newspaper"></i>
                            文章
                            <span class="count" id="count-article">0</span>
                        </button>
                        <button class="filter-tab" data-type="question">
                            <i class="fas fa-question-circle"></i>
                            问答
                            <span class="count" id="count-question">0</span>
                        </button>
                        <button class="filter-tab" data-type="disease">
                            <i class="fas fa-procedures"></i>
                            疾病
                            <span class="count" id="count-disease">0</span>
                        </button>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-outline" id="selectAllBtn">
                            <i class="fas fa-check-square"></i>
                            全选
                        </button>
                        <button class="btn btn-danger" id="deleteSelectedBtn" disabled>
                            <i class="fas fa-trash"></i>
                            删除选中
                        </button>
                    </div>
                </div>
                
                <!-- 收藏列表 -->
                <div class="favorites-container" id="favoritesContainer">
                    <div class="loading-placeholder">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            正在加载收藏...
                        </div>
                    </div>
                </div>
                
                <!-- 分页 -->
                <div class="pagination-wrapper" id="paginationWrapper" style="display: none;">
                    <!-- 分页内容将由JavaScript动态生成 -->
                </div>
            </main>
        </div>
    </div>
</div>

<script>
let currentType = 'all';
let currentPage = 1;
let selectedItems = new Set();
let favoriteCounts = {};

$(document).ready(function() {
    // 初始化加载
    loadFavorites();
    loadFavoriteCounts();
    
    // 筛选标签切换
    $('.filter-tab').on('click', function() {
        const type = $(this).data('type');
        
        if (type === currentType) return;
        
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');
        
        currentType = type;
        currentPage = 1;
        selectedItems.clear();
        updateBatchActions();
        
        loadFavorites();
    });
    
    // 全选/取消全选
    $('#selectAllBtn').on('click', function() {
        const allSelected = selectedItems.size > 0;
        
        if (allSelected) {
            selectedItems.clear();
            $('.favorite-item .item-checkbox').prop('checked', false);
            $(this).html('<i class="fas fa-check-square"></i> 全选');
        } else {
            $('.favorite-item').each(function() {
                const itemId = $(this).data('id');
                const itemType = $(this).data('type');
                selectedItems.add(`${itemType}_${itemId}`);
                $(this).find('.item-checkbox').prop('checked', true);
            });
            $(this).html('<i class="fas fa-square"></i> 取消全选');
        }
        
        updateBatchActions();
    });
    
    // 删除选中
    $('#deleteSelectedBtn').on('click', function() {
        if (selectedItems.size === 0) return;
        
        if (confirm(`确定要删除选中的 ${selectedItems.size} 项收藏吗？`)) {
            batchDeleteFavorites();
        }
    });
    
    // 收藏项选择
    $(document).on('change', '.item-checkbox', function() {
        const item = $(this).closest('.favorite-item');
        const itemId = item.data('id');
        const itemType = item.data('type');
        const key = `${itemType}_${itemId}`;
        
        if ($(this).is(':checked')) {
            selectedItems.add(key);
        } else {
            selectedItems.delete(key);
        }
        
        updateBatchActions();
    });
    
    // 取消收藏
    $(document).on('click', '.unfavorite-btn', function() {
        const item = $(this).closest('.favorite-item');
        const itemId = item.data('id');
        const itemType = item.data('type');
        
        if (confirm('确定要取消收藏吗？')) {
            unfavoriteItem(itemType, itemId, item);
        }
    });
});

// 加载收藏列表
function loadFavorites(page = 1) {
    const container = $('#favoritesContainer');
    
    if (page === 1) {
        container.html(`
            <div class="loading-placeholder">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    正在加载收藏...
                </div>
            </div>
        `);
    }
    
    $.ajax({
        url: '/api/favorites.php',
        method: 'GET',
        data: {
            type: currentType === 'all' ? '' : currentType,
            page: page,
            limit: 12
        },
        success: function(response) {
            if (response.success) {
                if (page === 1) {
                    if (response.data.length === 0) {
                        showEmptyState(container);
                    } else {
                        renderFavorites(response.data, container);
                        renderPagination(response);
                    }
                } else {
                    appendFavorites(response.data, container);
                }
                currentPage = page;
            } else {
                showError('加载失败: ' + response.message);
            }
        },
        error: function() {
            showError('网络错误，请稍后重试');
        }
    });
}

// 加载收藏统计
function loadFavoriteCounts() {
    $.ajax({
        url: '/api/favorites.php',
        method: 'GET',
        data: { action: 'counts' },
        success: function(response) {
            if (response.success) {
                favoriteCounts = response.data;
                updateCountDisplay();
            }
        }
    });
}

// 更新计数显示
function updateCountDisplay() {
    let total = 0;
    for (const [type, count] of Object.entries(favoriteCounts)) {
        $(`#count-${type}`).text(count);
        total += parseInt(count);
    }
    $('#count-all').text(total);
    $('#totalCount').text(total);
}

// 渲染收藏列表
function renderFavorites(favorites, container) {
    if (favorites.length === 0) {
        showEmptyState(container);
        return;
    }
    
    let html = '<div class="favorites-grid">';
    
    favorites.forEach(favorite => {
        html += renderFavoriteItem(favorite);
    });
    
    html += '</div>';
    container.html(html);
}

// 渲染单个收藏项
function renderFavoriteItem(favorite) {
    const item = favorite.item_data;
    const itemType = favorite.item_type;
    
    if (!item) return '';
    
    let itemHtml = `
        <div class="favorite-item" data-type="${itemType}" data-id="${favorite.item_id}">
            <div class="item-header">
                <label class="checkbox-container">
                    <input type="checkbox" class="item-checkbox">
                    <span class="checkmark"></span>
                </label>
                <button class="unfavorite-btn" title="取消收藏">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="item-content">
    `;
    
    switch (itemType) {
        case 'doctor':
            itemHtml += `
                <div class="item-avatar">
                    ${item.avatar ? 
                        `<img src="${item.avatar}" alt="${item.name}">` :
                        `<div class="avatar-placeholder"><i class="fas fa-user-md"></i></div>`
                    }
                </div>
                <div class="item-info">
                    <h4><a href="/doctors/detail.php?id=${item.id}">${item.name}</a></h4>
                    <p class="item-meta">${item.title || ''}</p>
                    <p class="item-desc">${item.hospital_name || ''}</p>
                    <div class="item-tags">
                        ${item.category_name ? `<span class="tag">${item.category_name}</span>` : ''}
                    </div>
                </div>
            `;
            break;
            
        case 'hospital':
            itemHtml += `
                <div class="item-avatar">
                    <div class="avatar-placeholder"><i class="fas fa-hospital"></i></div>
                </div>
                <div class="item-info">
                    <h4><a href="/hospitals/detail.php?id=${item.id}">${item.name}</a></h4>
                    <p class="item-meta">${item.level || '三级甲等'} | ${item.type || '综合医院'}</p>
                    <p class="item-desc">${item.address || ''}</p>
                    <div class="item-tags">
                        ${item.city ? `<span class="tag">${item.city}</span>` : ''}
                    </div>
                </div>
            `;
            break;
            
        case 'article':
            itemHtml += `
                <div class="item-avatar">
                    ${item.featured_image ? 
                        `<img src="${item.featured_image}" alt="${item.title}">` :
                        `<div class="avatar-placeholder"><i class="fas fa-newspaper"></i></div>`
                    }
                </div>
                <div class="item-info">
                    <h4><a href="/news/detail.php?id=${item.id}">${item.title}</a></h4>
                    <p class="item-meta">${formatDate(item.created_at)}</p>
                    <p class="item-desc">${item.summary || ''}</p>
                    <div class="item-tags">
                        ${item.category_name ? `<span class="tag">${item.category_name}</span>` : ''}
                    </div>
                </div>
            `;
            break;
            
        case 'question':
            itemHtml += `
                <div class="item-avatar">
                    <div class="avatar-placeholder"><i class="fas fa-question-circle"></i></div>
                </div>
                <div class="item-info">
                    <h4><a href="/qa/detail.php?id=${item.id}">${item.title}</a></h4>
                    <p class="item-meta">提问者: ${item.username}</p>
                    <p class="item-desc">${item.content ? item.content.substring(0, 100) + '...' : ''}</p>
                    <div class="item-tags">
                        ${item.category_name ? `<span class="tag">${item.category_name}</span>` : ''}
                        ${item.answer_count > 0 ? `<span class="tag answered">${item.answer_count}个回答</span>` : ''}
                    </div>
                </div>
            `;
            break;
            
        case 'disease':
            itemHtml += `
                <div class="item-avatar">
                    <div class="avatar-placeholder"><i class="fas fa-procedures"></i></div>
                </div>
                <div class="item-info">
                    <h4><a href="/diseases/detail.php?id=${item.id}">${item.name}</a></h4>
                    <p class="item-meta">${item.category_name || ''}</p>
                    <p class="item-desc">${item.summary || ''}</p>
                </div>
            `;
            break;
    }
    
    itemHtml += `
            </div>
            <div class="item-footer">
                <span class="favorite-time">收藏于 ${formatTime(favorite.created_at)}</span>
            </div>
        </div>
    `;
    
    return itemHtml;
}

// 显示空状态
function showEmptyState(container) {
    const typeNames = {
        'all': '内容',
        'doctor': '医生',
        'hospital': '医院',
        'article': '文章',
        'question': '问答',
        'disease': '疾病'
    };
    
    const typeName = typeNames[currentType] || '内容';
    
    container.html(`
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h3>暂无收藏${typeName}</h3>
            <p>您还没有收藏任何${typeName}，快去收藏感兴趣的内容吧！</p>
            <div class="empty-actions">
                <a href="/doctors/" class="btn btn-primary">浏览医生</a>
                <a href="/hospitals/" class="btn btn-outline">查看医院</a>
                <a href="/news/" class="btn btn-outline">阅读文章</a>
            </div>
        </div>
    `);
}

// 取消收藏
function unfavoriteItem(itemType, itemId, itemElement) {
    $.ajax({
        url: '/api/favorites.php',
        method: 'POST',
        data: {
            action: 'remove',
            item_type: itemType,
            item_id: itemId
        },
        success: function(response) {
            if (response.success) {
                itemElement.fadeOut(300, function() {
                    $(this).remove();
                    // 更新计数
                    if (favoriteCounts[itemType]) {
                        favoriteCounts[itemType]--;
                    }
                    updateCountDisplay();
                    
                    // 如果当前页没有内容了，检查是否需要显示空状态
                    if ($('.favorite-item').length === 0) {
                        showEmptyState($('#favoritesContainer'));
                    }
                });
                showSuccess('已取消收藏');
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('操作失败，请稍后重试');
        }
    });
}

// 批量删除收藏
function batchDeleteFavorites() {
    const items = Array.from(selectedItems).map(key => {
        const [type, id] = key.split('_');
        return { type, id };
    });
    
    $.ajax({
        url: '/api/favorites.php',
        method: 'POST',
        data: {
            action: 'batch_remove',
            items: JSON.stringify(items)
        },
        success: function(response) {
            if (response.success) {
                // 移除已删除的项目
                selectedItems.forEach(key => {
                    const [type, id] = key.split('_');
                    $(`.favorite-item[data-type="${type}"][data-id="${id}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                });
                
                selectedItems.clear();
                updateBatchActions();
                loadFavoriteCounts();
                
                showSuccess(`已删除 ${items.length} 项收藏`);
                
                setTimeout(() => {
                    if ($('.favorite-item').length === 0) {
                        showEmptyState($('#favoritesContainer'));
                    }
                }, 300);
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('批量删除失败，请稍后重试');
        }
    });
}

// 更新批量操作按钮状态
function updateBatchActions() {
    const hasSelection = selectedItems.size > 0;
    
    $('#deleteSelectedBtn').prop('disabled', !hasSelection);
    
    if (selectedItems.size === $('.favorite-item').length && $('.favorite-item').length > 0) {
        $('#selectAllBtn').html('<i class="fas fa-square"></i> 取消全选');
    } else {
        $('#selectAllBtn').html('<i class="fas fa-check-square"></i> 全选');
    }
}

// 格式化时间
function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return '1天前';
    } else if (diffDays < 7) {
        return diffDays + '天前';
    } else if (diffDays < 30) {
        return Math.ceil(diffDays / 7) + '周前';
    } else {
        return Math.ceil(diffDays / 30) + '个月前';
    }
}

// 格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.getFullYear() + '年' + (date.getMonth() + 1) + '月' + date.getDate() + '日';
}

// 显示成功消息
function showSuccess(message) {
    showMessage(message, 'success');
}

// 显示错误消息
function showError(message) {
    showMessage(message, 'error');
}

// 显示消息
function showMessage(message, type = 'info') {
    const toast = $(`<div class="message-toast message-${type}">${message}</div>`);
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php include '../templates/footer.php'; ?>