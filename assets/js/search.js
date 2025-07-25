// 搜索功能JavaScript

$(document).ready(function() {
    initSearch();
});

// 初始化搜索功能
function initSearch() {
    const $searchInput = $('#searchInput');
    const $searchSuggestions = $('#searchSuggestions');
    const $searchForm = $('.search-form');
    
    let searchTimeout;
    let currentSuggestionIndex = -1;
    
    // 搜索输入事件
    $searchInput.on('input', debounce(function() {
        const query = $(this).val().trim();
        
        if (query.length >= 2) {
            fetchSearchSuggestions(query);
        } else {
            hideSuggestions();
        }
    }, 300));
    
    // 键盘导航
    $searchInput.on('keydown', function(e) {
        const $suggestions = $searchSuggestions.find('.suggestion-item');
        const suggestionsCount = $suggestions.length;
        
        switch(e.keyCode) {
            case 38: // 上箭头
                e.preventDefault();
                currentSuggestionIndex = currentSuggestionIndex > 0 ? currentSuggestionIndex - 1 : suggestionsCount - 1;
                highlightSuggestion($suggestions, currentSuggestionIndex);
                break;
                
            case 40: // 下箭头
                e.preventDefault();
                currentSuggestionIndex = currentSuggestionIndex < suggestionsCount - 1 ? currentSuggestionIndex + 1 : 0;
                highlightSuggestion($suggestions, currentSuggestionIndex);
                break;
                
            case 13: // 回车键
                if (currentSuggestionIndex >= 0 && $suggestions.length > 0) {
                    e.preventDefault();
                    selectSuggestion($suggestions.eq(currentSuggestionIndex));
                }
                break;
                
            case 27: // ESC键
                hideSuggestions();
                currentSuggestionIndex = -1;
                break;
        }
    });
    
    // 点击建议项
    $(document).on('click', '.suggestion-item', function() {
        selectSuggestion($(this));
    });
    
    // 失去焦点隐藏建议
    $searchInput.on('blur', function() {
        setTimeout(hideSuggestions, 200);
    });
    
    // 获得焦点重新显示建议
    $searchInput.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length >= 2) {
            fetchSearchSuggestions(query);
        }
    });
    
    // 点击其他地方隐藏建议
    $(document).on('click', function(e) {
        if (!$searchInput.is(e.target) && !$searchSuggestions.is(e.target) && $searchSuggestions.has(e.target).length === 0) {
            hideSuggestions();
        }
    });
    
    // 表单提交
    $searchForm.on('submit', function(e) {
        const query = $searchInput.val().trim();
        if (!query) {
            e.preventDefault();
            showMessage('请输入搜索关键词', 'warning');
            return;
        }
        
        // 记录搜索关键词
        recordSearchKeyword(query);
    });
}

// 获取搜索建议
function fetchSearchSuggestions(query) {
    ajaxRequest({
        url: '/api/search_suggestions.php',
        type: 'GET',
        data: { q: query },
        beforeSend: function() {
            // 不显示全局loading
        },
        success: function(response) {
            if (response.success && response.data) {
                displaySearchSuggestions(response.data);
            } else {
                hideSuggestions();
            }
        },
        error: function() {
            hideSuggestions();
        }
    });
}

// 显示搜索建议
function displaySearchSuggestions(suggestions) {
    const $searchSuggestions = $('#searchSuggestions');
    
    if (!suggestions || suggestions.length === 0) {
        hideSuggestions();
        return;
    }
    
    let html = '';
    
    // 分类显示建议
    const categories = {
        'hospitals': { name: '医院', icon: 'fas fa-hospital' },
        'doctors': { name: '医生', icon: 'fas fa-user-md' },
        'diseases': { name: '疾病', icon: 'fas fa-book-medical' },
        'articles': { name: '资讯', icon: 'fas fa-newspaper' },
        'keywords': { name: '热门搜索', icon: 'fas fa-search' }
    };
    
    Object.keys(categories).forEach(function(categoryKey) {
        if (suggestions[categoryKey] && suggestions[categoryKey].length > 0) {
            const category = categories[categoryKey];
            html += `<div class="suggestion-category">
                        <div class="category-header">
                            <i class="${category.icon}"></i>
                            <span>${category.name}</span>
                        </div>`;
            
            suggestions[categoryKey].forEach(function(item) {
                html += createSuggestionItem(item, categoryKey);
            });
            
            html += '</div>';
        }
    });
    
    if (html) {
        $searchSuggestions.html(html).addClass('active');
        currentSuggestionIndex = -1;
    } else {
        hideSuggestions();
    }
}

