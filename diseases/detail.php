<?php
require_once '../includes/init.php';

// 获取疾病ID
$diseaseId = intval($_GET['id'] ?? 0);
if (!$diseaseId) {
    header('Location: /diseases/');
    exit;
}

// 获取疾病详细信息
$disease = $db->fetch("
    SELECT d.*, c.name as category_name, c.parent_id
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.id = ? AND d.status = 'active'
", [$diseaseId]);

if (!$disease) {
    header('HTTP/1.0 404 Not Found');
    include '../templates/404.php';
    exit;
}

// 更新浏览次数
$db->query("UPDATE diseases SET view_count = view_count + 1 WHERE id = ?", [$diseaseId]);

// 设置页面信息
$pageTitle = $disease['name'] . " - 疾病百科 - " . SITE_NAME;
$pageDescription = $disease['name'] . "的症状、病因、治疗方法、预防措施等详细信息。" . strip_tags($disease['description'] ?? '');
$pageKeywords = $disease['name'] . ",症状,病因,治疗,预防," . $disease['category_name'];
$currentPage = 'diseases';

// 获取相关疾病推荐
$relatedDiseases = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM diseases d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.category_id = ? AND d.id != ? AND d.status = 'active'
    ORDER BY d.view_count DESC
    LIMIT 6
", [$disease['category_id'], $diseaseId]);

// 获取相关医生推荐
$relatedDoctors = $db->fetchAll("
    SELECT d.*, h.name as hospital_name, h.city as hospital_city,
           c.name as category_name
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.category_id = ? AND d.status = 'active' AND h.status = 'active'
    ORDER BY d.rating DESC, d.view_count DESC
    LIMIT 5
", [$disease['category_id']]);

// 获取相关医院推荐
$relatedHospitals = $db->fetchAll("
    SELECT h.*, 
           (SELECT COUNT(*) FROM doctors d WHERE d.hospital_id = h.id AND d.category_id = ? AND d.status = 'active') as related_doctor_count
    FROM hospitals h 
    WHERE h.status = 'active' AND EXISTS (
        SELECT 1 FROM doctors d WHERE d.hospital_id = h.id AND d.category_id = ? AND d.status = 'active'
    )
    ORDER BY related_doctor_count DESC, h.rating DESC
    LIMIT 5
", [$disease['category_id'], $disease['category_id']]);

// 模拟相关问答（实际应该从Q&A表获取）
$relatedQuestions = [
    [
        'title' => '患有' . $disease['name'] . '需要注意什么？',
        'answer_count' => rand(3, 15),
        'view_count' => rand(100, 1000)
    ],
    [
        'title' => $disease['name'] . '的早期症状有哪些？',
        'answer_count' => rand(2, 10),
        'view_count' => rand(80, 800)
    ],
    [
        'title' => '如何预防' . $disease['name'] . '？',
        'answer_count' => rand(1, 8),
        'view_count' => rand(60, 600)
    ]
];

// 添加页面特定的CSS
$pageCSS = ['/assets/css/diseases.css'];

include '../templates/header.php';
?>

<div class="disease-detail-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '疾病百科', 'url' => '/diseases/'],
                ['title' => $disease['category_name'], 'url' => '/diseases/?category=' . $disease['category_id']],
                ['title' => $disease['name']]
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="disease-detail-layout">
            <!-- 主要内容区 -->
            <main class="disease-main-content">
                <!-- 疾病基本信息卡片 -->
                <div class="disease-profile-card">
                    <div class="disease-header">
                        <div class="disease-title-section">
                            <h1 class="disease-name"><?php echo h($disease['name']); ?></h1>
                            
                            <div class="disease-badges">
                                <span class="category-badge">
                                    <i class="fas fa-tag"></i>
                                    <?php echo h($disease['category_name']); ?>
                                </span>
                                
                                <?php if ($disease['difficulty']): ?>
                                    <span class="difficulty-badge difficulty-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $disease['difficulty']); ?>">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php echo h($disease['difficulty']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($disease['contagious']): ?>
                                    <span class="contagious-badge">
                                        <i class="fas fa-virus"></i>
                                        传染性疾病
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="disease-stats">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($disease['view_count']); ?>次查看
                                </span>
                                
                                <?php if ($disease['updated_at']): ?>
                                    <span class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        更新于 <?php echo date('Y年m月d日', strtotime($disease['updated_at'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="disease-actions">
                            <button class="btn btn-primary favorite-btn" 
                                    data-type="disease" 
                                    data-id="<?php echo $disease['id']; ?>">
                                <i class="far fa-bookmark"></i>
                                收藏疾病
                            </button>
                            
                            <button class="btn btn-outline share-btn" 
                                    data-title="<?php echo h($disease['name']); ?>"
                                    data-url="<?php echo getCurrentUrl(); ?>">
                                <i class="fas fa-share-alt"></i>
                                分享
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($disease['description']): ?>
                        <div class="disease-overview">
                            <h3>疾病概述</h3>
                            <div class="overview-content">
                                <?php echo nl2br(h($disease['description'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 疾病详细信息标签页 -->
                <div class="disease-tabs">
                    <div class="tab-navigation">
                        <button class="tab-btn active" data-tab="symptoms">症状表现</button>
                        <button class="tab-btn" data-tab="causes">病因分析</button>
                        <button class="tab-btn" data-tab="treatment">治疗方法</button>
                        <button class="tab-btn" data-tab="prevention">预防措施</button>
                        <button class="tab-btn" data-tab="diet">饮食建议</button>
                        <button class="tab-btn" data-tab="care">日常护理</button>
                    </div>
                    
                    <div class="tab-content">
                        <!-- 症状表现 -->
                        <div class="tab-panel active" id="symptoms">
                            <?php if ($disease['symptoms']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-thermometer-half"></i> 主要症状</h3>
                                    <div class="symptoms-content">
                                        <?php echo nl2br(h($disease['symptoms'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-thermometer-half"></i>
                                    <p>暂无症状信息</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 症状严重程度指示 -->
                            <?php if ($disease['difficulty']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-chart-line"></i> 严重程度评估</h3>
                                    <div class="severity-indicator">
                                        <div class="severity-level severity-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $disease['difficulty']); ?>">
                                            <span class="level-text"><?php echo h($disease['difficulty']); ?></span>
                                            <div class="level-description">
                                                <?php
                                                switch ($disease['difficulty']) {
                                                    case '轻微':
                                                        echo '症状较轻，通常不影响正常生活';
                                                        break;
                                                    case '一般':
                                                        echo '症状明显，需要适当治疗和护理';
                                                        break;
                                                    case '严重':
                                                        echo '症状严重，需要及时就医治疗';
                                                        break;
                                                    case '危重':
                                                        echo '生命危险，需要紧急医疗救治';
                                                        break;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 病因分析 -->
                        <div class="tab-panel" id="causes">
                            <?php if ($disease['causes']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-search"></i> 发病原因</h3>
                                    <div class="causes-content">
                                        <?php echo nl2br(h($disease['causes'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-search"></i>
                                    <p>暂无病因分析信息</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 治疗方法 -->
                        <div class="tab-panel" id="treatment">
                            <?php if ($disease['treatment']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-pills"></i> 治疗方案</h3>
                                    <div class="treatment-content">
                                        <?php echo nl2br(h($disease['treatment'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-pills"></i>
                                    <p>暂无治疗方法信息</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="content-section">
                                <h3><i class="fas fa-exclamation-triangle"></i> 重要提醒</h3>
                                <div class="warning-notice">
                                    <p>以上信息仅供参考，具体治疗方案需要根据个人情况制定。请及时就医，听从专业医生的诊断和建议。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 预防措施 -->
                        <div class="tab-panel" id="prevention">
                            <?php if ($disease['prevention']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-shield-alt"></i> 预防策略</h3>
                                    <div class="prevention-content">
                                        <?php echo nl2br(h($disease['prevention'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-shield-alt"></i>
                                    <p>暂无预防措施信息</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 饮食建议 -->
                        <div class="tab-panel" id="diet">
                            <?php if ($disease['diet_advice']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-utensils"></i> 饮食指导</h3>
                                    <div class="diet-content">
                                        <?php echo nl2br(h($disease['diet_advice'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-utensils"></i>
                                    <p>暂无饮食建议信息</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 日常护理 -->
                        <div class="tab-panel" id="care">
                            <?php if ($disease['care_advice']): ?>
                                <div class="content-section">
                                    <h3><i class="fas fa-hand-holding-heart"></i> 护理要点</h3>
                                    <div class="care-content">
                                        <?php echo nl2br(h($disease['care_advice'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-content">
                                    <i class="fas fa-hand-holding-heart"></i>
                                    <p>暂无护理建议信息</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="disease-sidebar">
                <!-- 快速操作 -->
                <div class="sidebar-widget quick-actions">
                    <h3 class="widget-title">
                        <i class="fas fa-bolt"></i>
                        快速操作
                    </h3>
                    <div class="action-buttons">
                        <a href="/doctors/?category=<?php echo $disease['category_id']; ?>" 
                           class="action-btn">
                            <i class="fas fa-user-md"></i>
                            <span>找相关医生</span>
                        </a>
                        
                        <a href="/hospitals/?category=<?php echo $disease['category_id']; ?>" 
                           class="action-btn">
                            <i class="fas fa-hospital"></i>
                            <span>找相关医院</span>
                        </a>
                        
                        <a href="/qa/ask.php?disease=<?php echo urlencode($disease['name']); ?>" 
                           class="action-btn">
                            <i class="fas fa-question-circle"></i>
                            <span>咨询问题</span>
                        </a>
                    </div>
                </div>
                
                <!-- 相关疾病 -->
                <?php if ($relatedDiseases): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-book-medical"></i>
                            相关疾病
                        </h3>
                        <div class="related-diseases">
                            <?php foreach ($relatedDiseases as $related): ?>
                                <div class="related-item">
                                    <h5>
                                        <a href="/diseases/detail.php?id=<?php echo $related['id']; ?>">
                                            <?php echo h($related['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="related-meta">
                                        <?php if ($related['difficulty']): ?>
                                            <span class="difficulty-badge difficulty-<?php echo str_replace(['轻微', '一般', '严重', '危重'], ['mild', 'normal', 'severe', 'critical'], $related['difficulty']); ?>">
                                                <?php echo h($related['difficulty']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($related['view_count']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 相关医生 -->
                <?php if ($relatedDoctors): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-user-md"></i>
                            相关专家
                        </h3>
                        <div class="related-doctors">
                            <?php foreach ($relatedDoctors as $doctor): ?>
                                <div class="doctor-item">
                                    <div class="doctor-avatar">
                                        <?php if ($doctor['avatar']): ?>
                                            <img src="<?php echo h($doctor['avatar']); ?>" 
                                                 alt="<?php echo h($doctor['name']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="doctor-info">
                                        <h5>
                                            <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                                                <?php echo h($doctor['name']); ?>
                                            </a>
                                        </h5>
                                        <div class="doctor-meta">
                                            <span class="title"><?php echo h($doctor['title']); ?></span>
                                            <span class="hospital"><?php echo h(truncate($doctor['hospital_name'], 20)); ?></span>
                                        </div>
                                        <div class="doctor-rating">
                                            <?php
                                            $rating = floatval($doctor['rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating):
                                            ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; endfor; ?>
                                            <span><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 相关问答 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-question-circle"></i>
                        相关问答
                    </h3>
                    <div class="related-questions">
                        <?php foreach ($relatedQuestions as $question): ?>
                            <div class="question-item">
                                <h5>
                                    <a href="/qa/">
                                        <?php echo h($question['title']); ?>
                                    </a>
                                </h5>
                                <div class="question-meta">
                                    <span class="answers">
                                        <i class="fas fa-comment"></i>
                                        <?php echo $question['answer_count']; ?>个回答
                                    </span>
                                    <span class="views">
                                        <i class="fas fa-eye"></i>
                                        <?php echo number_format($question['view_count']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 健康提醒 -->
                <div class="sidebar-widget health-reminder">
                    <h3 class="widget-title">
                        <i class="fas fa-heartbeat"></i>
                        健康提醒
                    </h3>
                    <div class="reminder-content">
                        <div class="reminder-item">
                            <i class="fas fa-user-md"></i>
                            <p>如有相关症状，请及时就医诊断</p>
                        </div>
                        <div class="reminder-item">
                            <i class="fas fa-pills"></i>
                            <p>请遵医嘱用药，不要自行停药或换药</p>
                        </div>
                        <div class="reminder-item">
                            <i class="fas fa-phone"></i>
                            <p>紧急情况请拨打120急救电话</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 标签页切换
    $('.tab-btn').on('click', function() {
        const targetTab = $(this).data('tab');
        
        // 更新标签页按钮状态
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // 更新内容面板
        $('.tab-panel').removeClass('active');
        $('#' + targetTab).addClass('active');
    });
    
    // 收藏功能
    $('.favorite-btn').on('click', function() {
        const $btn = $(this);
        const type = $btn.data('type');
        const id = $btn.data('id');
        
        if ($btn.hasClass('favorited')) {
            $btn.removeClass('favorited');
            $btn.find('i').removeClass('fas').addClass('far');
            $btn.html('<i class="far fa-bookmark"></i> 收藏疾病');
            showMessage('已取消收藏', 'info');
        } else {
            $btn.addClass('favorited');
            $btn.find('i').removeClass('far').addClass('fas');
            $btn.html('<i class="fas fa-bookmark"></i> 已收藏');
            showMessage('收藏成功', 'success');
        }
    });
    
    // 分享功能
    $('.share-btn').on('click', function() {
        const title = $(this).data('title');
        const url = $(this).data('url');
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // 复制链接到剪贴板
            navigator.clipboard.writeText(url).then(function() {
                showMessage('链接已复制到剪贴板', 'success');
            }).catch(function() {
                showMessage('复制失败，请手动复制链接', 'error');
            });
        }
    });
    
    // 平滑滚动到内容部分
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 300);
        }
    });
});

// 显示消息提示
function showMessage(message, type = 'info') {
    const toast = $('<div class="message-toast message-' + type + '">' + message + '</div>');
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// 获取当前页面URL
function getCurrentUrl() {
    return window.location.href;
}
</script>

<?php include '../templates/footer.php'; ?>