<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/user/history.php'));
    exit;
}

// 设置页面信息
$pageTitle = "浏览历史 - " . SITE_NAME;
$pageDescription = "查看您的浏览历史记录";
$pageKeywords = "浏览历史,访问记录,查看历史";

// 添加页面特定的CSS
$pageCSS = ['/assets/css/user.css'];

include '../templates/header.php';
?>

<div class="history-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '个人中心', 'url' => '/user/profile.php'],
                ['title' => '浏览历史']
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
                        <i class="fas fa-history"></i>
                        浏览历史
                    </h2>
                    <div class="header-actions">
                        <button class="btn btn-outline" id="clearAllBtn">
                            <i class="fas fa-trash"></i>
                            清空历史
                        </button>
                    </div>
                </div>
                
                <!-- 历史类型筛选 -->
                <div class="history-filter">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-type="all">
                            <i class="fas fa-th-large"></i>
                            全部记录
                        </button>
                        <button class="filter-tab" data-type="doctor">
                            <i class="fas fa-user-md"></i>
                            医生
                        </button>
                        <button class="filter-tab" data-type="hospital">
                            <i class="fas fa-hospital"></i>
                            医院
                        </button>
                        <button class="filter-tab" data-type="article">
                            <i class="fas fa-newspaper"></i>
                            文章
                        </button>
                        <button class="filter-tab" data-type="question">
                            <i class="fas fa-question-circle"></i>
                            问答
                        </button>
                        <button class="filter-tab" data-type="disease">
                            <i class="fas fa-procedures"></i>
                            疾病
                        </button>
                    </div>
                    
                    <div class="filter-options">
                        <select id="timeRange" class="form-control">
                            <option value="all">全部时间</option>
                            <option value="today">今天</option>
                            <option value="week">本周</option>
                            <option value="month">本月</option>
                        </select>
                    </div>
                </div>
                
                <!-- 历史记录列表 -->
                <div class="history-container" id="historyContainer">
                    <div class="loading-placeholder">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            正在加载历史记录...
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
let currentTimeRange = 'all';
let currentPage = 1;

$(document).ready(function() {
    // 初始化加载
    loadHistory();
    
    // 筛选标签切换
    $('.filter-tab').on('click', function() {
        const type = $(this).data('type');
        
        if (type === currentType) return;
        
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');
        
        currentType = type;
        currentPage = 1;
        
        loadHistory();
    });
    
    // 时间范围筛选
    $('#timeRange').on('change', function() {
        currentTimeRange = $(this).val();
        currentPage = 1;
        loadHistory();
    });
    
    // 清空历史
    $('#clearAllBtn').on('click', function() {
        if (confirm('确定要清空所有浏览历史吗？此操作无法撤销。')) {
            clearHistory();
        }
    });
    
    // 删除单条记录
    $(document).on('click', '.delete-history-btn', function() {
        const item = $(this).closest('.history-item');
        const itemType = item.data('type');
        const itemId = item.data('id');
        
        if (confirm('确定要删除这条历史记录吗？')) {
            deleteHistoryItem(itemType, itemId, item);
        }
    });
});

