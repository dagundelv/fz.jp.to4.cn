<?php
require_once 'includes/init.php';

$pageTitle = '水平滚动测试';
include 'templates/header.php';
?>

<style>
/* 测试样式 */
.test-section {
    background: #f0f0f0;
    padding: 20px;
    margin: 20px 0;
    border: 2px solid red;
}
.test-wide {
    background: yellow;
    width: 2000px; /* 故意设置很宽 */
    height: 50px;
    margin: 10px 0;
}
.debug-info {
    position: fixed;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px;
    border-radius: 5px;
    font-size: 12px;
    z-index: 9999;
}
</style>

<div class="debug-info">
    Screen width: <span id="screenWidth"></span><br>
    Body width: <span id="bodyWidth"></span><br>
    Scroll width: <span id="scrollWidth"></span>
</div>

<div class="container">
    <h1>水平滚动测试页面</h1>
    
    <div class="test-section">
        <h2>正常容器测试</h2>
        <p>这是一个正常的容器，应该不会导致水平滚动。</p>
    </div>
    
    <div class="test-section">
        <h2>超宽元素测试</h2>
        <div class="test-wide">这是一个故意设置为2000px宽的元素</div>
    </div>
    
    <div class="test-section">
        <h2>实际首页内容测试</h2>
        
        <!-- 复制首页的快速导航 -->
        <div class="quick-nav-grid">
            <a href="#" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3>找医院</h3>
                <p>全国医院查询</p>
            </a>
            <a href="#" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>找医生</h3>
                <p>专家在线预约</p>
            </a>
            <a href="#" class="quick-nav-item">
                <div class="nav-icon">
                    <i class="fas fa-book-medical"></i>
                </div>
                <h3>查疾病</h3>
                <p>疾病百科大全</p>
            </a>
        </div>
    </div>
</div>

<script>
function updateDebugInfo() {
    document.getElementById('screenWidth').textContent = window.innerWidth + 'px';
    document.getElementById('bodyWidth').textContent = document.body.offsetWidth + 'px';
    document.getElementById('scrollWidth').textContent = document.body.scrollWidth + 'px';
}

updateDebugInfo();
window.addEventListener('resize', updateDebugInfo);

// 检查哪些元素超出了视口宽度
function findWideElements() {
    const elements = document.querySelectorAll('*');
    const viewportWidth = window.innerWidth;
    const wideElements = [];
    
    elements.forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.width > viewportWidth) {
            wideElements.push({
                element: el,
                width: rect.width,
                className: el.className,
                tagName: el.tagName
            });
        }
    });
    
    console.log('超宽元素:', wideElements);
    return wideElements;
}

setTimeout(() => {
    findWideElements();
}, 1000);
</script>

<?php include 'templates/footer.php'; ?>