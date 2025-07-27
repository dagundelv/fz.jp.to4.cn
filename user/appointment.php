<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/user/appointment.php?' . $_SERVER['QUERY_STRING']));
    exit;
}

// 获取医生ID
$doctorId = intval($_GET['doctor_id'] ?? 0);
if (!$doctorId) {
    header('Location: /doctors/');
    exit;
}

// 获取医生信息
$doctor = $db->fetch("
    SELECT d.*, h.name as hospital_name, h.address as hospital_address, 
           h.phone as hospital_phone, c.name as category_name
    FROM doctors d 
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.id = ? AND d.status = 'active'
", [$doctorId]);

if (!$doctor) {
    header('Location: /doctors/');
    exit;
}

// 设置页面信息
$pageTitle = "预约挂号 - " . $doctor['name'] . " - " . SITE_NAME;
$pageDescription = "预约" . $doctor['name'] . "医生的挂号服务";
$pageKeywords = "预约挂号,医生预约," . $doctor['name'];

// 处理预约提交
$appointmentError = '';
$appointmentSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = $_POST['appointment_time'] ?? '';
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientPhone = trim($_POST['patient_phone'] ?? '');
    $patientAge = intval($_POST['patient_age'] ?? 0);
    $patientGender = $_POST['patient_gender'] ?? '';
    $symptoms = trim($_POST['symptoms'] ?? '');
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    // 验证输入
    if (empty($appointmentDate)) {
        $appointmentError = '请选择预约日期';
    } elseif (empty($appointmentTime)) {
        $appointmentError = '请选择预约时间';
    } elseif (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
        $appointmentError = '预约日期不能是过去的日期';
    } elseif (empty($patientName)) {
        $appointmentError = '请输入患者姓名';
    } elseif (empty($patientPhone)) {
        $appointmentError = '请输入联系电话';
    } elseif (!preg_match('/^1[3-9]\d{9}$/', $patientPhone)) {
        $appointmentError = '请输入有效的手机号码';
    } elseif ($patientAge < 1 || $patientAge > 120) {
        $appointmentError = '请输入有效的年龄';
    } elseif (empty($patientGender)) {
        $appointmentError = '请选择患者性别';
    } elseif (empty($symptoms)) {
        $appointmentError = '请描述主要症状';
    } else {
        // 检查时间段是否已被预约
        $existingAppointment = $db->fetch("
            SELECT id FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
            AND status IN ('pending', 'confirmed')
        ", [$doctorId, $appointmentDate, $appointmentTime]);
        
        if ($existingAppointment) {
            $appointmentError = '该时间段已被预约，请选择其他时间';
        } else {
            try {
                // 生成预约号
                $appointmentNumber = 'AP' . date('Ymd') . str_pad($doctorId, 4, '0', STR_PAD_LEFT) . rand(1000, 9999);
                
                $appointmentId = $db->query("
                    INSERT INTO appointments (
                        appointment_number, user_id, doctor_id, appointment_date, appointment_time,
                        patient_name, patient_phone, patient_age, patient_gender, symptoms,
                        is_urgent, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ", [
                    $appointmentNumber, $currentUser['id'], $doctorId, $appointmentDate, $appointmentTime,
                    $patientName, $patientPhone, $patientAge, $patientGender, $symptoms, $isUrgent
                ]);
                
                $appointmentSuccess = true;
                
                // 重定向到预约成功页面
                header('Location: /user/appointment-success.php?id=' . $appointmentId);
                exit;
                
            } catch (Exception $e) {
                $appointmentError = '预约失败，请稍后重试';
            }
        }
    }
}

// 生成可预约的日期（未来7天）
$availableDates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+{$i} days"));
    $dayOfWeek = date('w', strtotime($date));
    
    // 假设周末不出诊
    if ($dayOfWeek != 0 && $dayOfWeek != 6) {
        $availableDates[] = [
            'date' => $date,
            'display' => date('m月d日', strtotime($date)),
            'weekday' => ['日', '一', '二', '三', '四', '五', '六'][$dayOfWeek]
        ];
    }
}

// 生成可预约的时间段
$timeSlots = [
    'morning' => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'],
    'afternoon' => ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30']
];

// 添加页面特定的CSS
$pageCSS = ['/assets/css/appointment.css'];

include '../templates/header.php';
?>

