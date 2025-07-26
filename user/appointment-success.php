<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php');
    exit;
}

// 获取预约ID
$appointmentId = intval($_GET['id'] ?? 0);
if (!$appointmentId) {
    header('Location: /user/profile.php#appointments');
    exit;
}

// 获取预约详细信息
$appointment = $db->fetch("
    SELECT a.*, d.name as doctor_name, d.title as doctor_title, d.avatar as doctor_avatar,
           h.name as hospital_name, h.address as hospital_address, h.phone as hospital_phone,
           c.name as category_name
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE a.id = ? AND a.user_id = ?
", [$appointmentId, $currentUser['id']]);

if (!$appointment) {
    header('Location: /user/profile.php#appointments');
    exit;
}

// 设置页面信息
$pageTitle = "预约成功 - " . SITE_NAME;
$pageDescription = "您的预约已成功提交";
$pageKeywords = "预约成功,挂号成功";

// 添加页面特定的CSS
$pageCSS = ['/assets/css/appointment.css'];

include '../templates/header.php';
?>

<div class="appointment-success-page">
    <div class="container">
        <div class="success-content">
            <!-- 成功状态 -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>预约成功！</h1>
                <p>您的预约已成功提交，请按时到院就诊</p>
            </div>
            
            <!-- 预约详情 -->
            <div class="appointment-details">
                <h2>预约详情</h2>
                
                <div class="details-grid">
                    <div class="detail-card">
                        <div class="card-header">
                            <i class="fas fa-receipt"></i>
                            <h3>预约信息</h3>
                        </div>
                        <div class="card-content">
                            <div class="detail-item">
                                <strong>预约号：</strong>
                                <span class="appointment-number"><?php echo h($appointment['appointment_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>预约时间：</strong>
                                <span><?php echo date('Y年m月d日', strtotime($appointment['appointment_date'])); ?> <?php echo $appointment['appointment_time']; ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>预约状态：</strong>
                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                    <?php 
                                    switch($appointment['status']) {
                                        case 'pending': echo '待确认'; break;
                                        case 'confirmed': echo '已确认'; break;
                                        case 'cancelled': echo '已取消'; break;
                                        case 'completed': echo '已完成'; break;
                                        default: echo '未知状态';
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if ($appointment['is_urgent']): ?>
                                <div class="detail-item">
                                    <strong>紧急标识：</strong>
                                    <span class="urgent-badge">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        紧急预约
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-header">
                            <i class="fas fa-user-md"></i>
                            <h3>医生信息</h3>
                        </div>
                        <div class="card-content">
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <?php if ($appointment['doctor_avatar']): ?>
                                        <img src="<?php echo h($appointment['doctor_avatar']); ?>" alt="<?php echo h($appointment['doctor_name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="doctor-details">
                                    <h4><?php echo h($appointment['doctor_name']); ?></h4>
                                    <div class="doctor-title"><?php echo h($appointment['doctor_title']); ?></div>
                                    <div class="doctor-category"><?php echo h($appointment['category_name']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-header">
                            <i class="fas fa-hospital"></i>
                            <h3>医院信息</h3>
                        </div>
                        <div class="card-content">
                            <div class="detail-item">
                                <strong>医院名称：</strong>
                                <span><?php echo h($appointment['hospital_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>医院地址：</strong>
                                <span><?php echo h($appointment['hospital_address']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>联系电话：</strong>
                                <span><?php echo h($appointment['hospital_phone']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="card-header">
                            <i class="fas fa-user"></i>
                            <h3>患者信息</h3>
                        </div>
                        <div class="card-content">
                            <div class="detail-item">
                                <strong>患者姓名：</strong>
                                <span><?php echo h($appointment['patient_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>联系电话：</strong>
                                <span><?php echo h($appointment['patient_phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>年龄性别：</strong>
                                <span><?php echo $appointment['patient_age']; ?>岁 / <?php echo $appointment['patient_gender'] == 'male' ? '男' : '女'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($appointment['symptoms']): ?>
                    <div class="symptoms-section">
                        <h3>症状描述</h3>
                        <div class="symptoms-content">
                            <?php echo nl2br(h($appointment['symptoms'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 重要提醒 -->
            <div class="important-notes">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i>
                    重要提醒
                </h2>
                <div class="notes-grid">
                    <div class="note-item">
                        <div class="note-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="note-content">
                            <h4>提前到达</h4>
                            <p>请于预约时间前30-60分钟到达医院，办理相关手续</p>
                        </div>
                    </div>
                    
                    <div class="note-item">
                        <div class="note-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="note-content">
                            <h4>携带证件</h4>
                            <p>请携带有效身份证件、医保卡、既往病历等相关资料</p>
                        </div>
                    </div>
                    
                    <div class="note-item">
                        <div class="note-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="note-content">
                            <h4>保持通讯</h4>
                            <p>请保持预留手机号码畅通，以便医院联系确认</p>
                        </div>
                    </div>
                    
                    <div class="note-item">
                        <div class="note-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="note-content">
                            <h4>取消预约</h4>
                            <p>如需取消预约，请提前24小时联系医院或在线取消</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 操作按钮 -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    打印预约单
                </button>
                
                <a href="/user/profile.php#appointments" class="btn btn-outline">
                    <i class="fas fa-list"></i>
                    查看所有预约
                </a>
                
                <a href="/doctors/detail.php?id=<?php echo $appointment['doctor_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-user-md"></i>
                    返回医生详情
                </a>
                
                <a href="/" class="btn btn-outline">
                    <i class="fas fa-home"></i>
                    返回首页
                </a>
            </div>
            
            <!-- 联系方式 -->
            <div class="contact-info">
                <h3>需要帮助？</h3>
                <div class="contact-methods">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>客服热线</strong>
                            <span>400-123-4567</span>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>邮箱咨询</strong>
                            <span>support@health.com</span>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-comments"></i>
                        <div>
                            <strong>在线客服</strong>
                            <span>工作日 9:00-18:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 预约成功页面特殊样式 */
.appointment-success-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 0;
}

.success-content {
    max-width: 1000px;
    margin: 0 auto;
}

.success-header {
    text-align: center;
    color: white;
    margin-bottom: 40px;
}

.success-icon {
    font-size: 80px;
    margin-bottom: 20px;
    color: #10b981;
}

.success-header h1 {
    font-size: 36px;
    margin: 0 0 15px 0;
    font-weight: 700;
}

.success-header p {
    font-size: 18px;
    margin: 0;
    opacity: 0.9;
}

.appointment-details {
    background: white;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.appointment-details h2 {
    margin: 0 0 30px 0;
    color: #1f2937;
    font-size: 24px;
    font-weight: 700;
    text-align: center;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.detail-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    background: #f9fafb;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header i {
    color: #4f46e5;
    font-size: 18px;
}

.card-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.card-content {
    padding: 20px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f3f4f6;
}

.detail-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
}

.detail-item strong {
    color: #6b7280;
    font-size: 14px;
}

.detail-item span {
    color: #1f2937;
    font-weight: 500;
}

.appointment-number {
    font-family: monospace;
    background: #eef2ff;
    color: #4f46e5;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.status-badge.status-pending {
    background: #f59e0b;
}

.status-badge.status-confirmed {
    background: #10b981;
}

.status-badge.status-cancelled {
    background: #ef4444;
}

.status-badge.status-completed {
    background: #6b7280;
}

.urgent-badge {
    background: #fee2e2;
    color: #dc2626;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.doctor-info {
    display: flex;
    gap: 15px;
    align-items: center;
}

.doctor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.doctor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.doctor-avatar .avatar-placeholder {
    width: 60px;
    height: 60px;
    background: #f3f4f6;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 24px;
}

.doctor-details h4 {
    margin: 0 0 5px 0;
    color: #1f2937;
    font-size: 18px;
}

.doctor-title, .doctor-category {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 2px;
}

.symptoms-section {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
}

.symptoms-section h3 {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 18px;
    font-weight: 600;
}

.symptoms-content {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    color: #6b7280;
    line-height: 1.6;
}

.important-notes {
    background: white;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.important-notes h2 {
    margin: 0 0 30px 0;
    color: #dc2626;
    font-size: 24px;
    font-weight: 700;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.note-item {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
}

.note-icon {
    width: 40px;
    height: 40px;
    background: #f59e0b;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.note-content h4 {
    margin: 0 0 8px 0;
    color: #92400e;
    font-size: 16px;
    font-weight: 600;
}

.note-content p {
    margin: 0;
    color: #92400e;
    font-size: 14px;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.contact-info {
    background: white;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.contact-info h3 {
    margin: 0 0 25px 0;
    color: #1f2937;
    font-size: 20px;
    font-weight: 600;
}

.contact-methods {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-item i {
    color: #4f46e5;
    font-size: 20px;
}

.contact-item div strong {
    display: block;
    color: #1f2937;
    font-size: 14px;
    margin-bottom: 2px;
}

.contact-item div span {
    color: #6b7280;
    font-size: 13px;
}

@media print {
    .appointment-success-page {
        background: white !important;
        padding: 0 !important;
    }
    
    .success-header {
        color: #1f2937 !important;
    }
    
    .action-buttons,
    .contact-info {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .success-header h1 {
        font-size: 28px;
    }
    
    .success-icon {
        font-size: 60px;
    }
    
    .appointment-details,
    .important-notes,
    .contact-info {
        padding: 25px;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .notes-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .contact-methods {
        flex-direction: column;
        gap: 20px;
    }
}
</style>

<script>
$(document).ready(function() {
    // 自动选中预约号以便复制
    $('.appointment-number').on('click', function() {
        const range = document.createRange();
        range.selectNode(this);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        
        // 尝试复制到剪贴板
        try {
            document.execCommand('copy');
            showMessage('预约号已复制到剪贴板', 'success');
        } catch (err) {
            console.log('复制失败');
        }
        
        window.getSelection().removeAllRanges();
    });
    
    // 打印功能
    $('button[onclick="window.print()"]').on('click', function(e) {
        e.preventDefault();
        window.print();
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