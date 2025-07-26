// 管理员后台JavaScript功能

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    initAdminFeatures();
});

// 初始化管理员功能
function initAdminFeatures() {
    // 初始化数据表格
    initDataTables();
    
    // 初始化图表
    initCharts();
    
    // 初始化文件上传
    initFileUpload();
    
    // 初始化富文本编辑器
    initRichEditor();
    
    // 初始化AJAX表单
    initAjaxForms();
    
    // 初始化删除确认
    initDeleteConfirm();
}

// 初始化数据表格
function initDataTables() {
    if (typeof DataTable !== 'undefined') {
        $('.data-table').DataTable({
            language: {
                url: '/assets/js/dataTables.chinese.json'
            },
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']]
        });
    }
}

// 初始化图表
function initCharts() {
    // 用户注册趋势图
    const userChartCanvas = document.getElementById('userChart');
    if (userChartCanvas && typeof Chart !== 'undefined') {
        const ctx = userChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月'],
                datasets: [{
                    label: '新增用户',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // 文章访问量饼图
    const articleChartCanvas = document.getElementById('articleChart');
    if (articleChartCanvas && typeof Chart !== 'undefined') {
        const ctx = articleChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['健康资讯', '疾病预防', '养生保健', '医学研究'],
                datasets: [{
                    data: [30, 25, 25, 20],
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#f39c12',
                        '#27ae60'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// 初始化文件上传
function initFileUpload() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        const preview = area.querySelector('.upload-preview');
        
        // 拖拽上传
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0], input, preview);
            }
        });
        
        // 点击上传
        area.addEventListener('click', function() {
            input.click();
        });
        
        // 文件选择
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0], input, preview);
            }
        });
    });
}

// 处理文件上传
function handleFileUpload(file, input, preview) {
    // 验证文件类型
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showAdminMessage('只允许上传图片文件', 'error');
        return;
    }
    
    // 验证文件大小（5MB）
    if (file.size > 5 * 1024 * 1024) {
        showAdminMessage('文件大小不能超过5MB', 'error');
        return;
    }
    
    // 显示预览
    const reader = new FileReader();
    reader.onload = function(e) {
        if (preview) {
            preview.innerHTML = `<img src="${e.target.result}" alt="预览图片">`;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
    
    // 上传文件
    uploadFile(file);
}

// 上传文件到服务器
function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    fetch('/admin/api/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAdminMessage('文件上传成功', 'success');
        } else {
            showAdminMessage(data.message || '文件上传失败', 'error');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showAdminMessage('文件上传失败', 'error');
    });
}

// 初始化富文本编辑器
function initRichEditor() {
    const editors = document.querySelectorAll('.rich-editor');
    
    editors.forEach(editor => {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                target: editor,
                language: 'zh_CN',
                height: 400,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                         alignleft aligncenter alignright alignjustify | \
                         bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px }'
            });
        }
    });
}

// AJAX表单提交
function submitAjaxForm(form, callback) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = '处理中...';
    
    fetch(form.action, {
        method: form.method || 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAdminMessage(data.message, 'success');
            if (callback) callback(data);
        } else {
            showAdminMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Form submit error:', error);
        showAdminMessage('操作失败，请稍后重试', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// 批量操作
function batchOperation(action, ids) {
    if (!ids || ids.length === 0) {
        showAdminMessage('请选择要操作的项目', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('ids', JSON.stringify(ids));
    
    fetch('/admin/api/batch.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAdminMessage(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAdminMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Batch operation error:', error);
        showAdminMessage('批量操作失败', 'error');
    });
}

// 导出数据
function exportData(type, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = `/admin/api/export.php?type=${type}&${queryString}`;
    
    // 创建隐藏的下载链接
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAdminMessage('数据导出已开始，请稍候...', 'info');
}

// 搜索功能
function initSearch() {
    const searchInput = document.getElementById('adminSearch');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const keyword = this.value.trim();
            performSearch(keyword);
        }, 500);
    });
}

// 执行搜索
function performSearch(keyword) {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) return;
    
    if (!keyword) {
        searchResults.style.display = 'none';
        return;
    }
    
    fetch('/admin/api/search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ keyword: keyword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.results);
        }
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

// 显示搜索结果
function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) return;
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="no-results">没有找到相关内容</div>';
    } else {
        let html = '<div class="search-results-list">';
        results.forEach(item => {
            html += `
                <div class="search-result-item">
                    <h4><a href="${item.url}">${item.title}</a></h4>
                    <p>${item.description}</p>
                    <span class="result-type">${item.type}</span>
                </div>
            `;
        });
        html += '</div>';
        searchResults.innerHTML = html;
    }
    
    searchResults.style.display = 'block';
}

// 实时数据更新
function startRealTimeUpdates() {
    setInterval(() => {
        updateDashboardStats();
    }, 30000); // 每30秒更新一次
}

// 更新控制台统计数据
function updateDashboardStats() {
    fetch('/admin/api/stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatsDisplay(data.stats);
        }
    })
    .catch(error => {
        console.error('Stats update error:', error);
    });
}

// 更新统计显示
function updateStatsDisplay(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = stats[key];
        }
    });
}

// 快捷键支持
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S 保存
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form');
        if (form) {
            form.submit();
        }
    }
    
    // ESC 关闭模态框
    if (e.key === 'Escape') {
        hideConfirmModal();
        hideAdminMessage();
    }
});

// 初始化所有功能
document.addEventListener('DOMContentLoaded', function() {
    initSearch();
    startRealTimeUpdates();
});

// 显示管理员消息
function showAdminMessage(message, type = 'info') {
    // 移除现有消息
    const existingMessage = document.querySelector('.admin-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // 创建消息元素
    const messageDiv = document.createElement('div');
    messageDiv.className = `admin-message admin-message-${type}`;
    messageDiv.innerHTML = `
        <i class="fas fa-${getMessageIcon(type)}"></i>
        <span>${message}</span>
        <button class="close-btn" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // 添加到页面
    document.body.appendChild(messageDiv);
    
    // 自动隐藏
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// 获取消息图标
function getMessageIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// 初始化AJAX表单
function initAjaxForms() {
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.hasAttribute('data-ajax')) {
            e.preventDefault();
            submitAjaxForm(form, function(data) {
                if (data.reload) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            });
        }
    });
}

// 初始化删除确认
function initDeleteConfirm() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
            const btn = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
            const confirmMessage = btn.getAttribute('data-confirm') || '确定要删除吗？';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });
}

// 隐藏管理员消息
function hideAdminMessage() {
    const message = document.querySelector('.admin-message');
    if (message) {
        message.remove();
    }
}