<div class="appointment-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '医生查询', 'url' => '/doctors/'],
                ['title' => $doctor['name'], 'url' => '/doctors/detail.php?id=' . $doctorId],
                ['title' => '预约挂号']
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="appointment-layout">
            <!-- 医生信息卡片 -->
            <aside class="doctor-info-card">
                <div class="doctor-header">
                    <div class="doctor-avatar">
                        <?php if ($doctor['avatar']): ?>
                            <img src="<?php echo h($doctor['avatar']); ?>" alt="<?php echo h($doctor['name']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="doctor-details">
                        <h3><?php echo h($doctor['name']); ?></h3>
                        <div class="doctor-title"><?php echo h($doctor['title']); ?></div>
                        <div class="doctor-category"><?php echo h($doctor['category_name']); ?></div>
                        
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
                
                <div class="hospital-info">
                    <h4>
                        <i class="fas fa-hospital"></i>
                        医院信息
                    </h4>
                    <div class="info-item">
                        <strong>医院名称：</strong>
                        <span><?php echo h($doctor['hospital_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>医院地址：</strong>
                        <span><?php echo h($doctor['hospital_address']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>联系电话：</strong>
                        <span><?php echo h($doctor['hospital_phone']); ?></span>
                    </div>
                </div>
                
                <?php if ($doctor['consultation_fee']): ?>
                    <div class="fee-info">
                        <h4>
                            <i class="fas fa-money-bill"></i>
                            挂号费用
                        </h4>
                        <div class="fee-amount">
                            ¥<?php echo number_format($doctor['consultation_fee'], 0); ?>
                        </div>
                        <div class="fee-note">* 费用仅供参考，实际以医院为准</div>
                    </div>
                <?php endif; ?>
                
                <div class="appointment-tips">
                    <h4>
                        <i class="fas fa-info-circle"></i>
                        预约须知
                    </h4>
                    <ul>
                        <li>请提前至少1小时到达医院</li>
                        <li>携带有效身份证件和相关病历</li>
                        <li>如需取消请提前24小时通知</li>
                        <li>预约成功后会收到确认短信</li>
                    </ul>
                </div>
            </aside>
            
            <!-- 预约表单 -->
            <main class="appointment-form-section">
                <div class="form-header">
                    <h2>
                        <i class="fas fa-calendar-check"></i>
                        预约挂号
                    </h2>
                    <p>请填写预约信息，我们会尽快为您安排</p>
                </div>
                
                <?php if ($appointmentError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo h($appointmentError); ?>
                    </div>
                <?php endif; ?>
                
                <form class="appointment-form" method="POST">
                    <!-- 预约时间选择 -->
                    <div class="form-section">
                        <h3>选择预约时间</h3>
                        
                        <div class="date-selection">
                            <label class="form-label">预约日期 <span class="required">*</span></label>
                            <div class="date-options">
                                <?php foreach ($availableDates as $dateInfo): ?>
                                    <label class="date-option">
                                        <input type="radio" name="appointment_date" value="<?php echo $dateInfo['date']; ?>" required>
                                        <div class="date-card">
                                            <div class="date-day"><?php echo $dateInfo['display']; ?></div>
                                            <div class="date-weekday">周<?php echo $dateInfo['weekday']; ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="time-selection">
                            <label class="form-label">预约时间 <span class="required">*</span></label>
                            
                            <div class="time-period">
                                <h4>上午</h4>
                                <div class="time-options">
                                    <?php foreach ($timeSlots['morning'] as $time): ?>
                                        <label class="time-option">
                                            <input type="radio" name="appointment_time" value="<?php echo $time; ?>" required>
                                            <div class="time-card"><?php echo $time; ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="time-period">
                                <h4>下午</h4>
                                <div class="time-options">
                                    <?php foreach ($timeSlots['afternoon'] as $time): ?>
                                        <label class="time-option">
                                            <input type="radio" name="appointment_time" value="<?php echo $time; ?>" required>
                                            <div class="time-card"><?php echo $time; ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 患者信息 -->
                    <div class="form-section">
                        <h3>患者信息</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="patient_name" class="form-label">
                                    患者姓名 <span class="required">*</span>
                                </label>
                                <input type="text" name="patient_name" id="patient_name" class="form-control" 
                                       placeholder="请输入患者真实姓名"
                                       value="<?php echo h($_POST['patient_name'] ?? $currentUser['real_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="patient_phone" class="form-label">
                                    联系电话 <span class="required">*</span>
                                </label>
                                <input type="tel" name="patient_phone" id="patient_phone" class="form-control" 
                                       placeholder="请输入手机号码"
                                       value="<?php echo h($_POST['patient_phone'] ?? $currentUser['phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="patient_age" class="form-label">
                                    患者年龄 <span class="required">*</span>
                                </label>
                                <input type="number" name="patient_age" id="patient_age" class="form-control" 
                                       placeholder="请输入年龄" min="1" max="120"
                                       value="<?php echo h($_POST['patient_age'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="patient_gender" class="form-label">
                                    患者性别 <span class="required">*</span>
                                </label>
                                <select name="patient_gender" id="patient_gender" class="form-control" required>
                                    <option value="">请选择性别</option>
                                    <option value="male" <?php echo ($_POST['patient_gender'] ?? '') == 'male' ? 'selected' : ''; ?>>男</option>
                                    <option value="female" <?php echo ($_POST['patient_gender'] ?? '') == 'female' ? 'selected' : ''; ?>>女</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 症状描述 -->
                    <div class="form-section">
                        <h3>症状描述</h3>
                        
                        <div class="form-group">
                            <label for="symptoms" class="form-label">
                                主要症状 <span class="required">*</span>
                            </label>
                            <textarea name="symptoms" id="symptoms" class="form-control" rows="5" 
                                      placeholder="请详细描述主要症状、病史、用药情况等（有助于医生提前了解病情）" 
                                      required><?php echo h($_POST['symptoms'] ?? ''); ?></textarea>
                            <div class="form-help">
                                详细的症状描述有助于医生更好地为您诊治
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_urgent" value="1" 
                                       <?php echo isset($_POST['is_urgent']) ? 'checked' : ''; ?>>
                                <span class="checkbox-text">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    紧急情况（病情较急，需要优先处理）
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="form-actions">
                        <button type="submit" name="submit_appointment" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-check"></i>
                            确认预约
                        </button>
                        <a href="/doctors/detail.php?id=<?php echo $doctorId; ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            返回医生详情
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 日期选择交互
    $('input[name="appointment_date"]').on('change', function() {
        const selectedDate = $(this).val();
        
        // 重置时间选择
        $('input[name="appointment_time"]').prop('checked', false);
        $('.time-card').removeClass('disabled');
        
        // 显示加载状态
        $('.time-options').addClass('loading');
        
        // AJAX请求检查该日期的可用时间段
        checkAvailableTimeSlots(selectedDate);
    });
    
    // 时间选择交互
    $('input[name="appointment_time"]').on('change', function() {
        const selectedTime = $(this).val();
        const selectedDate = $('input[name="appointment_date"]:checked').val();
        
        if (selectedDate && selectedTime) {
            // 检查该时间段是否可用
            checkTimeSlotAvailability(selectedDate, selectedTime);
        }
    });
    
    // 检查可用时间段
    function checkAvailableTimeSlots(date) {
        if (!date) return;
        
        const doctorId = <?php echo $doctorId; ?>;
        
        $.ajax({
            url: '/api/appointment-slots.php',
            method: 'GET',
            data: {
                doctor_id: doctorId,
                date: date
            },
            success: function(response) {
                $('.time-options').removeClass('loading');
                
                if (response.success && response.data) {
                    updateTimeSlots(response.data);
                } else {
                    showMessage(response.message || '获取时间段失败', 'error');
                    $('.time-card').addClass('disabled');
                }
            },
            error: function() {
                $('.time-options').removeClass('loading');
                showMessage('网络错误，请稍后重试', 'error');
                $('.time-card').addClass('disabled');
            }
        });
    }
    
    // 更新时间段显示
    function updateTimeSlots(sessions) {
        $('.time-card').addClass('disabled').removeClass('available');
        
        sessions.forEach(function(session) {
            session.slots.forEach(function(slot) {
                const timeInput = $('input[name="appointment_time"][value="' + slot.time + '"]');
                const timeCard = timeInput.parent().find('.time-card');
                
                if (slot.available) {
                    timeCard.removeClass('disabled').addClass('available');
                    timeInput.prop('disabled', false);
                } else {
                    timeCard.addClass('disabled').removeClass('available');
                    timeInput.prop('disabled', true);
                }
            });
        });
    }
    
    // 检查特定时间段可用性
    function checkTimeSlotAvailability(date, time) {
        // 模拟检查逻辑
        console.log('Checking availability for:', date, time);
    }
    
    // 表单验证
    $('.appointment-form').on('submit', function(e) {
        const appointmentDate = $('input[name="appointment_date"]:checked').val();
        const appointmentTime = $('input[name="appointment_time"]:checked').val();
        const patientName = $('#patient_name').val().trim();
        const patientPhone = $('#patient_phone').val().trim();
        const patientAge = $('#patient_age').val();
        const symptoms = $('#symptoms').val().trim();
        
        if (!appointmentDate) {
            e.preventDefault();
            showMessage('请选择预约日期', 'error');
            return;
        }
        
        if (!appointmentTime) {
            e.preventDefault();
            showMessage('请选择预约时间', 'error');
            return;
        }
        
        if (!patientName) {
            e.preventDefault();
            showMessage('请输入患者姓名', 'error');
            $('#patient_name').focus();
            return;
        }
        
        if (!patientPhone || !/^1[3-9]\d{9}$/.test(patientPhone)) {
            e.preventDefault();
            showMessage('请输入有效的手机号码', 'error');
            $('#patient_phone').focus();
            return;
        }
        
        if (!symptoms || symptoms.length < 10) {
            e.preventDefault();
            showMessage('请详细描述症状（至少10个字符）', 'error');
            $('#symptoms').focus();
            return;
        }
    });
    
    // 自动填充用户信息
    if (!$('#patient_name').val()) {
        $('#patient_name').val('<?php echo addslashes($currentUser['real_name'] ?? ''); ?>');
    }
    
    if (!$('#patient_phone').val()) {
        $('#patient_phone').val('<?php echo addslashes($currentUser['phone'] ?? ''); ?>');
    }
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