// 创建建议项HTML
function createSuggestionItem(item, category) {
    let itemHtml = '';
    let itemText = '';
    let itemUrl = '';
    
    switch(category) {
        case 'hospitals':
            itemText = item.name;
            itemUrl = `/hospitals/detail.php?id=${item.id}`;
            itemHtml = `
                <div class="suggestion-item" data-url="${itemUrl}" data-text="${itemText}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${highlightText(item.name, $('#searchInput').val())}</div>
                        <div class="suggestion-meta">${item.level} • ${item.city}</div>
                    </div>
                    <div class="suggestion-type">医院</div>
                </div>
            `;
            break;
            
        case 'doctors':
            itemText = item.name;
            itemUrl = `/doctors/detail.php?id=${item.id}`;
            itemHtml = `
                <div class="suggestion-item" data-url="${itemUrl}" data-text="${itemText}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${highlightText(item.name, $('#searchInput').val())}</div>
                        <div class="suggestion-meta">${item.title} • ${item.hospital_name}</div>
                    </div>
                    <div class="suggestion-type">医生</div>
                </div>
            `;
            break;
            
        case 'diseases':
            itemText = item.name;
            itemUrl = `/diseases/detail.php?id=${item.id}`;
            itemHtml = `
                <div class="suggestion-item" data-url="${itemUrl}" data-text="${itemText}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${highlightText(item.name, $('#searchInput').val())}</div>
                        <div class="suggestion-meta">${item.category_name}</div>
                    </div>
                    <div class="suggestion-type">疾病</div>
                </div>
            `;
            break;
            
        case 'articles':
            itemText = item.title;
            itemUrl = `/news/detail.php?id=${item.id}`;
            itemHtml = `
                <div class="suggestion-item" data-url="${itemUrl}" data-text="${itemText}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${highlightText(item.title, $('#searchInput').val())}</div>
                        <div class="suggestion-meta">${formatTime(item.publish_time)}</div>
                    </div>
                    <div class="suggestion-type">资讯</div>
                </div>
            `;
            break;
            
        case 'keywords':
            itemText = item.keyword;
            itemUrl = `/search.php?q=${encodeURIComponent(item.keyword)}`;
            itemHtml = `
                <div class="suggestion-item" data-url="${itemUrl}" data-text="${itemText}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${highlightText(item.keyword, $('#searchInput').val())}</div>
                        <div class="suggestion-meta">${formatNumber(item.search_count)}次搜索</div>
                    </div>
                    <div class="suggestion-type">热搜</div>
                </div>
            `;
            break;
    }
    
    return itemHtml;
}

// 高亮匹配文本
function highlightText(text, query) {
    if (!query) return text;
    
    const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

// 转义正则表达式特殊字符
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// 高亮建议项
function highlightSuggestion($suggestions, index) {
    $suggestions.removeClass('highlighted');
    if (index >= 0 && index < $suggestions.length) {
        $suggestions.eq(index).addClass('highlighted');
        
        // 更新输入框内容
        const selectedText = $suggestions.eq(index).data('text');
        if (selectedText) {
            $('#searchInput').val(selectedText);
        }
    }
}

// 选择建议项
function selectSuggestion($suggestion) {
    const url = $suggestion.data('url');
    const text = $suggestion.data('text');
    
    if (url && url.startsWith('/search.php')) {
        // 搜索页面，提交表单
        $('#searchInput').val(text);
        $('.search-form').submit();
    } else if (url) {
        // 直接跳转到详情页
        window.location.href = url;
    } else {
        // 填充搜索框并提交
        $('#searchInput').val(text);
        $('.search-form').submit();
    }
    
    hideSuggestions();
}

// 隐藏建议
function hideSuggestions() {
    $('#searchSuggestions').removeClass('active').empty();
    currentSuggestionIndex = -1;
}

// 记录搜索关键词
function recordSearchKeyword(keyword) {
    // 异步记录，不影响搜索体验
    $.ajax({
        url: '/api/record_search.php',
        type: 'POST',
        data: { keyword: keyword },
        // 静默处理，不显示错误
        error: function() {}
    });
}

// 高级搜索功能
function initAdvancedSearch() {
    const $advancedBtn = $('.advanced-search-btn');
    const $advancedPanel = $('.advanced-search-panel');
    const $advancedForm = $('.advanced-search-form');
    
    // 显示/隐藏高级搜索面板
    $advancedBtn.on('click', function() {
        $advancedPanel.toggleClass('active');
    });
    
    // 高级搜索表单提交
    $advancedForm.on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serializeArray();
        const searchParams = new URLSearchParams();
        
        formData.forEach(function(item) {
            if (item.value) {
                searchParams.append(item.name, item.value);
            }
        });
        
        window.location.href = '/search.php?' + searchParams.toString();
    });
    
    // 重置高级搜索
    $('.reset-advanced-search').on('click', function() {
        $advancedForm[0].reset();
    });
}

