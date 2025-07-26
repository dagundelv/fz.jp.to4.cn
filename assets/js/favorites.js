/**
 * 收藏功能 JavaScript 工具类
 */
class FavoritesManager {
    constructor() {
        this.apiUrl = '/api/favorites.php';
        this.init();
    }
    
    init() {
        // 初始化收藏按钮事件
        this.bindFavoriteButtons();
        
        // 检查用户登录状态
        this.checkLoginStatus();
    }
    
    /**
     * 绑定收藏按钮事件
     */
    bindFavoriteButtons() {
        $(document).on('click', '.favorite-btn', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(e.currentTarget);
            const itemType = $btn.data('type');
            const itemId = $btn.data('id');
            
            if (!itemType || !itemId) {
                this.showMessage('收藏参数错误', 'error');
                return;
            }
            
            this.toggleFavorite(itemType, itemId, $btn);
        });
    }
    
    /**
     * 检查登录状态
     */
    checkLoginStatus() {
        // 这里可以添加检查用户是否登录的逻辑
        // 如果未登录，禁用收藏按钮
    }
    
    /**
     * 切换收藏状态
     */
    async toggleFavorite(itemType, itemId, $btn) {
        if ($btn.hasClass('loading')) {
            return;
        }
        
        $btn.addClass('loading');
        const originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i>');
        
        try {
            const response = await this.makeRequest('POST', {
                action: 'toggle',
                item_type: itemType,
                item_id: itemId
            });
            
            if (response.success) {
                this.updateFavoriteButton($btn, response.favorited);
                this.showMessage(response.message, 'success');
                
                // 更新收藏计数
                this.updateFavoriteCount(response.total_favorites);
                
                // 触发自定义事件
                $(document).trigger('favoriteChanged', {
                    itemType: itemType,
                    itemId: itemId,
                    favorited: response.favorited,
                    totalFavorites: response.total_favorites
                });
            } else {
                this.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('收藏操作失败:', error);
            this.showMessage('操作失败，请稍后重试', 'error');
        } finally {
            $btn.removeClass('loading');
            if ($btn.hasClass('loading')) {
                $btn.html(originalText);
            }
        }
    }
    
    /**
     * 添加收藏
     */
    async addFavorite(itemType, itemId) {
        try {
            const response = await this.makeRequest('POST', {
                action: 'add',
                item_type: itemType,
                item_id: itemId
            });
            
            if (response.success) {
                this.showMessage(response.message, 'success');
                this.updateFavoriteCount(response.total_favorites);
                return true;
            } else {
                this.showMessage(response.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('添加收藏失败:', error);
            this.showMessage('添加收藏失败', 'error');
            return false;
        }
    }
    
    /**
     * 移除收藏
     */
    async removeFavorite(itemType, itemId) {
        try {
            const response = await this.makeRequest('POST', {
                action: 'remove',
                item_type: itemType,
                item_id: itemId
            });
            
            if (response.success) {
                this.showMessage(response.message, 'success');
                this.updateFavoriteCount(response.total_favorites);
                return true;
            } else {
                this.showMessage(response.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('移除收藏失败:', error);
            this.showMessage('移除收藏失败', 'error');
            return false;
        }
    }
    
    /**
     * 检查收藏状态
     */
    async checkFavoriteStatus(itemType, itemId) {
        try {
            const response = await this.makeRequest('POST', {
                action: 'check',
                item_type: itemType,
                item_id: itemId
            });
            
            return response.success ? response.favorited : false;
        } catch (error) {
            console.error('检查收藏状态失败:', error);
            return false;
        }
    }
    
    /**
     * 获取收藏列表
     */
    async getFavorites(type = 'all', page = 1, limit = 10) {
        try {
            const params = new URLSearchParams({
                type: type,
                page: page,
                limit: limit
            });
            
            const response = await fetch(`${this.apiUrl}?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            return result.success ? result : null;
        } catch (error) {
            console.error('获取收藏列表失败:', error);
            return null;
        }
    }
    
    /**
     * 更新收藏按钮状态
     */
    updateFavoriteButton($btn, favorited) {
        if (favorited) {
            $btn.addClass('favorited')
                .removeClass('not-favorited')
                .html('<i class="fas fa-heart"></i> 已收藏')
                .attr('title', '取消收藏');
        } else {
            $btn.removeClass('favorited')
                .addClass('not-favorited')
                .html('<i class="far fa-heart"></i> 收藏')
                .attr('title', '添加收藏');
        }
    }
    
    /**
     * 初始化页面上的收藏按钮状态
     */
    async initializeFavoriteButtons() {
        const $buttons = $('.favorite-btn');
        
        if ($buttons.length === 0) return;
        
        // 批量检查收藏状态
        const promises = $buttons.map(async (index, btn) => {
            const $btn = $(btn);
            const itemType = $btn.data('type');
            const itemId = $btn.data('id');
            
            if (itemType && itemId) {
                const favorited = await this.checkFavoriteStatus(itemType, itemId);
                this.updateFavoriteButton($btn, favorited);
            }
        });
        
        await Promise.all(promises);
    }
    
    /**
     * 更新收藏计数显示
     */
    updateFavoriteCount(count) {
        $('.favorites-count').text(count);
        $('.stat-number[data-stat="favorites"]').text(count);
    }
    
    /**
     * 创建收藏按钮
     */
    createFavoriteButton(itemType, itemId, options = {}) {
        const defaults = {
            className: 'favorite-btn btn btn-outline',
            showText: true,
            size: 'normal'
        };
        
        const opts = { ...defaults, ...options };
        const sizeClass = opts.size === 'small' ? 'btn-sm' : '';
        const textHtml = opts.showText ? ' 收藏' : '';
        
        return `
            <button class="${opts.className} ${sizeClass} not-favorited" 
                    data-type="${itemType}" 
                    data-id="${itemId}" 
                    title="添加收藏">
                <i class="far fa-heart"></i>${textHtml}
            </button>
        `;
    }
    
    /**
     * 发送HTTP请求
     */
    async makeRequest(method, data) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };
        
        if (method === 'POST' && data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.apiUrl, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * 显示消息提示
     */
    showMessage(message, type = 'info') {
        // 如果页面有 showMessage 函数则使用，否则使用简单的 alert
        if (typeof window.showMessage === 'function') {
            window.showMessage(message, type);
        } else {
            this.createToast(message, type);
        }
    }
    
    /**
     * 创建简单的消息提示
     */
    createToast(message, type) {
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'info': 'fas fa-info-circle',
            'warning': 'fas fa-exclamation-triangle'
        };
        
        const toast = $(`
            <div id="${toastId}" class="message-toast message-${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                max-width: 300px;
                display: flex;
                align-items: center;
                gap: 10px;
                ${type === 'success' ? 'border-left: 4px solid #10b981; background: #f0fdf4;' : ''}
                ${type === 'error' ? 'border-left: 4px solid #ef4444; background: #fef2f2;' : ''}
                ${type === 'info' ? 'border-left: 4px solid #3b82f6; background: #eff6ff;' : ''}
                ${type === 'warning' ? 'border-left: 4px solid #f59e0b; background: #fffbeb;' : ''}
            ">
                <i class="${iconMap[type] || iconMap['info']}"></i>
                <span>${message}</span>
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(() => {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
}

// 初始化收藏管理器
$(document).ready(function() {
    window.favoritesManager = new FavoritesManager();
    
    // 页面加载完成后初始化收藏按钮状态
    setTimeout(() => {
        window.favoritesManager.initializeFavoriteButtons();
    }, 500);
});

// 导出为全局函数以便其他脚本使用
window.addToFavorites = function(itemType, itemId) {
    return window.favoritesManager.addFavorite(itemType, itemId);
};

window.removeFromFavorites = function(itemType, itemId) {
    return window.favoritesManager.removeFavorite(itemType, itemId);
};

window.toggleFavorite = function(itemType, itemId) {
    return window.favoritesManager.toggleFavorite(itemType, itemId);
};

window.createFavoriteButton = function(itemType, itemId, options) {
    return window.favoritesManager.createFavoriteButton(itemType, itemId, options);
};