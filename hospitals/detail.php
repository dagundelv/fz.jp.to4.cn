<?php
require_once '../includes/init.php';

// 获取医院ID
$hospitalId = intval($_GET['id'] ?? 0);

if (!$hospitalId) {
    redirect('/hospitals/');
}

// 获取医院详情
$hospital = $db->fetch("
    SELECT * FROM hospitals 
    WHERE id = ? AND status = 'active'
", [$hospitalId]);

if (!$hospital) {
    redirect('/404.html');
}

// 设置页面信息
$pageTitle = h($hospital['name']) . " - 医院详情 - " . SITE_NAME;
$pageDescription = $hospital['introduction'] ? h(truncate(strip_tags($hospital['introduction']), 160)) : h($hospital['name']) . "医院详情信息";
$pageKeywords = h($hospital['name']) . ",医院信息,医院科室,专家团队";

// 获取医院科室和医生
$departments = $db->fetchAll("
    SELECT c.*, COUNT(d.id) as doctor_count
    FROM categories c
    LEFT JOIN doctors d ON c.id = d.category_id AND d.hospital_id = ? AND d.status = 'active'
    WHERE c.parent_id = 0 AND EXISTS (
        SELECT 1 FROM doctors d2 WHERE d2.category_id = c.id AND d2.hospital_id = ? AND d2.status = 'active'
    )
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
", [$hospitalId, $hospitalId]);

// 获取医院医生（按科室分组）
$doctorsByDepartment = [];
foreach ($departments as $dept) {
    $doctors = $db->fetchAll("
        SELECT d.*, c.name as category_name
        FROM doctors d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.hospital_id = ? AND d.category_id = ? AND d.status = 'active'
        ORDER BY d.rating DESC, d.title DESC
        LIMIT 6
    ", [$hospitalId, $dept['id']]);
    
    if ($doctors) {
        $doctorsByDepartment[$dept['id']] = $doctors;
    }
}

// 获取医院评论
$comments = $db->fetchAll("
    SELECT c.*, u.username, u.avatar
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.target_type = 'hospital' 
    AND c.target_id = ? 
    AND c.status = 'active'
    ORDER BY c.created_at DESC
    LIMIT 10
", [$hospitalId]);

// 获取相关医院
$relatedHospitals = $db->fetchAll("
    SELECT h.*, 
           (SELECT COUNT(*) FROM doctors d WHERE d.hospital_id = h.id AND d.status = 'active') as doctor_count
    FROM hospitals h 
    WHERE h.status = 'active' 
    AND h.city = ? 
    AND h.id != ?
    ORDER BY h.rating DESC, h.level DESC
    LIMIT 4
", [$hospital['city'], $hospitalId]);

// 添加页面特定的CSS
$pageCSS = ['/assets/css/hospitals.css'];

include '../templates/header.php';
?>

<div class="hospital-detail-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '医院查询', 'url' => '/hospitals/'],
                ['title' => $hospital['city'] . '医院', 'url' => '/hospitals/?city=' . urlencode($hospital['city'])],
                ['title' => truncate($hospital['name'], 30)]
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <!-- 医院基本信息 -->
        <div class="hospital-header">
            <div class="hospital-info">
                <div class="hospital-title">
                    <h1><?php echo h($hospital['name']); ?></h1>
                    <div class="hospital-badges">
                        <span class="level-badge level-<?php echo str_replace(['甲', '乙'], ['a', 'b'], $hospital['level']); ?>">
                            <?php echo h($hospital['level']); ?>
                        </span>
                        <span class="type-badge">
                            <?php echo h($hospital['type']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="hospital-rating">
                    <div class="rating-stars">
                        <?php
                        $rating = floatval($hospital['rating']);
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $rating):
                        ?>
                            <i class="fas fa-star"></i>
                        <?php elseif ($i - 0.5 <= $rating): ?>
                            <i class="fas fa-star-half-alt"></i>
                        <?php else: ?>
                            <i class="far fa-star"></i>
                        <?php endif; endfor; ?>
                    </div>
                    <span class="rating-score"><?php echo number_format($rating, 1); ?></span>
                    <span class="rating-text">综合评分</span>
                </div>
            </div>
            
            <div class="hospital-actions">
                <button class="btn btn-primary favorite-btn" 
                        data-type="hospital" 
                        data-id="<?php echo $hospital['id']; ?>">
                    <i class="fas fa-heart"></i>
                    收藏医院
                </button>
                
                <button class="btn btn-secondary share-btn" 
                        data-url="<?php echo SITE_URL . '/hospitals/detail.php?id=' . $hospital['id']; ?>" 
                        data-title="<?php echo h($hospital['name']); ?>">
                    <i class="fas fa-share"></i>
                    分享
                </button>
                
                <?php if ($hospital['phone']): ?>
                    <a href="tel:<?php echo h($hospital['phone']); ?>" class="btn btn-outline">
                        <i class="fas fa-phone"></i>
                        <?php echo h($hospital['phone']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="hospital-layout">
            <!-- 主要内容 -->
            <main class="hospital-main">
                <!-- 医院详细信息 -->
                <section class="hospital-details">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        医院信息
                    </h2>
                    
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                医院地址
                            </div>
                            <div class="detail-value">
                                <?php echo h($hospital['address']); ?>
                                <?php if ($hospital['latitude'] && $hospital['longitude']): ?>
                                    <button class="map-btn" 
                                            data-lat="<?php echo $hospital['latitude']; ?>" 
                                            data-lng="<?php echo $hospital['longitude']; ?>"
                                            data-name="<?php echo h($hospital['name']); ?>">
                                        <i class="fas fa-map"></i>
                                        查看地图
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($hospital['phone']): ?>
                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="fas fa-phone"></i>
                                    联系电话
                                </div>
                                <div class="detail-value">
                                    <a href="tel:<?php echo h($hospital['phone']); ?>">
                                        <?php echo h($hospital['phone']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($hospital['website']): ?>
                            <div class="detail-item">
                                <div class="detail-label">
                                    <i class="fas fa-globe"></i>
                                    官方网站
                                </div>
                                <div class="detail-value">
                                    <a href="<?php echo h($hospital['website']); ?>" target="_blank" rel="noopener">
                                        <?php echo h($hospital['website']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-building"></i>
                                医院等级
                            </div>
                            <div class="detail-value">
                                <?php echo h($hospital['level']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-hospital"></i>
                                医院类型
                            </div>
                            <div class="detail-value">
                                <?php echo h($hospital['type']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-user-md"></i>
                                医生数量
                            </div>
                            <div class="detail-value">
                                <?php echo array_sum(array_column($departments, 'doctor_count')); ?> 位医生
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- 医院介绍 -->
                <?php if ($hospital['introduction']): ?>
                    <section class="hospital-introduction">
                        <h2 class="section-title">
                            <i class="fas fa-file-alt"></i>
                            医院介绍
                        </h2>
                        <div class="introduction-content">
                            <?php echo nl2br(stripTags($hospital['introduction'])); ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- 特色科室 -->
                <?php if ($hospital['specialties']): ?>
                    <section class="hospital-specialties">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i>
                            特色科室
                        </h2>
                        <div class="specialties-content">
                            <?php echo nl2br(h($hospital['specialties'])); ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- 医疗设备 -->
                <?php if ($hospital['equipment']): ?>
                    <section class="hospital-equipment">
                        <h2 class="section-title">
                            <i class="fas fa-cogs"></i>
                            医疗设备
                        </h2>
                        <div class="equipment-content">
                            <?php echo nl2br(h($hospital['equipment'])); ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- 科室医生 -->
                <?php if ($departments): ?>
                    <section class="hospital-departments">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>
                            科室医生
                        </h2>
                        
                        <div class="departments-tabs">
                            <div class="tab-buttons">
                                <?php foreach ($departments as $index => $dept): ?>
                                    <button class="tab-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                                            data-tab="dept-<?php echo $dept['id']; ?>">
                                        <?php echo h($dept['name']); ?>
                                        <span class="count">(<?php echo $dept['doctor_count']; ?>)</span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="tab-contents">
                                <?php foreach ($departments as $index => $dept): ?>
                                    <div class="tab-content <?php echo $index === 0 ? 'active' : ''; ?>" 
                                         id="dept-<?php echo $dept['id']; ?>">
                                        
                                        <?php if (isset($doctorsByDepartment[$dept['id']])): ?>
                                            <div class="doctors-grid">
                                                <?php foreach ($doctorsByDepartment[$dept['id']] as $doctor): ?>
                                                    <div class="doctor-card">
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
                                                            <h4>
                                                                <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>">
                                                                    <?php echo h($doctor['name']); ?>
                                                                </a>
                                                            </h4>
                                                            <p class="doctor-title"><?php echo h($doctor['title']); ?></p>
                                                            
                                                            <?php if ($doctor['specialties']): ?>
                                                                <p class="doctor-specialties">
                                                                    擅长：<?php echo h(truncate($doctor['specialties'], 80)); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            
                                                            <div class="doctor-rating">
                                                                <?php
                                                                $doctorRating = floatval($doctor['rating']);
                                                                for ($i = 1; $i <= 5; $i++):
                                                                    if ($i <= $doctorRating):
                                                                ?>
                                                                    <i class="fas fa-star"></i>
                                                                <?php elseif ($i - 0.5 <= $doctorRating): ?>
                                                                    <i class="fas fa-star-half-alt"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star"></i>
                                                                <?php endif; endfor; ?>
                                                                <span><?php echo number_format($doctorRating, 1); ?></span>
                                                            </div>
                                                            
                                                            <div class="doctor-actions">
                                                                <a href="/doctors/detail.php?id=<?php echo $doctor['id']; ?>" 
                                                                   class="btn btn-sm btn-primary">查看详情</a>
                                                                <a href="/user/appointment.php?doctor_id=<?php echo $doctor['id']; ?>" 
                                                                   class="btn btn-sm btn-secondary">预约挂号</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <?php if ($dept['doctor_count'] > 6): ?>
                                                <div class="view-more-doctors">
                                                    <a href="/doctors/?hospital_id=<?php echo $hospital['id']; ?>&category=<?php echo $dept['id']; ?>" 
                                                       class="btn btn-outline">
                                                        查看<?php echo h($dept['name']); ?>全部医生 
                                                        (<?php echo $dept['doctor_count']; ?>位)
                                                        <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <div class="no-doctors">
                                                <p>该科室医生信息正在完善中...</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- 用户评价 -->
                <section class="hospital-reviews">
                    <h2 class="section-title">
                        <i class="fas fa-comments"></i>
                        用户评价 (<?php echo count($comments); ?>)
                    </h2>
                    
                    <!-- 评价表单 -->
                    <?php if (isLoggedIn()): ?>
                        <form class="review-form" data-ajax action="/api/comments.php" method="POST">
                            <input type="hidden" name="target_type" value="hospital">
                            <input type="hidden" name="target_id" value="<?php echo $hospital['id']; ?>">
                            
                            <div class="form-group">
                                <textarea name="content" placeholder="分享您在这家医院的就医体验..." 
                                          rows="4" required></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <label class="anonymous-option">
                                    <input type="checkbox" name="is_anonymous" value="1">
                                    匿名评价
                                </label>
                                
                                <button type="submit" class="btn btn-primary">
                                    发表评价
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p>
                                <i class="fas fa-sign-in-alt"></i>
                                <a href="/user/login.php">登录</a> 后才能发表评价
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 评价列表 -->
                    <?php if ($comments): ?>
                        <div class="reviews-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="review-item">
                                    <div class="review-avatar">
                                        <?php if ($comment['avatar'] && !$comment['is_anonymous']): ?>
                                            <img src="<?php echo h($comment['avatar']); ?>" 
                                                 alt="<?php echo h($comment['username']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="review-content">
                                        <div class="review-header">
                                            <span class="username">
                                                <?php echo $comment['is_anonymous'] ? '匿名用户' : h($comment['username']); ?>
                                            </span>
                                            <span class="review-time">
                                                <?php echo formatTime($comment['created_at']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="review-text">
                                            <?php echo nl2br(h($comment['content'])); ?>
                                        </div>
                                        
                                        <div class="review-actions">
                                            <button class="review-like like-btn" 
                                                    data-type="comment" 
                                                    data-id="<?php echo $comment['id']; ?>">
                                                <i class="fas fa-thumbs-up"></i>
                                                <span class="like-count"><?php echo $comment['like_count']; ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reviews">
                            <div class="no-reviews-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <p>暂无用户评价，快来发表第一个评价吧！</p>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="hospital-sidebar">
                <!-- 快速预约 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-calendar-check"></i>
                        快速预约
                    </h3>
                    <div class="quick-appointment">
                        <p>选择科室和医生，在线预约挂号</p>
                        <a href="/doctors/?hospital_id=<?php echo $hospital['id']; ?>" 
                           class="btn btn-primary btn-block">
                            <i class="fas fa-stethoscope"></i>
                            选择医生预约
                        </a>
                    </div>
                </div>
                
                <!-- 联系信息 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-address-card"></i>
                        联系方式
                    </h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>医院地址</strong>
                                <p><?php echo h($hospital['address']); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($hospital['phone']): ?>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <strong>联系电话</strong>
                                    <p>
                                        <a href="tel:<?php echo h($hospital['phone']); ?>">
                                            <?php echo h($hospital['phone']); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($hospital['website']): ?>
                            <div class="contact-item">
                                <i class="fas fa-globe"></i>
                                <div>
                                    <strong>官方网站</strong>
                                    <p>
                                        <a href="<?php echo h($hospital['website']); ?>" target="_blank" rel="noopener">
                                            访问官网
                                        </a>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 相关医院 -->
                <?php if ($relatedHospitals): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-hospital"></i>
                            同城医院
                        </h3>
                        <div class="related-hospitals">
                            <?php foreach ($relatedHospitals as $related): ?>
                                <div class="related-item">
                                    <div class="related-info">
                                        <h4>
                                            <a href="/hospitals/detail.php?id=<?php echo $related['id']; ?>">
                                                <?php echo h(truncate($related['name'], 35)); ?>
                                            </a>
                                        </h4>
                                        <div class="related-meta">
                                            <span class="level"><?php echo h($related['level']); ?></span>
                                            <span class="doctors"><?php echo $related['doctor_count']; ?>位医生</span>
                                        </div>
                                        <div class="related-rating">
                                            <?php
                                            $relatedRating = floatval($related['rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $relatedRating):
                                            ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $relatedRating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; endfor; ?>
                                            <span><?php echo number_format($relatedRating, 1); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>

<!-- 地图弹窗 -->
<div id="mapModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="mapTitle">医院位置</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="hospitalMap" style="height: 400px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
                <div style="text-align: center;">
                    <i class="fas fa-map-marker-alt" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    <p><strong><?php echo h($hospital['name']); ?></strong></p>
                    <p><?php echo h($hospital['address']); ?></p>
                    <p style="font-size: 12px; margin-top: 10px; color: #999;">地图功能需要集成第三方地图服务</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 科室标签切换
    $('.tab-btn').on('click', function() {
        const targetTab = $(this).data('tab');
        
        // 激活按钮
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // 显示对应内容
        $('.tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');
    });
    
    // 地图弹窗
    $('.map-btn').on('click', function() {
        const name = $(this).data('name');
        $('#mapTitle').text(name + ' - 位置地图');
        $('#mapModal').fadeIn(300);
    });
    
    $('.modal-close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#mapModal').fadeOut(300);
        }
    });
    
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // 分享功能
    $('.share-btn').on('click', function() {
        const url = $(this).data('url');
        const title = $(this).data('title');
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // 复制链接到剪贴板
            navigator.clipboard.writeText(url).then(() => {
                showMessage('医院链接已复制到剪贴板', 'success');
            });
        }
    });
});
</script>

<?php include '../templates/footer.php'; ?>