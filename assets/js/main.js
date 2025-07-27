// 健康医疗网站主要JavaScript文件

$(document).ready(function() {
    // 初始化所有功能
    initMobileMenu();
    initHeroSlider();
    initScrollEffects();
    initServicePanel();
    initTooltips();
    initSmoothScroll();
    
    // 初始化扩展功能
    initExtendedFeatures();
    
    // 页面加载完成后的初始化
    $(window).on('load', function() {
        initAnimations();
    });
});

// 移动端菜单功能
function initMobileMenu() {
    const $mobileMenuBtn = $('.mobile-menu-btn');
    const $mobileMenu = $('.mobile-menu');
    const $closeBtn = $('.close-btn');
    const $body = $('body');
    
    // 打开移动菜单
    $mobileMenuBtn.on('click', function() {
        $mobileMenu.addClass('active');
        $body.addClass('menu-open');
    });
    
    // 关闭移动菜单
    $closeBtn.on('click', closeMobileMenu);
    
    // 点击遮罩关闭菜单
    $(document).on('click', function(e) {
        if ($mobileMenu.hasClass('active') && !$mobileMenu.is(e.target) && $mobileMenu.has(e.target).length === 0 && !$mobileMenuBtn.is(e.target) && $mobileMenuBtn.has(e.target).length === 0) {
            closeMobileMenu();
        }
    });
    
    // ESC键关闭菜单
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && $mobileMenu.hasClass('active')) {
            closeMobileMenu();
        }
    });
    
    function closeMobileMenu() {
        $mobileMenu.removeClass('active');
        $body.removeClass('menu-open');
    }
}

// 首页轮播功能
function initHeroSlider() {
    const $slides = $('.hero-slide');
    const $indicators = $('.indicator');
    const $prevBtn = $('.hero-prev');
    const $nextBtn = $('.hero-next');
    let currentSlide = 0;
    let slideInterval;
    
    if ($slides.length <= 1) return;
    
    // 开始自动轮播
    function startSlider() {
        slideInterval = setInterval(nextSlide, 6000);
    }
    
    // 停止自动轮播
    function stopSlider() {
        clearInterval(slideInterval);
    }
    
    // 下一张幻灯片
    function nextSlide() {
        currentSlide = (currentSlide + 1) % $slides.length;
        showSlide(currentSlide);
    }
    
    // 上一张幻灯片
    function prevSlide() {
        currentSlide = (currentSlide - 1 + $slides.length) % $slides.length;
        showSlide(currentSlide);
    }
    
    // 显示指定幻灯片
    function showSlide(index) {
        $slides.removeClass('active');
        $indicators.removeClass('active');
        
        $slides.eq(index).addClass('active');
        $indicators.eq(index).addClass('active');
        
        currentSlide = index;
        
        // 重置动画
        const $activeSlide = $slides.eq(index);
        $activeSlide.find('.animate-fade-up, .animate-fade-up-delay, .animate-fade-up-delay-2')
                   .removeClass('animate-fade-up animate-fade-up-delay animate-fade-up-delay-2')
                   .addClass('animate-fade-up');
        
        // 延迟添加动画类
        setTimeout(() => {
            $activeSlide.find('.hero-title').addClass('animate-fade-up');
            setTimeout(() => {
                $activeSlide.find('.hero-subtitle').addClass('animate-fade-up-delay');
                setTimeout(() => {
                    $activeSlide.find('.hero-actions').addClass('animate-fade-up-delay-2');
                }, 300);
            }, 300);
        }, 100);
    }
    
    // 指示器点击事件
    $indicators.on('click', function() {
        const index = $(this).data('slide');
        showSlide(index);
        stopSlider();
        startSlider();
    });
    
    // 导航按钮点击事件
    $nextBtn.on('click', function() {
        nextSlide();
        stopSlider();
        startSlider();
    });
    
    $prevBtn.on('click', function() {
        prevSlide();
        stopSlider();
        startSlider();
    });
    
    // 键盘导航
    $(document).on('keydown', function(e) {
        if (e.keyCode === 37) { // 左箭头
            prevSlide();
            stopSlider();
            startSlider();
        } else if (e.keyCode === 39) { // 右箭头
            nextSlide();
            stopSlider();
            startSlider();
        }
    });
    
    // 鼠标悬停暂停轮播
    $('.hero-section').on('mouseenter', stopSlider).on('mouseleave', startSlider);
    
    // 触摸滑动支持
    let touchStartX = 0;
    let touchEndX = 0;
    
    $('.hero-section').on('touchstart', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
    });
    
    $('.hero-section').on('touchend', function(e) {
        touchEndX = e.originalEvent.changedTouches[0].clientX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                nextSlide();
            } else {
                prevSlide();
            }
            stopSlider();
            startSlider();
        }
    }
    
    // 初始化第一张幻灯片动画
    showSlide(0);
    
    // 开始轮播
    startSlider();
}

