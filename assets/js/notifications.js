/**
 * 通知系统
 * 提供各种类型的用户通知功能
 */

class NotificationManager {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.queue = [];
        this.maxNotifications = 5;
        this.defaultDuration = 5000;
        this.positions = {
            'top-right': { top: '20px', right: '20px' },
            'top-left': { top: '20px', left: '20px' },
            'bottom-right': { bottom: '20px', right: '20px' },
            'bottom-left': { bottom: '20px', left: '20px' },
            'top-center': { top: '20px', left: '50%', transform: 'translateX(-50%)' },
            'bottom-center': { bottom: '20px', left: '50%', transform: 'translateX(-50%)' }
        };
        this.currentPosition = 'top-right';
        
        this.init();
    }
    
    init() {
        this.createContainer();
        this.bindEvents();
        this.overrideGlobalAlert();
    }
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.id = 'notificationContainer';
        this.setPosition(this.currentPosition);
        
        // 添加基础样式
        this.addStyles();
        
        document.body.appendChild(this.container);
    }
    
    addStyles() {
        if (document.getElementById('notification-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification-container {
                position: fixed;
                z-index: 10000;
                pointer-events: none;
            }
            
            .notification {
                pointer-events: auto;
                margin-bottom: 10px;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                font-size: 14px;
                line-height: 1.4;
                max-width: 400px;
                min-width: 300px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                position: relative;
                display: flex;
                align-items: flex-start;
                background: white;
                border-left: 4px solid #007bff;
            }
            
            .notification.show {
                opacity: 1;
                transform: translateX(0);
            }
            
            .notification.hide {
                opacity: 0;
                transform: translateX(100%);
                margin-bottom: 0;
                padding-top: 0;
                padding-bottom: 0;
                max-height: 0;
                overflow: hidden;
            }
            
            .notification-icon {
                flex-shrink: 0;
                margin-right: 12px;
                font-size: 18px;
                margin-top: 1px;
            }
            
            .notification-content {
                flex: 1;
            }
            
            .notification-title {
                font-weight: 600;
                margin-bottom: 4px;
                color: #333;
            }
            
            .notification-message {
                color: #666;
                margin: 0;
            }
            
            .notification-close {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: #999;
                padding: 4px;
                line-height: 1;
                transition: color 0.2s;
            }
            
            .notification-close:hover {
                color: #666;
            }
            
            .notification-actions {
                margin-top: 12px;
                display: flex;
                gap: 8px;
            }
            
            .notification-btn {
                padding: 6px 12px;
                border: none;
                border-radius: 4px;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s;
                font-weight: 500;
            }
            
            .notification-btn-primary {
                background: #007bff;
                color: white;
            }
            
            .notification-btn-primary:hover {
                background: #0056b3;
            }
            
            .notification-btn-secondary {
                background: #e9ecef;
                color: #495057;
            }
            
            .notification-btn-secondary:hover {
                background: #dee2e6;
            }
            
            .notification-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: rgba(0, 123, 255, 0.3);
                transition: width linear;
            }
            
            /* 不同类型的通知样式 */
            .notification.success {
                border-left-color: #28a745;
                background: #f8fff9;
            }
            
            .notification.success .notification-icon {
                color: #28a745;
            }
            
            .notification.error {
                border-left-color: #dc3545;
                background: #fff8f8;
            }
            
            .notification.error .notification-icon {
                color: #dc3545;
            }
            
            .notification.warning {
                border-left-color: #ffc107;
                background: #fffef8;
            }
            
            .notification.warning .notification-icon {
                color: #ffc107;
            }
            
            .notification.info {
                border-left-color: #17a2b8;
                background: #f8feff;
            }
            
            .notification.info .notification-icon {
                color: #17a2b8;
            }
            
            /* 位置调整 */
            .notification-container.position-left .notification {
                transform: translateX(-100%);
            }
            
            .notification-container.position-left .notification.show {
                transform: translateX(0);
            }
            
            .notification-container.position-left .notification.hide {
                transform: translateX(-100%);
            }
            
            /* 响应式 */
            @media (max-width: 768px) {
                .notification {
                    min-width: 280px;
                    max-width: calc(100vw - 40px);
                    margin-left: 20px;
                    margin-right: 20px;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    setPosition(position) {
        if (!this.positions[position]) return;
        
        this.currentPosition = position;
        const pos = this.positions[position];
        
        Object.keys(pos).forEach(key => {
            this.container.style[key] = pos[key];
        });
        
        // 更新动画方向
        this.container.className = `notification-container position-${position.includes('left') ? 'left' : 'right'}`;
    }
    
    bindEvents() {
        // 监听页面可见性变化
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAllTimers();
            } else {
                this.resumeAllTimers();
            }
        });
        
        // 监听窗口焦点变化
        window.addEventListener('blur', () => this.pauseAllTimers());
        window.addEventListener('focus', () => this.resumeAllTimers());
    }
    
    overrideGlobalAlert() {
        // 覆盖全局的showMessage函数（如果存在）
        if (typeof window.showMessage === 'function') {
            window.originalShowMessage = window.showMessage;
        }
        
        window.showMessage = (message, type = 'info', options = {}) => {
            return this.show(message, type, options);
        };
        
        // 兼容原有的alert
        window.showNotification = (message, type = 'info', options = {}) => {
            return this.show(message, type, options);
        };
    }
    
    show(message, type = 'info', options = {}) {
        const config = {
            title: options.title || '',
            message: message,
            type: type,
            duration: options.duration !== undefined ? options.duration : this.defaultDuration,
            closable: options.closable !== false,
            showProgress: options.showProgress !== false,
            actions: options.actions || [],
            onClick: options.onClick || null,
            onClose: options.onClose || null,
            ...options
        };
        
        const id = this.generateId();
        const notification = this.createNotification(id, config);
        
        // 如果超过最大数量，移除最旧的
        if (this.notifications.size >= this.maxNotifications) {
            const oldestId = this.notifications.keys().next().value;
            this.hide(oldestId);
        }
        
        this.notifications.set(id, {
            element: notification,
            config: config,
            timer: null,
            startTime: null,
            remainingTime: config.duration,
            paused: false
        });
        
        this.container.appendChild(notification);
        
        // 触发显示动画
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // 设置自动关闭
        if (config.duration > 0) {
            this.startTimer(id);
        }
        
        return id;
    }
    
    createNotification(id, config) {
        const notification = document.createElement('div');
        notification.className = `notification ${config.type}`;
        notification.setAttribute('data-id', id);
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        let html = `
            <div class="notification-icon">
                <i class="${icons[config.type] || icons.info}"></i>
            </div>
            <div class="notification-content">
        `;
        
        if (config.title) {
            html += `<div class="notification-title">${this.escapeHtml(config.title)}</div>`;
        }
        
        html += `<div class="notification-message">${this.escapeHtml(config.message)}</div>`;
        
        if (config.actions.length > 0) {
            html += '<div class="notification-actions">';
            config.actions.forEach((action, index) => {
                const btnClass = action.primary ? 'notification-btn-primary' : 'notification-btn-secondary';
                html += `<button class="notification-btn ${btnClass}" data-action="${index}">${this.escapeHtml(action.text)}</button>`;
            });
            html += '</div>';
        }
        
        html += '</div>';
        
        if (config.closable) {
            html += '<button class="notification-close" aria-label="关闭">&times;</button>';
        }
        
        if (config.showProgress && config.duration > 0) {
            html += '<div class="notification-progress"></div>';
        }
        
        notification.innerHTML = html;
        
        this.bindNotificationEvents(notification, id, config);
        
        return notification;
    }
    
    bindNotificationEvents(notification, id, config) {
        // 关闭按钮
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.hide(id);
            });
        }
        
        // 点击事件
        if (config.onClick) {
            notification.addEventListener('click', (e) => {
                if (!e.target.closest('.notification-close, .notification-actions')) {
                    config.onClick(id, e);
                }
            });
        }
        
        // 动作按钮
        const actionBtns = notification.querySelectorAll('[data-action]');
        actionBtns.forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = config.actions[index];
                if (action.handler) {
                    const result = action.handler(id, e);
                    if (result !== false) {
                        this.hide(id);
                    }
                } else {
                    this.hide(id);
                }
            });
        });
        
        // 鼠标悬停暂停计时器
        notification.addEventListener('mouseenter', () => {
            this.pauseTimer(id);
        });
        
        notification.addEventListener('mouseleave', () => {
            this.resumeTimer(id);
        });
    }
    
    startTimer(id) {
        const notificationData = this.notifications.get(id);
        if (!notificationData || notificationData.config.duration <= 0) return;
        
        notificationData.startTime = Date.now();
        notificationData.paused = false;
        
        const updateProgress = () => {
            if (!this.notifications.has(id) || notificationData.paused) return;
            
            const elapsed = Date.now() - notificationData.startTime;
            const remaining = Math.max(0, notificationData.remainingTime - elapsed);
            const progress = (notificationData.config.duration - remaining) / notificationData.config.duration * 100;
            
            const progressBar = notificationData.element.querySelector('.notification-progress');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            
            if (remaining <= 0) {
                this.hide(id);
            } else {
                notificationData.timer = requestAnimationFrame(updateProgress);
            }
        };
        
        notificationData.timer = requestAnimationFrame(updateProgress);
    }
    
    pauseTimer(id) {
        const notificationData = this.notifications.get(id);
        if (!notificationData || notificationData.paused) return;
        
        notificationData.paused = true;
        if (notificationData.timer) {
            cancelAnimationFrame(notificationData.timer);
        }
        
        const elapsed = Date.now() - notificationData.startTime;
        notificationData.remainingTime = Math.max(0, notificationData.remainingTime - elapsed);
    }
    
    resumeTimer(id) {
        const notificationData = this.notifications.get(id);
        if (!notificationData || !notificationData.paused) return;
        
        notificationData.startTime = Date.now();
        notificationData.paused = false;
        
        this.startTimer(id);
    }
    
    pauseAllTimers() {
        this.notifications.forEach((_, id) => {
            this.pauseTimer(id);
        });
    }
    
    resumeAllTimers() {
        this.notifications.forEach((_, id) => {
            this.resumeTimer(id);
        });
    }
    
    hide(id) {
        const notificationData = this.notifications.get(id);
        if (!notificationData) return;
        
        const { element, config } = notificationData;
        
        // 停止计时器
        if (notificationData.timer) {
            cancelAnimationFrame(notificationData.timer);
        }
        
        // 触发关闭回调
        if (config.onClose) {
            config.onClose(id);
        }
        
        // 添加隐藏动画
        element.classList.add('hide');
        
        // 动画完成后移除元素
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
            this.notifications.delete(id);
        }, 300);
    }
    
    hideAll() {
        this.notifications.forEach((_, id) => {
            this.hide(id);
        });
    }
    
    update(id, options) {
        const notificationData = this.notifications.get(id);
        if (!notificationData) return false;
        
        const { element, config } = notificationData;
        
        // 更新配置
        Object.assign(config, options);
        
        // 更新内容
        if (options.message !== undefined) {
            const messageEl = element.querySelector('.notification-message');
            if (messageEl) {
                messageEl.textContent = options.message;
            }
        }
        
        if (options.title !== undefined) {
            const titleEl = element.querySelector('.notification-title');
            if (titleEl) {
                titleEl.textContent = options.title;
            } else if (options.title) {
                // 添加标题
                const contentEl = element.querySelector('.notification-content');
                const titleEl = document.createElement('div');
                titleEl.className = 'notification-title';
                titleEl.textContent = options.title;
                contentEl.insertBefore(titleEl, contentEl.firstChild);
            }
        }
        
        // 更新类型
        if (options.type !== undefined && options.type !== config.type) {
            element.classList.remove(config.type);
            element.classList.add(options.type);
        }
        
        return true;
    }
    
    generateId() {
        return 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 便捷方法
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }
    
    error(message, options = {}) {
        return this.show(message, 'error', { duration: 0, ...options });
    }
    
    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }
    
    info(message, options = {}) {
        return this.show(message, 'info', options);
    }
    
    // 确认对话框
    confirm(message, options = {}) {
        return new Promise((resolve) => {
            const config = {
                title: options.title || '确认',
                duration: 0,
                closable: false,
                actions: [
                    {
                        text: options.cancelText || '取消',
                        handler: () => resolve(false)
                    },
                    {
                        text: options.confirmText || '确认',
                        primary: true,
                        handler: () => resolve(true)
                    }
                ],
                ...options
            };
            
            this.show(message, 'warning', config);
        });
    }
}

// 初始化全局通知管理器
document.addEventListener('DOMContentLoaded', function() {
    if (!window.notificationManager) {
        window.notificationManager = new NotificationManager();
        
        // 提供全局便捷方法
        window.notify = {
            show: (message, type, options) => window.notificationManager.show(message, type, options),
            success: (message, options) => window.notificationManager.success(message, options),
            error: (message, options) => window.notificationManager.error(message, options),
            warning: (message, options) => window.notificationManager.warning(message, options),
            info: (message, options) => window.notificationManager.info(message, options),
            confirm: (message, options) => window.notificationManager.confirm(message, options),
            hide: (id) => window.notificationManager.hide(id),
            hideAll: () => window.notificationManager.hideAll()
        };
    }
});

// 导出类
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}