// 搜索历史功能
function initSearchHistory() {
    const HISTORY_KEY = 'search_history';
    const MAX_HISTORY = 10;
    
    // 获取搜索历史
    function getSearchHistory() {
        try {
            const history = localStorage.getItem(HISTORY_KEY);
            return history ? JSON.parse(history) : [];
        } catch (e) {
            return [];
        }
    }
    
    // 保存搜索历史
    function saveSearchHistory(keyword) {
        try {
            let history = getSearchHistory();
            
            // 移除重复项
            history = history.filter(item => item !== keyword);
            
            // 添加到开头
            history.unshift(keyword);
            
            // 限制数量
            if (history.length > MAX_HISTORY) {
                history = history.slice(0, MAX_HISTORY);
            }
            
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        } catch (e) {
            // 存储失败，忽略
        }
    }
    
    // 显示搜索历史
    function displaySearchHistory() {
        const history = getSearchHistory();
        if (history.length === 0) return;
        
        let html = '<div class="suggestion-category">';
        html += '<div class="category-header">';
        html += '<i class="fas fa-history"></i>';
        html += '<span>搜索历史</span>';
        html += '<button class="clear-history" type="button" title="清除历史">';
        html += '<i class="fas fa-trash"></i>';
        html += '</button>';
        html += '</div>';
        
        history.forEach(function(keyword) {
            html += `
                <div class="suggestion-item" data-text="${keyword}" data-url="/search.php?q=${encodeURIComponent(keyword)}">
                    <div class="suggestion-content">
                        <div class="suggestion-title">${keyword}</div>
                    </div>
                    <div class="suggestion-type">历史</div>
                </div>
            `;
        });
        
        html += '</div>';
        
        $('#searchSuggestions').html(html).addClass('active');
    }
    
    // 清除搜索历史
    $(document).on('click', '.clear-history', function(e) {
        e.stopPropagation();
        localStorage.removeItem(HISTORY_KEY);
        hideSuggestions();
        showMessage('搜索历史已清除', 'info');
    });
    
    // 输入框获得焦点时显示历史
    $('#searchInput').on('focus', function() {
        const query = $(this).val().trim();
        if (!query) {
            displaySearchHistory();
        }
    });
    
    // 搜索时保存历史
    $('.search-form').on('submit', function() {
        const keyword = $('#searchInput').val().trim();
        if (keyword) {
            saveSearchHistory(keyword);
        }
    });
}

// 智能搜索提示
function initSmartSearchTips() {
    const tips = [
        '试试搜索"心内科"找到相关医生',
        '输入症状如"头痛"查找相关疾病',
        '搜索医院名称快速找到联系方式',
        '使用"北京 + 科室"精确搜索',
        '搜索医生姓名预约挂号'
    ];
    
    const $searchInput = $('#searchInput');
    let tipIndex = 0;
    
    function showNextTip() {
        if ($searchInput.val() === '' && !$searchInput.is(':focus')) {
            $searchInput.attr('placeholder', tips[tipIndex]);
            tipIndex = (tipIndex + 1) % tips.length;
        }
    }
    
    // 每5秒切换一次提示
    setInterval(showNextTip, 5000);
    
    // 初始显示
    showNextTip();
    
    // 聚焦时恢复默认提示
    $searchInput.on('focus', function() {
        $(this).attr('placeholder', '搜索医院、医生、疾病...');
    });
    
    // 失焦时继续轮播
    $searchInput.on('blur', function() {
        if ($(this).val() === '') {
            setTimeout(showNextTip, 1000);
        }
    });
}

// 搜索结果统计
function trackSearchResults(query, resultCount, category) {
    // 异步发送统计数据
    $.ajax({
        url: '/api/search_stats.php',
        type: 'POST',
        data: {
            query: query,
            result_count: resultCount,
            category: category
        },
        // 静默处理
        error: function() {}
    });
}

// 初始化所有搜索相关功能
$(document).ready(function() {
    initAdvancedSearch();
    initSearchHistory();
    initSmartSearchTips();
});

// 搜索页面专用功能
if (window.location.pathname.includes('/search.php')) {
    $(document).ready(function() {
        // 高亮搜索关键词
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('q');
        
        if (query) {
            highlightSearchResults(query);
            
            // 统计搜索结果
            const resultCount = $('.search-result-item').length;
            const category = urlParams.get('category') || 'all';
            trackSearchResults(query, resultCount, category);
        }
    });
}

// 高亮搜索结果中的关键词
function highlightSearchResults(query) {
    const $resultItems = $('.search-result-item');
    
    $resultItems.each(function() {
        const $item = $(this);
        const $title = $item.find('.result-title');
        const $content = $item.find('.result-content');
        
        if ($title.length) {
            $title.html(highlightText($title.text(), query));
        }
        
        if ($content.length) {
            $content.html(highlightText($content.text(), query));
        }
    });
}