// 滚动效果
function initScrollEffects() {
    const $window = $(window);
    const $backToTop = $('.back-to-top');
    
    // 返回顶部按钮 - 使用节流优化
    $window.on('scroll', throttle(function() {
        if ($window.scrollTop() > 300) {
            $backToTop.addClass('visible');
        } else {
            $backToTop.removeClass('visible');
        }
    }, 100));
    
    // 返回顶部点击事件
    $backToTop.on('click', function() {
        $('html, body').animate({
            scrollTop: 0
        }, 800);
    });
    
    // 导航栏滚动效果
    let lastScrollTop = 0;
    const $navbar = $('.main-nav');
    
    $window.on('scroll', throttle(function() {
        const scrollTop = $window.scrollTop();
        
        if (scrollTop > 100) {
            $navbar.addClass('scrolled');
        } else {
            $navbar.removeClass('scrolled');
        }
        
        lastScrollTop = scrollTop;
    }, 100));
}

// 在线客服面板
function initServicePanel() {
    const $serviceBtn = $('.service-btn');
    const $servicePanel = $('.service-panel');
    const $closeService = $('.close-service');
    
    // 打开客服面板
    $serviceBtn.on('click', function() {
        $servicePanel.toggleClass('active');
    });
    
    // 关闭客服面板
    $closeService.on('click', function() {
        $servicePanel.removeClass('active');
    });
    
    // 点击外部关闭面板
    $(document).on('click', function(e) {
        if (!$serviceBtn.is(e.target) && !$servicePanel.is(e.target) && $servicePanel.has(e.target).length === 0) {
            $servicePanel.removeClass('active');
        }
    });
}

// 工具提示
function initTooltips() {
    $('[data-tooltip]').each(function() {
        const $this = $(this);
        const tooltipText = $this.data('tooltip');
        
        $this.on('mouseenter', function() {
            const $tooltip = $('<div class="tooltip">' + tooltipText + '</div>');
            $('body').append($tooltip);
            
            const offset = $this.offset();
            const tooltipWidth = $tooltip.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            
            $tooltip.css({
                top: offset.top - tooltipHeight - 10,
                left: offset.left + ($this.outerWidth() / 2) - (tooltipWidth / 2)
            }).fadeIn(200);
        });
        
        $this.on('mouseleave', function() {
            $('.tooltip').fadeOut(200, function() {
                $(this).remove();
            });
        });
    });
}

// 平滑滚动
function initSmoothScroll() {
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 800);
        }
    });
}

// 动画效果
function initAnimations() {
    // 滚动动画
    const $animatedElements = $('.animate-on-scroll');
    
    function checkAnimation() {
        const windowTop = $(window).scrollTop();
        const windowBottom = windowTop + $(window).height();
        
        $animatedElements.each(function() {
            const $element = $(this);
            const elementTop = $element.offset().top;
            const elementBottom = elementTop + $element.outerHeight();
            
            if (elementBottom >= windowTop && elementTop <= windowBottom) {
                $element.addClass('animated');
            }
        });
    }
    
    $(window).on('scroll', throttle(checkAnimation, 100));
    checkAnimation(); // 初始检查
}

