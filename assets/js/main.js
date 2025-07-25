// 健康医疗网站主要JavaScript文件

$(document).ready(function() {
    // 初始化所有功能
    initMobileMenu();
    initHeroSlider();
    initScrollEffects();
    initServicePanel();
    initTooltips();
    initSmoothScroll();
    
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
    let currentSlide = 0;
    let slideInterval;
    
    if ($slides.length <= 1) return;
    
    // 开始自动轮播
    function startSlider() {
        slideInterval = setInterval(nextSlide, 5000);
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
    
    // 显示指定幻灯片
    function showSlide(index) {
        $slides.removeClass('active');
        $indicators.removeClass('active');
        
        $slides.eq(index).addClass('active');
        $indicators.eq(index).addClass('active');
        
        currentSlide = index;
    }
    
    // 指示器点击事件
    $indicators.on('click', function() {
        const index = $(this).data('slide');
        showSlide(index);
        stopSlider();
        startSlider();
    });
    
    // 鼠标悬停暂停轮播
    $('.hero-section').on('mouseenter', stopSlider).on('mouseleave', startSlider);
    
    // 开始轮播
    startSlider();
}

// 滚动效果
function initScrollEffects() {
    const $window = $(window);
    const $backToTop = $('.back-to-top');
    
    // 返回顶部按钮
    $window.on('scroll', function() {
        if ($window.scrollTop() > 300) {
            $backToTop.addClass('visible');
        } else {
            $backToTop.removeClass('visible');
        }
    });
    
    // 返回顶部点击事件
    $backToTop.on('click', function() {
        $('html, body').animate({
            scrollTop: 0
        }, 800);
    });
    
    // 导航栏滚动效果
    let lastScrollTop = 0;
    const $navbar = $('.main-nav');
    
    $window.on('scroll', function() {
        const scrollTop = $window.scrollTop();
        
        if (scrollTop > 100) {
            $navbar.addClass('scrolled');
        } else {
            $navbar.removeClass('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });
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
    
    $(window).on('scroll', checkAnimation);
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
    if (!window.SITE_CONFIG.isLoggedIn) {
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
    if (!window.SITE_CONFIG.isLoggedIn) {
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

// 初始化懒加载
$(document).ready(function() {
    initLazyLoad();
});

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