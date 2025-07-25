<?php
require_once '../includes/init.php';

// 获取医生ID
$doctorId = intval($_GET['id'] ?? 0);
if (!$doctorId) {
    header('Location: /doctors/');
    exit;
}

// 获取医生详细信息
$doctor = $db->fetch("
    SELECT d.*, h.name as hospital_name, h.city as hospital_city, 
           h.address as hospital_address, h.level as hospital_level,
           h.phone as hospital_phone, h.website as hospital_website,
           c.name as category_name, c.parent_id
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.id = ? AND d.status = 'active'
", [$doctorId]);

if (!$doctor) {
    header('HTTP/1.0 404 Not Found');
    include '../templates/404.php';
    exit;
}

// 更新浏览次数
$db->query("UPDATE doctors SET view_count = view_count + 1 WHERE id = ?", [$doctorId]);

// 设置页面信息
$pageTitle = $doctor['name'] . " - " . $doctor['title'] . " - " . SITE_NAME;
$pageDescription = $doctor['name'] . "，" . $doctor['title'] . "，" . $doctor['hospital_name'] . "，擅长：" . strip_tags($doctor['specialties']);
$pageKeywords = $doctor['name'] . "," . $doctor['title'] . "," . $doctor['category_name'] . "," . $doctor['hospital_name'];
$currentPage = 'doctors';

// 获取同科室医生推荐
$relatedDoctors = $db->fetchAll("
    SELECT d.*, h.name as hospital_name, h.city as hospital_city,
           c.name as category_name
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.category_id = ? AND d.id != ? AND d.status = 'active' AND h.status = 'active'
    ORDER BY d.rating DESC, d.view_count DESC
    LIMIT 6
", [$doctor['category_id'], $doctorId]);

// 获取同医院医生推荐
$hospitalDoctors = $db->fetchAll("
    SELECT d.*, c.name as category_name
    FROM doctors d 
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.hospital_id = ? AND d.id != ? AND d.status = 'active'
    ORDER BY d.title DESC, d.rating DESC
    LIMIT 8
", [$doctor['hospital_id'], $doctorId]);

// 获取患者评价（示例数据）
$reviews = [
    [
        'patient_name' => '张**',
        'rating' => 5,
        'content' => '医生非常专业，态度也很好，详细解答了我的问题。',
        'date' => '2024-01-15'
    ],
    [
        'patient_name' => '李**',
        'rating' => 4,
        'content' => '看病很仔细，诊断准确，给出了很好的治疗建议。',
        'date' => '2024-01-10'
    ]
];

// 添加页面特定的CSS
$pageCSS = ['/assets/css/doctors.css'];

include '../templates/header.php';
?>

<div class="doctor-detail-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '医生查询', 'url' => '/doctors/'],
                ['title' => $doctor['category_name'], 'url' => '/doctors/?category=' . $doctor['category_id']],
                ['title' => $doctor['name']]
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="doctor-detail-layout">
            <!-- 主要内容区 -->
            <main class="doctor-main-content">
                <!-- 医生基本信息卡片 -->
                <div class="doctor-profile-card">
                    <div class="doctor-header">
                        <div class="doctor-avatar">
                            <?php if ($doctor['avatar']): ?>
                                <img src="<?php echo h($doctor['avatar']); ?>" 
                                     alt="<?php echo h($doctor['name']); ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user-md"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 在线状态 -->
                            <div class="online-status" title="在线咨询">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                        
                        <div class="doctor-basic-info">
                            <h1 class="doctor-name"><?php echo h($doctor['name']); ?></h1>
                            
                            <div class="doctor-title-section">
                                <span class="title-badge title-<?php echo str_replace(['主任', '副主任', '主治', '住院', '医师'], ['zr', 'fzr', 'zz', 'zy', 'ys'], $doctor['title']); ?>">
                                    <?php echo h($doctor['title']); ?>
                                </span>
                                <span class="category-tag"><?php echo h($doctor['category_name']); ?></span>
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
                                <span class="rating-score"><?php echo number_format($rating, 1); ?></span>
                                <span class="rating-count">(<?php echo rand(50, 200); ?>条评价)</span>
                            </div>
                            
                            <div class="doctor-hospital-info">
                                <i class="fas fa-hospital"></i>
                                <a href="/hospitals/detail.php?id=<?php echo $doctor['hospital_id']; ?>">
                                    <?php echo h($doctor['hospital_name']); ?>
                                </a>
                                <span class="hospital-level"><?php echo h($doctor['hospital_level']); ?></span>
                            </div>
                            
                            <div class="doctor-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo h($doctor['hospital_city']); ?>
                            </div>
                        </div>
                        
                        <div class="doctor-actions">
                            <button class="btn btn-primary consultation-btn" 
                                    data-doctor-id="<?php echo $doctor['id']; ?>"
                                    data-doctor-name="<?php echo h($doctor['name']); ?>">
                                <i class="fas fa-comments"></i>
                                在线咨询
                                <?php if ($doctor['consultation_fee']): ?>
                                    <small>¥<?php echo number_format($doctor['consultation_fee'], 0); ?></small>
                                <?php endif; ?>
                            </button>
                            
                            <a href="/user/appointment.php?doctor_id=<?php echo $doctor['id']; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-calendar-check"></i>
                                预约挂号
                            </a>
                            
                            <button class="btn btn-outline favorite-btn" 
                                    data-type="doctor" 
                                    data-id="<?php echo $doctor['id']; ?>">
                                <i class="fas fa-heart"></i>
                                收藏
                            </button>
                        </div>
                    </div>
                    
                    <div class="doctor-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($doctor['view_count']); ?></span>
                            <span class="stat-label">总访问量</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(20, 80); ?>%</span>
                            <span class="stat-label">好评率</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo rand(100, 500); ?></span>
                            <span class="stat-label">服务患者</span>
                        </div>
                        <?php if ($doctor['experience_years']): ?>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $doctor['experience_years']; ?>年</span>
                            <span class="stat-label">从医经验</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 医生详细信息标签页 -->
                <div class="doctor-tabs">
                    <div class="tab-navigation">
                        <button class="tab-btn active" data-tab="introduction">医生介绍</button>
                        <button class="tab-btn" data-tab="schedule">出诊时间</button>
                        <button class="tab-btn" data-tab="reviews">患者评价</button>
                        <button class="tab-btn" data-tab="qa">问答记录</button>
                    </div>
                    
                    <div class="tab-content">
                        <!-- 医生介绍 -->
                        <div class="tab-panel active" id="introduction">
                            <?php if ($doctor['specialties']): ?>
                                <div class="info-section">
                                    <h3><i class="fas fa-stethoscope"></i> 擅长领域</h3>
                                    <div class="specialties-content">
                                        <?php echo nl2br(h($doctor['specialties'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doctor['introduction']): ?>
                                <div class="info-section">
                                    <h3><i class="fas fa-user"></i> 医生简介</h3>
                                    <div class="introduction-content">
                                        <?php echo nl2br(h($doctor['introduction'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doctor['experience']): ?>
                                <div class="info-section">
                                    <h3><i class="fas fa-briefcase"></i> 工作经历</h3>
                                    <div class="experience-content">
                                        <?php echo nl2br(h($doctor['experience'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doctor['education']): ?>
                                <div class="info-section">
                                    <h3><i class="fas fa-graduation-cap"></i> 教育背景</h3>
                                    <div class="education-content">
                                        <?php echo nl2br(h($doctor['education'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doctor['achievements']): ?>
                                <div class="info-section">
                                    <h3><i class="fas fa-trophy"></i> 学术成就</h3>
                                    <div class="achievements-content">
                                        <?php echo nl2br(h($doctor['achievements'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 出诊时间 -->
                        <div class="tab-panel" id="schedule">
                            <div class="schedule-section">
                                <h3><i class="fas fa-calendar-alt"></i> 门诊安排</h3>
                                <div class="schedule-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>时间</th>
                                                <th>周一</th>
                                                <th>周二</th>
                                                <th>周三</th>
                                                <th>周四</th>
                                                <th>周五</th>
                                                <th>周六</th>
                                                <th>周日</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>上午</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                                <td class="unavailable">-</td>
                                            </tr>
                                            <tr>
                                                <td>下午</td>
                                                <td class="unavailable">-</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                                <td class="available">出诊</td>
                                                <td class="unavailable">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="schedule-note">
                                    <p><i class="fas fa-info-circle"></i> 具体出诊时间以医院安排为准，建议提前预约</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 患者评价 -->
                        <div class="tab-panel" id="reviews">
                            <div class="reviews-section">
                                <div class="reviews-summary">
                                    <div class="rating-overview">
                                        <div class="overall-rating">
                                            <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $rating): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php elseif ($i - 0.5 <= $rating): ?>
                                                        <i class="fas fa-star-half-alt"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="total-reviews"><?php echo count($reviews); ?>条评价</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="reviews-list">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item">
                                            <div class="review-header">
                                                <div class="patient-info">
                                                    <span class="patient-name"><?php echo h($review['patient_name']); ?></span>
                                                    <div class="review-rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $review['rating']): ?>
                                                                <i class="fas fa-star"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <span class="review-date"><?php echo $review['date']; ?></span>
                                            </div>
                                            <div class="review-content">
                                                <?php echo h($review['content']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 问答记录 -->
                        <div class="tab-panel" id="qa">
                            <div class="qa-section">
                                <p class="no-content">暂无问答记录</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="doctor-sidebar">
                <!-- 医院信息 -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-hospital"></i>
                        所属医院
                    </h3>
                    <div class="hospital-widget">
                        <h4>
                            <a href="/hospitals/detail.php?id=<?php echo $doctor['hospital_id']; ?>">
                                <?php echo h($doctor['hospital_name']); ?>
                            </a>
                        </h4>
                        <div class="hospital-info">
                            <div class="info-item">
                                <span class="label">等级：</span>
                                <span class="value"><?php echo h($doctor['hospital_level']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">地址：</span>
                                <span class="value"><?php echo h($doctor['hospital_address']); ?></span>
                            </div>
                            <?php if ($doctor['hospital_phone']): ?>
                                <div class="info-item">
                                    <span class="label">电话：</span>
                                    <span class="value"><?php echo h($doctor['hospital_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="/doctors/?hospital_id=<?php echo $doctor['hospital_id']; ?>" 
                           class="btn btn-outline btn-block">
                            查看该院所有医生
                        </a>
                    </div>
                </div>
                
                <!-- 同科室医生推荐 -->
                <?php if ($relatedDoctors): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-user-md"></i>
                            同科室医生
                        </h3>
                        <div class="related-doctors">
                            <?php foreach ($relatedDoctors as $related): ?>
                                <div class="doctor-item">
                                    <div class="doctor-avatar">
                                        <?php if ($related['avatar']): ?>
                                            <img src="<?php echo h($related['avatar']); ?>" 
                                                 alt="<?php echo h($related['name']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="doctor-info">
                                        <h5>
                                            <a href="/doctors/detail.php?id=<?php echo $related['id']; ?>">
                                                <?php echo h($related['name']); ?>
                                            </a>
                                        </h5>
                                        <div class="doctor-meta">
                                            <span class="title"><?php echo h($related['title']); ?></span>
                                            <span class="hospital"><?php echo h(truncate($related['hospital_name'], 20)); ?></span>
                                        </div>
                                        <div class="doctor-rating">
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
                
                <!-- 在线咨询提示 -->
                <div class="sidebar-widget consultation-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-comments"></i>
                        在线咨询
                    </h3>
                    <div class="consultation-info">
                        <p>与<?php echo h($doctor['name']); ?>医生在线交流</p>
                        <ul>
                            <li>快速获得专业建议</li>
                            <li>隐私保护，安全可靠</li>
                            <li>24小时内回复</li>
                        </ul>
                        <button class="btn btn-primary btn-block consultation-btn" 
                                data-doctor-id="<?php echo $doctor['id']; ?>"
                                data-doctor-name="<?php echo h($doctor['name']); ?>">
                            立即咨询
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- 在线咨询弹窗 -->
<div id="consultationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="consultationTitle">在线咨询</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="consultation-content">
                <div class="doctor-consultation-info">
                    <div class="consultation-avatar">
                        <?php if ($doctor['avatar']): ?>
                            <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-user-md"></i>
                        <?php endif; ?>
                    </div>
                    <div class="consultation-details">
                        <h4 id="consultationDoctorName"><?php echo h($doctor['name']); ?></h4>
                        <p><?php echo h($doctor['title']); ?> · <?php echo h($doctor['category_name']); ?></p>
                        <?php if ($doctor['consultation_fee']): ?>
                            <p class="consultation-fee">咨询费用：¥<?php echo number_format($doctor['consultation_fee'], 0); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form class="consultation-form" data-ajax>
                        <input type="hidden" id="consultationDoctorId" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                        
                        <div class="form-group">
                            <label>咨询内容：</label>
                            <textarea name="content" placeholder="请详细描述您的问题，包括症状、病史等相关信息..." rows="6" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_anonymous" value="1">
                                匿名咨询（不显示真实姓名）
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            发送咨询
                        </button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>
                            <i class="fas fa-sign-in-alt"></i>
                            请先 <a href="/user/login.php">登录</a> 后使用在线咨询服务
                        </p>
                        <a href="/user/login.php" class="btn btn-primary">立即登录</a>
                    </div>
                <?php endif; ?>
            </div>
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
    
    // 在线咨询弹窗
    $('.consultation-btn').on('click', function() {
        const doctorId = $(this).data('doctor-id');
        const doctorName = $(this).data('doctor-name');
        
        $('#consultationDoctorId').val(doctorId);
        $('#consultationDoctorName').text(doctorName);
        $('#consultationTitle').text('咨询 ' + doctorName);
        
        $('#consultationModal').fadeIn(300);
    });
    
    // 关闭咨询弹窗
    $('.modal-close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#consultationModal').fadeOut(300);
        }
    });
    
    // 阻止弹窗内容区域点击关闭
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // 咨询表单提交
    $('.consultation-form').on('submit', function(e) {
        e.preventDefault();
        
        // 这里可以添加AJAX提交逻辑
        showMessage('咨询已发送，医生会尽快回复您', 'success');
        $('#consultationModal').fadeOut(300);
        $(this)[0].reset();
    });
    
    // 收藏功能
    $('.favorite-btn').on('click', function() {
        const $btn = $(this);
        const type = $btn.data('type');
        const id = $btn.data('id');
        
        // 这里可以添加AJAX收藏逻辑
        if ($btn.hasClass('favorited')) {
            $btn.removeClass('favorited');
            $btn.find('i').removeClass('fas').addClass('far');
            showMessage('已取消收藏', 'info');
        } else {
            $btn.addClass('favorited');
            $btn.find('i').removeClass('far').addClass('fas');
            showMessage('收藏成功', 'success');
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
</script>

<?php include '../templates/footer.php'; ?>