// 表单验证
function validateForm($form) {
    let isValid = true;
    const $requiredFields = $form.find('[required]');
    
    $requiredFields.each(function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (!value) {
            showFieldError($field, '此字段为必填项');
            isValid = false;
        } else {
            clearFieldError($field);
            
            // 邮箱验证
            if ($field.attr('type') === 'email' && !isValidEmail(value)) {
                showFieldError($field, '请输入有效的邮箱地址');
                isValid = false;
            }
            
            // 手机号验证
            if ($field.attr('type') === 'tel' && !isValidPhone(value)) {
                showFieldError($field, '请输入有效的手机号码');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// 显示字段错误
function showFieldError($field, message) {
    clearFieldError($field);
    
    const $errorDiv = $('<div class="field-error">' + message + '</div>');
    $field.addClass('error').after($errorDiv);
}

// 清除字段错误
function clearFieldError($field) {
    $field.removeClass('error').next('.field-error').remove();
}

// 邮箱验证
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// 手机号验证
function isValidPhone(phone) {
    const phoneRegex = /^1[3-9]\d{9}$/;
    return phoneRegex.test(phone);
}

// AJAX请求封装
function ajaxRequest(options) {
    const defaults = {
        type: 'POST',
        dataType: 'json',
        timeout: 30000,
        beforeSend: function() {
            showLoading();
        },
        complete: function() {
            hideLoading();
        },
        error: function(xhr, status, error) {
            if (status === 'timeout') {
                showMessage('请求超时，请重试', 'error');
            } else {
                showMessage('请求失败：' + error, 'error');
            }
        }
    };
    
    const settings = $.extend({}, defaults, options);
    return $.ajax(settings);
}

// 显示加载状态
function showLoading(message = '加载中...') {
    if ($('.loading-overlay').length === 0) {
        const $loading = $('<div class="loading-overlay"><div class="loading-spinner"></div><div class="loading-text">' + message + '</div></div>');
        $('body').append($loading);
    }
    $('.loading-overlay').fadeIn(200);
}

// 隐藏加载状态
function hideLoading() {
    $('.loading-overlay').fadeOut(200);
}

// 显示消息提示
function showMessage(message, type = 'info', duration = 3000) {
    const $message = $('<div class="message-toast message-' + type + '">' + message + '</div>');
    
    $('body').append($message);
    
    $message.fadeIn(300);
    
    setTimeout(function() {
        $message.fadeOut(300, function() {
            $(this).remove();
        });
    }, duration);
}

// 确认对话框
function showConfirm(message, callback) {
    const $confirm = $(`
        <div class="confirm-overlay">
            <div class="confirm-dialog">
                <div class="confirm-content">
                    <div class="confirm-message">${message}</div>
                    <div class="confirm-actions">
                        <button class="btn btn-secondary confirm-cancel">取消</button>
                        <button class="btn btn-primary confirm-ok">确认</button>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    $('body').append($confirm);
    
    $confirm.find('.confirm-ok').on('click', function() {
        $confirm.remove();
        if (typeof callback === 'function') {
            callback(true);
        }
    });
    
    $confirm.find('.confirm-cancel').on('click', function() {
        $confirm.remove();
        if (typeof callback === 'function') {
            callback(false);
        }
    });
    
    $confirm.fadeIn(200);
}

// 格式化时间
function formatTime(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = Math.floor((now - time) / 1000);
    
    if (diff < 60) {
        return '刚刚';
    } else if (diff < 3600) {
        return Math.floor(diff / 60) + '分钟前';
    } else if (diff < 86400) {
        return Math.floor(diff / 3600) + '小时前';
    } else if (diff < 2592000) {
        return Math.floor(diff / 86400) + '天前';
    } else {
        return time.getFullYear() + '-' + 
               String(time.getMonth() + 1).padStart(2, '0') + '-' + 
               String(time.getDate()).padStart(2, '0');
    }
}

// 格式化数字
function formatNumber(num) {
    if (num >= 10000) {
        return (num / 10000).toFixed(1) + '万';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toString();
}

// 防抖函数
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        
        const callNow = immediate && !timeout;
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) func.apply(context, args);
    };
}

// 节流函数
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// 收藏功能
function toggleFavorite(type, id, $element) {
    if (!window.SITE_CONFIG || !window.SITE_CONFIG.isLoggedIn) {
        showMessage('请先登录', 'warning');
        return;
    }
    
    const isFavorited = $element.hasClass('favorited');
    const action = isFavorited ? 'remove' : 'add';
    
    ajaxRequest({
        url: '/api/favorites.php',
        data: {
            action: action,
            type: type,
            id: id
        },
        success: function(response) {
            if (response.success) {
                if (action === 'add') {
                    $element.addClass('favorited');
                    showMessage('收藏成功', 'success');
                } else {
                    $element.removeClass('favorited');
                    showMessage('取消收藏', 'info');
                }
            } else {
                showMessage(response.message || '操作失败', 'error');
            }
        }
    });
}

// 点赞功能
function toggleLike(type, id, $element) {
    if (!window.SITE_CONFIG || !window.SITE_CONFIG.isLoggedIn) {
        showMessage('请先登录', 'warning');
        return;
    }
    
    const isLiked = $element.hasClass('liked');
    const action = isLiked ? 'unlike' : 'like';
    
    ajaxRequest({
        url: '/api/likes.php',
        data: {
            action: action,
            type: type,
            id: id
        },
        success: function(response) {
            if (response.success) {
                const $count = $element.find('.like-count');
                let currentCount = parseInt($count.text()) || 0;
                
                if (action === 'like') {
                    $element.addClass('liked');
                    $count.text(formatNumber(currentCount + 1));
                } else {
                    $element.removeClass('liked');
                    $count.text(formatNumber(Math.max(0, currentCount - 1)));
                }
            } else {
                showMessage(response.message || '操作失败', 'error');
            }
        }
    });
}

// 懒加载图片
function initLazyLoad() {
    const $lazyImages = $('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const $img = $(entry.target);
                    $img.attr('src', $img.data('src')).removeAttr('data-src');
                    $img.removeClass('lazy').addClass('loaded');
                    observer.unobserve(entry.target);
                }
            });
        });
        
        $lazyImages.each(function() {
            imageObserver.observe(this);
        });
    } else {
        // 降级方案
        $lazyImages.each(function() {
            const $img = $(this);
            $img.attr('src', $img.data('src')).removeAttr('data-src');
        });
    }
}

// 全局事件绑定
$(document).on('click', '.favorite-btn', function(e) {
    e.preventDefault();
    const $this = $(this);
    const type = $this.data('type');
    const id = $this.data('id');
    toggleFavorite(type, id, $this);
});

$(document).on('click', '.like-btn', function(e) {
    e.preventDefault();
    const $this = $(this);
    const type = $this.data('type');
    const id = $this.data('id');
    toggleLike(type, id, $this);
});

// 表单提交事件
$(document).on('submit', 'form[data-ajax]', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    if (!validateForm($form)) {
        return;
    }
    
    const formData = new FormData(this);
    
    ajaxRequest({
        url: $form.attr('action'),
        type: $form.attr('method') || 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showMessage(response.message || '操作成功', 'success');
                if (response.redirect) {
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                }
            } else {
                showMessage(response.message || '操作失败', 'error');
            }
        }
    });
});

// 数字动画
function animateCounters() {
    $('.stat-number[data-count]').each(function() {
        const $this = $(this);
        const target = parseInt($this.data('count'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(function() {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            $this.text(formatNumber(Math.floor(current)));
        }, 16);
    });
}

// 延迟初始化函数 - 合并到主要的ready函数中
function initExtendedFeatures() {
    // 初始化懒加载
    initLazyLoad();
    
    // 初始化Q&A交互功能
    if ($('.qa-filter-tabs').length) {
        initQAInteractions();
        initQACardEvents();
    }
    
    // 添加统计数字动画到滚动检测中
    const $statsSection = $('.stats-section');
    if ($statsSection.length) {
        let statsAnimated = false;
        
        $(window).on('scroll', throttle(function() {
            if (!statsAnimated) {
                const windowTop = $(window).scrollTop();
                const windowBottom = windowTop + $(window).height();
                const statsTop = $statsSection.offset().top;
                
                if (statsTop <= windowBottom - 100) {
                    animateCounters();
                    statsAnimated = true;
                }
            }
        }, 100));
    }
}

// Q&A 交互功能
function initQAInteractions() {
    // 过滤标签点击
    $('.qa-filter-tabs .tab-btn').on('click', function() {
        const $btn = $(this);
        const category = $btn.data('category');
        
        // 更新激活状态和可访问性属性
        $('.tab-btn').removeClass('active').attr('aria-pressed', 'false');
        $btn.addClass('active').attr('aria-pressed', 'true');
        
        // 过滤问答卡片
        filterQACards(category);
    });
    
    // 问答卡片点击
    $('.qa-card').on('click', function(e) {
        if (!$(e.target).closest('.qa-expand-btn').length) {
            const link = $(this).find('.question-title a').attr('href');
            if (link) {
                window.location.href = link;
            }
        }
    });
    
    // 展开按钮点击
    $('.qa-expand-btn').on('click', function(e) {
        e.stopPropagation();
        const $card = $(this).closest('.qa-card');
        const link = $card.find('.question-title a').attr('href');
        if (link) {
            window.open(link, '_blank');
        }
    });
    
    // 加载更多按钮
    $('.load-more-btn').on('click', function() {
        loadMoreQA();
    });
    
    // 问答卡片悬停效果已在initQACardEvents中处理
}

// 过滤问答卡片 - 优化DOM查询
function filterQACards(category) {
    const $cards = $('.qa-card');
    
    $cards.each(function() {
        const $card = $(this);
        const cardCategory = $card.data('category');
        const views = parseInt($card.data('views')) || 0;
        
        let shouldShow = false;
        
        switch(category) {
            case 'all':
                shouldShow = true;
                break;
            case 'answered':
                shouldShow = cardCategory === 'answered';
                break;
            case 'pending':
                shouldShow = cardCategory === 'pending';
                break;
            case 'hot':
                shouldShow = views > 500; // 热门问题阈值
                break;
        }
        
        if (shouldShow) {
            $card.show().addClass('animate-on-scroll');
        } else {
            $card.hide().removeClass('animate-on-scroll');
        }
    });
    
    // 重新触发滚动动画
    setTimeout(() => {
        checkScrollAnimations();
    }, 100);
}

// 加载更多问答
function loadMoreQA() {
    const $btn = $('.load-more-btn');
    const $loading = $('.loading-indicator');
    
    $btn.hide();
    $loading.show();
    
    // 模拟加载延迟
    setTimeout(() => {
        ajaxRequest({
            url: '/api/qa/load-more.php',
            type: 'GET',
            data: {
                offset: $('.qa-card').length,
                limit: 6
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    appendQACards(response.data);
                    $btn.show();
                } else {
                    $btn.text('没有更多问答了').prop('disabled', true);
                    setTimeout(() => {
                        $btn.hide();
                    }, 2000);
                }
            },
            error: function() {
                $btn.text('加载失败，点击重试').show();
            },
            complete: function() {
                $loading.hide();
            }
        });
    }, 800);
}

// 添加问答卡片
function appendQACards(qaData) {
    const $grid = $('.qa-grid');
    
    qaData.forEach((qa, index) => {
        const $card = createQACard(qa, index);
        $grid.append($card);
    });
    
    // 重新绑定事件
    initQACardEvents();
    
    // 触发滚动动画
    setTimeout(() => {
        checkScrollAnimations();
    }, 100);
}

// 创建问答卡片
function createQACard(qa, index) {
    const priorityBadge = getPriorityBadge(qa);
    const tags = qa.tags ? qa.tags.split(',').map(tag => 
        `<span class="tag">#${tag.trim()}</span>`
    ).join('') : '';
    
    const viewCount = qa.view_count > 1000 ? 
        (qa.view_count / 1000).toFixed(1) + 'k' : qa.view_count;
    
    return $(`
        <div class="qa-card animate-on-scroll" 
             style="animation-delay: ${index * 0.1}s"
             data-category="${qa.answer_count > 0 ? 'answered' : 'pending'}"
             data-views="${qa.view_count}">
            
            <div class="qa-card-header">
                <div class="question-priority">
                    ${priorityBadge}
                </div>
                
                <div class="question-category">
                    <i class="fas fa-tag"></i>
                    ${qa.category_name || '综合咨询'}
                </div>
            </div>
            
            <div class="qa-card-content">
                <h3 class="question-title">
                    <a href="/qa/detail.php?id=${qa.id}">
                        ${qa.title}
                    </a>
                </h3>
                
                <p class="question-preview">
                    ${qa.content.replace(/<[^>]*>/g, '').substring(0, 100)}...
                </p>
                
                <div class="question-tags">
                    ${tags}
                </div>
            </div>
            
            <div class="qa-card-footer">
                <div class="question-author">
                    <div class="author-avatar">
                        <i class="fas fa-${qa.is_anonymous ? 'user-secret' : 'user-circle'}"></i>
                    </div>
                    <div class="author-info">
                        <span class="author-name">
                            ${qa.is_anonymous ? '匿名用户' : qa.username}
                        </span>
                        <span class="question-time">
                            ${formatTime(qa.created_at)}
                        </span>
                    </div>
                </div>
                
                <div class="question-stats">
                    <div class="stat-group">
                        <span class="stat-item">
                            <i class="fas fa-comments"></i>
                            ${qa.answer_count}
                        </span>
                        <span class="stat-item">
                            <i class="fas fa-eye"></i>
                            ${viewCount}
                        </span>
                        ${qa.answer_count > 0 ? '<span class="stat-item helpful"><i class="fas fa-thumbs-up"></i>有帮助</span>' : ''}
                    </div>
                    
                    <button class="qa-expand-btn" title="查看详情">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    `);
}

// 获取优先级徽章
function getPriorityBadge(qa) {
    if (qa.view_count > 1000) {
        return '<span class="priority-badge hot"><i class="fas fa-fire"></i>热门</span>';
    } else if (qa.answer_count > 0) {
        return '<span class="priority-badge answered"><i class="fas fa-check-circle"></i>已解答</span>';
    } else {
        return '<span class="priority-badge pending"><i class="fas fa-clock"></i>待解答</span>';
    }
}

// 初始化问答卡片事件
function initQACardEvents() {
    // 移除旧的事件监听器 - 完整解绑
    $('.qa-card').off('.qacard');
    $('.qa-expand-btn').off('.qaexpand');
    $('.qa-card').off('mouseenter.qahover mouseleave.qahover');
    
    // 问答卡片点击
    $('.qa-card').on('click.qacard', function(e) {
        if (!$(e.target).closest('.qa-expand-btn').length) {
            const link = $(this).find('.question-title a').attr('href');
            if (link) {
                window.location.href = link;
            }
        }
    });
    
    // 展开按钮点击
    $('.qa-expand-btn').on('click.qaexpand', function(e) {
        e.stopPropagation();
        const $card = $(this).closest('.qa-card');
        const link = $card.find('.question-title a').attr('href');
        if (link) {
            window.open(link, '_blank');
        }
    });
    
    // 问答卡片悬停效果 - 使用命名空间
    $('.qa-card').on('mouseenter.qahover', function() {
        $(this).find('.qa-expand-btn').addClass('visible');
    }).on('mouseleave.qahover', function() {
        $(this).find('.qa-expand-btn').removeClass('visible');
    });
}

// 检查滚动动画 - 缺失的函数定义
function checkScrollAnimations() {
    const windowTop = $(window).scrollTop();
    const windowBottom = windowTop + $(window).height();
    
    $('.animate-on-scroll:visible').each(function() {
        const $element = $(this);
        if (!$element.hasClass('animated')) {
            const elementTop = $element.offset().top;
            const elementBottom = elementTop + $element.outerHeight();
            
            if (elementBottom >= windowTop && elementTop <= windowBottom - 50) {
                $element.addClass('animated');
            }
        }
    });
}

// 错误处理
window.onerror = function(message, source, lineno, colno, error) {
    console.error('JavaScript Error:', {
        message: message,
        source: source,
        line: lineno,
        column: colno,
        error: error
    });
    
    if (window.SITE_CONFIG && window.SITE_CONFIG.debug) {
        showMessage('页面出现错误，请刷新重试', 'error');
    }
    
    return true;
};