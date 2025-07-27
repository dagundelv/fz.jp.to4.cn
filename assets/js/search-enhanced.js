/**
 * 增强型搜索功能
 * 包括智能提示、搜索历史、热门搜索等功能
 */

class EnhancedSearch {
    constructor(options = {}) {
        this.options = {
            inputSelector: '#searchInput',
            suggestionsSelector: '#searchSuggestions',
            formSelector: '.search-form',
            minLength: 2,
            maxSuggestions: 8,
            debounceDelay: 300,
            cacheTimeout: 5 * 60 * 1000, // 5分钟
            enableHistory: true,
            enableHotSearches: true,
            apiEndpoint: '/api/search-suggestions.php',
            hotSearchEndpoint: '/api/hot-searches.php',
            ...options
        };
        
        this.cache = new Map();
        this.searchHistory = this.loadSearchHistory();
        this.currentRequest = null;
        this.selectedIndex = -1;
        this.suggestions = [];
        
        this.init();
    }
    
    init() {
        this.input = document.querySelector(this.options.inputSelector);
        this.suggestionsContainer = document.querySelector(this.options.suggestionsSelector);
        this.form = document.querySelector(this.options.formSelector);
        
        if (!this.input) return;
        
        this.bindEvents();
        this.createSuggestionsContainer();
        
        // 加载热门搜索
        if (this.options.enableHotSearches) {
            this.loadHotSearches();
        }
    }
    
    bindEvents() {
        // 输入事件
        this.input.addEventListener('input', this.debounce(this.handleInput.bind(this), this.options.debounceDelay));
        
        // 键盘事件
        this.input.addEventListener('keydown', this.handleKeydown.bind(this));
        
        // 焦点事件
        this.input.addEventListener('focus', this.handleFocus.bind(this));
        this.input.addEventListener('blur', this.handleBlur.bind(this));
        
        // 表单提交
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
        
        // 点击外部隐藏建议
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.suggestionsContainer.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    createSuggestionsContainer() {
        if (!this.suggestionsContainer) {
            this.suggestionsContainer = document.createElement('div');
            this.suggestionsContainer.className = 'search-suggestions';
            this.suggestionsContainer.id = 'searchSuggestions';
            this.input.parentNode.appendChild(this.suggestionsContainer);
        }
        
        this.suggestionsContainer.style.display = 'none';
    }
    
    handleInput(e) {
        const query = e.target.value.trim();
        
        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }
        
        this.getSuggestions(query);
    }
    