// 加载历史记录
function loadHistory(page = 1) {
    const container = $('#historyContainer');
    
    if (page === 1) {
        container.html(`
            <div class="loading-placeholder">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    正在加载历史记录...
                </div>
            </div>
        `);
    }
    
    $.ajax({
        url: '/api/user-history.php',
        method: 'GET',
        data: {
            type: currentType === 'all' ? '' : currentType,
            time_range: currentTimeRange,
            page: page,
            limit: 15
        },
        success: function(response) {
            if (response.success) {
                if (page === 1) {
                    if (response.data.length === 0) {
                        showEmptyState(container);
                    } else {
                        renderHistory(response.data, container);
                        renderPagination(response);
                    }
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

// 渲染历史记录
function renderHistory(historyItems, container) {
    if (historyItems.length === 0) {
        showEmptyState(container);
        return;
    }
    
    let html = '<div class="history-timeline">';
    let currentDate = '';
    
    historyItems.forEach(item => {
        const itemDate = formatDate(item.last_viewed_at);
        
        // 如果是新的日期，添加日期分隔符
        if (itemDate !== currentDate) {
            currentDate = itemDate;
            html += `<div class="date-separator">${itemDate}</div>`;
        }
        
        html += renderHistoryItem(item);
    });
    
    html += '</div>';
    container.html(html);
}

// 渲染单个历史记录项
function renderHistoryItem(item) {
    const itemData = item.item_data;
    const itemType = item.item_type;
    
    if (!itemData) return '';
    
    let itemHtml = `
        <div class="history-item" data-type="${itemType}" data-id="${item.item_id}">
            <div class="item-time">
                ${formatTime(item.last_viewed_at)}
            </div>
            <div class="item-content">
    `;
    
    switch (itemType) {
        case 'doctor':
            itemHtml += `
                <div class="item-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="item-info">
                    <h4><a href="/doctors/detail.php?id=${itemData.id}">${itemData.name}</a></h4>
                    <p class="item-meta">${itemData.title || ''} | ${itemData.hospital_name || ''}</p>
                    <p class="item-desc">${itemData.category_name || ''}</p>
                </div>
            `;
            break;
            
        case 'hospital':
            itemHtml += `
                <div class="item-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="item-info">
                    <h4><a href="/hospitals/detail.php?id=${itemData.id}">${itemData.name}</a></h4>
                    <p class="item-meta">${itemData.level || ''} | ${itemData.type || ''}</p>
                    <p class="item-desc">${itemData.address || ''}</p>
                </div>
            `;
            break;
            
        case 'article':
            itemHtml += `
                <div class="item-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="item-info">
                    <h4><a href="/news/detail.php?id=${itemData.id}">${itemData.title}</a></h4>
                    <p class="item-meta">${itemData.category_name || ''}</p>
                    <p class="item-desc">${itemData.summary || ''}</p>
                </div>
            `;
            break;
            
        case 'question':
            itemHtml += `
                <div class="item-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="item-info">
                    <h4><a href="/qa/detail.php?id=${itemData.id}">${itemData.title}</a></h4>
                    <p class="item-meta">提问者: ${itemData.username || '匿名'}</p>
                    <p class="item-desc">${itemData.content ? itemData.content.substring(0, 100) + '...' : ''}</p>
                </div>
            `;
            break;
            
        case 'disease':
            itemHtml += `
                <div class="item-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div class="item-info">
                    <h4><a href="/diseases/detail.php?id=${itemData.id}">${itemData.name}</a></h4>
                    <p class="item-meta">${itemData.category_name || ''}</p>
                    <p class="item-desc">${itemData.summary || ''}</p>
                </div>
            `;
            break;
    }
    
    itemHtml += `
            </div>
            <div class="item-actions">
                <span class="view-count">浏览 ${item.view_count} 次</span>
                <button class="delete-history-btn" title="删除记录">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    return itemHtml;
}

// 显示空状态
function showEmptyState(container) {
    const typeNames = {
        'all': '',
        'doctor': '医生',
        'hospital': '医院',
        'article': '文章',
        'question': '问答',
        'disease': '疾病'
    };
    
    const typeName = typeNames[currentType] || '';
    
    container.html(`
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-history"></i>
            </div>
            <h3>暂无${typeName}浏览记录</h3>
            <p>您还没有浏览过任何${typeName || '内容'}，快去探索吧！</p>
            <div class="empty-actions">
                <a href="/doctors/" class="btn btn-primary">浏览医生</a>
                <a href="/hospitals/" class="btn btn-outline">查看医院</a>
                <a href="/news/" class="btn btn-outline">阅读文章</a>
            </div>
        </div>
    `);
}

// 删除历史记录项
function deleteHistoryItem(itemType, itemId, itemElement) {
    $.ajax({
        url: '/api/user-history.php',
        method: 'POST',
        data: {
            action: 'delete',
            item_type: itemType,
            item_id: itemId
        },
        success: function(response) {
            if (response.success) {
                itemElement.fadeOut(300, function() {
                    $(this).remove();
                    
                    // 检查是否需要显示空状态
                    if ($('.history-item').length === 0) {
                        showEmptyState($('#historyContainer'));
                    }
                });
                showSuccess('已删除历史记录');
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('删除失败，请稍后重试');
        }
    });
}

// 清空历史记录
function clearHistory() {
    $.ajax({
        url: '/api/user-history.php',
        method: 'POST',
        data: {
            action: 'clear',
            type: currentType === 'all' ? '' : currentType
        },
        success: function(response) {
            if (response.success) {
                showEmptyState($('#historyContainer'));
                showSuccess('历史记录已清空');
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('清空失败，请稍后重试');
        }
    });
}

// 格式化时间
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('zh-CN', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// 格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return '今天';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return '昨天';
    } else {
        return date.toLocaleDateString('zh-CN', {
            month: 'long',
            day: 'numeric'
        });
    }
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