    handleKeydown(e) {
        if (!this.suggestionsContainer || this.suggestionsContainer.style.display === 'none') {
            return;
        }
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.moveSelection(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.moveSelection(-1);
                break;
            case 'Enter':
                e.preventDefault();
                this.selectSuggestion();
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    handleFocus(e) {
        const query = e.target.value.trim();
        if (query.length >= this.options.minLength) {
            this.getSuggestions(query);
        } else if (this.options.enableHistory && this.searchHistory.length > 0) {
            this.showSearchHistory();
        }
    }
    
    handleBlur(e) {
        // 延迟隐藏，允许点击建议项
        setTimeout(() => {
            this.hideSuggestions();
        }, 200);
    }
    
    handleSubmit(e) {
        const query = this.input.value.trim();
        if (query && this.options.enableHistory) {
            this.addToHistory(query);
        }
    }
    
    getSuggestions(query) {
        // 检查缓存
        const cached = this.getCachedSuggestions(query);
        if (cached) {
            this.showSuggestions(cached, query);
            return;
        }
        
        // 取消之前的请求
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        // 发送新请求
        this.currentRequest = fetch(`${this.options.apiEndpoint}?q=${encodeURIComponent(query)}&limit=${this.options.maxSuggestions}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.cacheSuggestions(query, data.suggestions);
                    this.showSuggestions(data.suggestions, query);
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('搜索建议获取失败:', error);
                }
            })
            .finally(() => {
                this.currentRequest = null;
            });
    }
    
    showSuggestions(suggestions, query) {
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        this.suggestions = suggestions;
        this.selectedIndex = -1;
        
        const html = this.renderSuggestions(suggestions, query);
        this.suggestionsContainer.innerHTML = html;
        this.suggestionsContainer.style.display = 'block';
        
        // 绑定点击事件
        this.bindSuggestionEvents();
    }
    
    renderSuggestions(suggestions, query) {
        let html = '<div class="suggestions-list">';
        
        suggestions.forEach((suggestion, index) => {
            const highlighted = this.highlightMatch(suggestion.text, query);
            const icon = this.getTypeIcon(suggestion.type);
            
            html += `
                <div class="suggestion-item" data-index="${index}" data-url="${suggestion.url || ''}">
                    <div class="suggestion-icon">
                        <i class="${icon}"></i>
                    </div>
                    <div class="suggestion-content">
                        <div class="suggestion-text">${highlighted}</div>
                        <div class="suggestion-type">${suggestion.category || this.getTypeName(suggestion.type)}</div>
                    </div>
                    <div class="suggestion-action">
                        <i class="fas fa-arrow-up-right"></i>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    renderSearchHistory() {
        if (!this.searchHistory.length) return '';
        
        let html = '<div class="search-history">';
        html += '<div class="history-header">';
        html += '<span class="history-title"><i class="fas fa-history"></i> 搜索历史</span>';
        html += '<button class="clear-history" onclick="enhancedSearch.clearHistory()"><i class="fas fa-times"></i></button>';
        html += '</div>';
        html += '<div class="history-list">';
        
        this.searchHistory.slice(0, 5).forEach((item, index) => {
            html += `
                <div class="history-item" data-query="${item}">
                    <div class="history-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="history-text">${item}</div>
                    <div class="history-action">
                        <button class="remove-history" onclick="enhancedSearch.removeFromHistory('${item}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }
    
    showSearchHistory() {
        if (!this.searchHistory.length) return;
        
        const html = this.renderSearchHistory();
        this.suggestionsContainer.innerHTML = html;
        this.suggestionsContainer.style.display = 'block';
        
        this.bindHistoryEvents();
    }
    
    bindSuggestionEvents() {
        const items = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        items.forEach((item, index) => {
            item.addEventListener('click', () => {
                this.selectedIndex = index;
                this.selectSuggestion();
            });
            
            item.addEventListener('mouseenter', () => {
                this.setSelectedIndex(index);
            });
        });
    }
    
    bindHistoryEvents() {
        const items = this.suggestionsContainer.querySelectorAll('.history-item');
        items.forEach(item => {
            item.addEventListener('click', () => {
                const query = item.dataset.query;
                this.input.value = query;
                this.hideSuggestions();
                this.submitSearch(query);
            });
        });
    }
    
    moveSelection(direction) {
        const items = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        if (!items.length) return;
        
        this.selectedIndex += direction;
        
        if (this.selectedIndex < -1) {
            this.selectedIndex = items.length - 1;
        } else if (this.selectedIndex >= items.length) {
            this.selectedIndex = -1;
        }
        
        this.setSelectedIndex(this.selectedIndex);
    }
    
    setSelectedIndex(index) {
        const items = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        
        items.forEach((item, i) => {
            item.classList.toggle('selected', i === index);
        });
        
        this.selectedIndex = index;
    }
    
    selectSuggestion() {
        if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
            const suggestion = this.suggestions[this.selectedIndex];
            
            if (suggestion.url) {
                window.location.href = suggestion.url;
            } else {
                this.input.value = suggestion.text;
                this.submitSearch(suggestion.text);
            }
        } else {
            // 如果没有选中建议，提交当前输入
            this.submitSearch(this.input.value);
        }
        
        this.hideSuggestions();
    }
    
    submitSearch(query) {
        if (this.options.enableHistory) {
            this.addToHistory(query);
        }
        
        if (this.form) {
            this.input.value = query;
            this.form.submit();
        } else {
            window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
        }
    }
    
    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = 'none';
        }
        this.selectedIndex = -1;
    }
    
    highlightMatch(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    getTypeIcon(type) {
        const icons = {
            doctor: 'fas fa-user-md',
            hospital: 'fas fa-hospital',
            disease: 'fas fa-stethoscope',
            article: 'fas fa-newspaper',
            question: 'fas fa-question-circle',
            category: 'fas fa-list'
        };
        return icons[type] || 'fas fa-search';
    }
    
    getTypeName(type) {
        const names = {
            doctor: '医生',
            hospital: '医院',
            disease: '疾病',
            article: '资讯',
            question: '问答',
            category: '科室'
        };
        return names[type] || '搜索';
    }
    
    // 缓存管理
    cacheSuggestions(query, suggestions) {
        this.cache.set(query.toLowerCase(), {
            data: suggestions,
            timestamp: Date.now()
        });
        
        // 清理过期缓存
        this.cleanCache();
    }
    
    getCachedSuggestions(query) {
        const cached = this.cache.get(query.toLowerCase());
        if (cached && (Date.now() - cached.timestamp) < this.options.cacheTimeout) {
            return cached.data;
        }
        return null;
    }
    
    cleanCache() {
        const now = Date.now();
        for (const [key, value] of this.cache.entries()) {
            if (now - value.timestamp > this.options.cacheTimeout) {
                this.cache.delete(key);
            }
        }
    }
    
    // 搜索历史管理
    loadSearchHistory() {
        if (!this.options.enableHistory) return [];
        
        try {
            const history = localStorage.getItem('search_history');
            return history ? JSON.parse(history) : [];
        } catch (e) {
            return [];
        }
    }
    
    saveSearchHistory() {
        if (!this.options.enableHistory) return;
        
        try {
            localStorage.setItem('search_history', JSON.stringify(this.searchHistory));
        } catch (e) {
            console.warn('无法保存搜索历史:', e);
        }
    }
    
    addToHistory(query) {
        if (!query || !this.options.enableHistory) return;
        
        // 移除重复项
        this.searchHistory = this.searchHistory.filter(item => item !== query);
        
        // 添加到开头
        this.searchHistory.unshift(query);
        
        // 限制历史记录数量
        this.searchHistory = this.searchHistory.slice(0, 20);
        
        this.saveSearchHistory();
    }
    
    removeFromHistory(query) {
        this.searchHistory = this.searchHistory.filter(item => item !== query);
        this.saveSearchHistory();
        
        // 更新显示
        if (this.suggestionsContainer.style.display !== 'none') {
            this.showSearchHistory();
        }
    }
    
    clearHistory() {
        this.searchHistory = [];
        this.saveSearchHistory();
        this.hideSuggestions();
    }
    
    // 热门搜索
    loadHotSearches() {
        fetch(this.options.hotSearchEndpoint)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.hotSearches = data.data;
                    this.displayHotSearches();
                }
            })
            .catch(error => {
                console.error('热门搜索加载失败:', error);
            });
    }
    
    displayHotSearches() {
        const container = document.querySelector('.hot-searches');
        if (!container || !this.hotSearches) return;
        
        let html = '';
        this.hotSearches.slice(0, 8).forEach((search, index) => {
            const rankClass = index < 3 ? 'top-rank' : '';
            html += `
                <a href="/search.php?q=${encodeURIComponent(search.keyword)}" 
                   class="hot-search-item ${rankClass}" 
                   title="${search.keyword}">
                    <span class="rank">${index + 1}</span>
                    <span class="keyword">${search.keyword}</span>
                    ${search.is_hot ? '<span class="hot-badge">热</span>' : ''}
                </a>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // 工具方法
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// 全局实例
let enhancedSearch;

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    enhancedSearch = new EnhancedSearch({
        inputSelector: '#searchInput, .search-input',
        suggestionsSelector: '#searchSuggestions, .search-suggestions'
    });
    
    // 初始化其他搜索框
    document.querySelectorAll('.search-input').forEach(input => {
        if (input.id !== 'searchInput') {
            new EnhancedSearch({
                inputSelector: `#${input.id}`,
                suggestionsSelector: `#${input.id}_suggestions`
            });
        }
    });
});