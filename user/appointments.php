<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/user/appointments.php'));
    exit;
}

// 设置页面信息
$pageTitle = "我的预约 - " . SITE_NAME;
$pageDescription = "查看和管理我的预约记录";
$pageKeywords = "预约记录,预约管理,我的预约";

// 获取筛选参数
$status = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = 10;

// 服务器端预加载预约数据作为fallback
$userId = $currentUser['id'];
$whereClause = "a.user_id = ?";
$params = [$userId];

if ($status && in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
    $whereClause .= " AND a.status = ?";
    $params[] = $status;
}

try {
    $serverAppointments = $db->fetchAll("
        SELECT a.*, 
               d.name as doctor_name, 
               d.title as doctor_title, 
               d.avatar as doctor_avatar,
               h.name as hospital_name, 
               h.address as hospital_address, 
               h.phone as hospital_phone,
               c.name as category_name
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE $whereClause
        ORDER BY a.created_at DESC
        LIMIT $limit OFFSET " . (($page - 1) * $limit) . "
    ", $params);
    
    $totalAppointments = $db->fetch("
        SELECT COUNT(*) as count 
        FROM appointments a
        WHERE $whereClause
    ", $params)['count'];
    
} catch (Exception $e) {
    $serverAppointments = [];
    $totalAppointments = 0;
}

// 添加页面特定的CSS
$pageCSS = ['/assets/css/appointment.css', '/assets/css/user.css', '/assets/css/appointments-page.css'];

include '../templates/header.php';
?>

<div class="user-appointments-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '个人中心', 'url' => '/user/profile.php'],
                ['title' => '我的预约']
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="user-layout">
            <!-- 侧边导航 -->
            <?php include '../templates/user-sidebar.php'; ?>
            
            <!-- 主要内容 -->
            <main class="user-main">
                <div class="page-header">
                    <h2>
                        <i class="fas fa-calendar-check"></i>
                        我的预约
                    </h2>
                    <div class="header-actions">
                        <a href="/doctors/" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            新增预约
                        </a>
                    </div>
                </div>
                
                <!-- 状态筛选 -->
                <div class="appointments-filter">
                    <div class="filter-tabs">
                        <a href="/user/appointments.php" class="filter-tab <?php echo $status === '' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            全部预约
                        </a>
                        <a href="/user/appointments.php?status=pending" class="filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            待确认
                        </a>
                        <a href="/user/appointments.php?status=confirmed" class="filter-tab <?php echo $status === 'confirmed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i>
                            已确认
                        </a>
                        <a href="/user/appointments.php?status=completed" class="filter-tab <?php echo $status === 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-double"></i>
                            已完成
                        </a>
                        <a href="/user/appointments.php?status=cancelled" class="filter-tab <?php echo $status === 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i>
                            已取消
                        </a>
                    </div>
                </div>
                
                <!-- 预约列表 -->
                <div class="appointments-list" id="appointmentsList">
                    <?php if (count($serverAppointments) > 0): ?>
                        <!-- 服务器端渲染的预约记录 -->
                        <?php foreach ($serverAppointments as $appointment): ?>
                            <div class="appointment-card" data-id="<?php echo $appointment['id']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-number">
                                        预约号：<?php echo h($appointment['appointment_number'] ?: $appointment['id']); ?>
                                    </div>
                                    <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                        <i class="fas fa-<?php 
                                            switch($appointment['status']) {
                                                case 'pending': echo 'clock'; break;
                                                case 'confirmed': echo 'check-circle'; break;
                                                case 'completed': echo 'check-double'; break;
                                                case 'cancelled': echo 'times-circle'; break;
                                                default: echo 'question-circle';
                                            }
                                        ?>"></i>
                                        <?php
                                            switch($appointment['status']) {
                                                case 'pending': echo '待确认'; break;
                                                case 'confirmed': echo '已确认'; break;
                                                case 'completed': echo '已完成'; break;
                                                case 'cancelled': echo '已取消'; break;
                                                default: echo $appointment['status'];
                                            }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="appointment-main">
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
                                            <h4><?php echo h($appointment['doctor_name'] ?: '未知医生'); ?></h4>
                                            <div class="doctor-title"><?php echo h($appointment['doctor_title'] ?: ''); ?></div>
                                            <div class="doctor-category"><?php echo h($appointment['category_name'] ?: ''); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="appointment-info">
                                        <div class="info-row">
                                            <div class="info-item">
                                                <i class="fas fa-calendar"></i>
                                                <span><?php echo date('Y年m月d日', strtotime($appointment['appointment_date'])); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-item">
                                                <i class="fas fa-hospital"></i>
                                                <span><?php echo h($appointment['hospital_name'] ?: '未知医院'); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-user"></i>
                                                <span><?php echo h($appointment['patient_name'] ?: ''); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="appointment-actions">
                                    <button class="btn btn-outline btn-sm" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                        查看详情
                                    </button>
                                    <?php 
                                    $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                                    $canCancel = $appointment['status'] === 'pending' && $appointmentDateTime > time() + 24 * 60 * 60;
                                    ?>
                                    <?php if ($canCancel): ?>
                                        <button class="btn btn-danger btn-sm" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            取消预约
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] === 'completed'): ?>
                                        <button class="btn btn-primary btn-sm" onclick="writeReview(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-star"></i>
                                            写评价
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- 无预约记录时的显示 -->
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>暂无预约记录</h3>
                            <p>您还没有任何预约记录</p>
                            <a href="/doctors/" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                立即预约
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 分页 -->
                <div class="pagination-wrapper" id="paginationWrapper">
                    <?php if ($totalAppointments > $limit): ?>
                        <div class="pagination">
                            <?php
                            $totalPages = ceil($totalAppointments / $limit);
                            $paginationParams = $_GET;
                            
                            // 上一页
                            if ($page > 1) {
                                $paginationParams['page'] = $page - 1;
                                echo '<a href="/user/appointments.php?' . http_build_query($paginationParams) . '" class="page-btn">上一页</a>';
                            }
                            
                            // 页码
                            for ($i = 1; $i <= $totalPages; $i++) {
                                if ($i === $page) {
                                    echo '<span class="page-btn active">' . $i . '</span>';
                                } else {
                                    $paginationParams['page'] = $i;
                                    echo '<a href="/user/appointments.php?' . http_build_query($paginationParams) . '" class="page-btn">' . $i . '</a>';
                                }
                            }
                            
                            // 下一页
                            if ($page < $totalPages) {
                                $paginationParams['page'] = $page + 1;
                                echo '<a href="/user/appointments.php?' . http_build_query($paginationParams) . '" class="page-btn">下一页</a>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- 预约详情模态框 -->
<div id="appointmentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>预约详情</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- 内容由JavaScript动态填充 -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">关闭</button>
        </div>
    </div>
</div>

<!-- 取消预约模态框 -->
<div id="cancelModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>取消预约</h3>
            <button class="modal-close" onclick="closeCancelModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>确定要取消这个预约吗？</p>
            <div class="form-group">
                <label for="cancelReason">取消原因：</label>
                <select id="cancelReason" class="form-control">
                    <option value="">请选择取消原因</option>
                    <option value="schedule_conflict">时间冲突</option>
                    <option value="condition_improved">病情好转</option>
                    <option value="wrong_doctor">选错医生</option>
                    <option value="emergency">紧急情况</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="form-group" id="otherReasonGroup" style="display: none;">
                <label for="otherReason">其他原因：</label>
                <textarea id="otherReason" class="form-control" rows="3" placeholder="请详细说明取消原因"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeCancelModal()">取消</button>
            <button class="btn btn-danger" onclick="confirmCancel()">确定取消</button>
        </div>
    </div>
</div>

<script>
let currentAppointmentId = null;
let currentPage = <?php echo $page; ?>;
let currentStatus = '<?php echo addslashes($status); ?>';

$(document).ready(function() {
    console.log('Appointments page loaded...');
    
    // 检查是否已有服务器端数据
    const hasServerData = $('.appointment-card').length > 0 || $('.empty-state').length > 0;
    
    if (hasServerData) {
        console.log('Server-side data found, skipping AJAX load');
    } else {
        console.log('No server data, loading via AJAX...');
        loadAppointments();
    }
    
    // 取消原因选择
    $('#cancelReason').on('change', function() {
        if ($(this).val() === 'other') {
            $('#otherReasonGroup').show();
        } else {
            $('#otherReasonGroup').hide();
        }
    });
});

// 加载预约列表
function loadAppointments(page = 1) {
    console.log('Loading appointments, page:', page, 'status:', currentStatus);
    $.ajax({
        url: '/api/appointments.php',
        method: 'GET',
        data: {
            page: page,
            limit: 10,
            status: currentStatus
        },
        success: function(response) {
            if (response.success) {
                renderAppointments(response.data);
                renderPagination(response);
            } else {
                showError('加载预约记录失败: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            if (xhr.status === 401) {
                window.location.href = '/user/login.php';
            } else {
                $('#appointmentsList').html(`
                    <div class="error-state">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>加载失败</h3>
                        <p>无法加载预约记录，请稍后重试</p>
                        <button class="btn btn-primary" onclick="loadAppointments()">
                            <i class="fas fa-refresh"></i>
                            重新加载
                        </button>
                    </div>
                `);
            }
        }
    });
}

// 渲染预约列表
function renderAppointments(appointments) {
    const container = $('#appointmentsList');
    
    if (appointments.length === 0) {
        container.html(`
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>暂无预约记录</h3>
                <p>您还没有任何预约记录</p>
                <a href="/doctors/" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    立即预约
                </a>
            </div>
        `);
        return;
    }
    
    let html = '';
    
    appointments.forEach(function(appointment) {
        const statusInfo = getStatusInfo(appointment.status);
        const appointmentDate = new Date(appointment.appointment_date + ' ' + appointment.appointment_time);
        const canCancel = appointment.status === 'pending' && appointmentDate > new Date(Date.now() + 24 * 60 * 60 * 1000);
        
        html += `
            <div class="appointment-card" data-id="${appointment.id}">
                <div class="appointment-header">
                    <div class="appointment-number">
                        预约号：${appointment.appointment_number || appointment.id}
                    </div>
                    <div class="appointment-status status-${appointment.status}">
                        <i class="${statusInfo.icon}"></i>
                        ${statusInfo.text}
                    </div>
                </div>
                
                <div class="appointment-main">
                    <div class="doctor-info">
                        <div class="doctor-avatar">
                            ${appointment.doctor_avatar ? 
                                `<img src="${appointment.doctor_avatar}" alt="${appointment.doctor_name}">` : 
                                `<div class="avatar-placeholder"><i class="fas fa-user-md"></i></div>`
                            }
                        </div>
                        <div class="doctor-details">
                            <h4>${appointment.doctor_name}</h4>
                            <div class="doctor-title">${appointment.doctor_title || ''}</div>
                            <div class="doctor-category">${appointment.category_name || ''}</div>
                        </div>
                    </div>
                    
                    <div class="appointment-info">
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span>${formatDate(appointment.appointment_date)}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>${formatTime(appointment.appointment_time)}</span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fas fa-hospital"></i>
                                <span>${appointment.hospital_name}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <span>${appointment.patient_name}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="appointment-actions">
                    <button class="btn btn-outline btn-sm" onclick="viewAppointment(${appointment.id})">
                        <i class="fas fa-eye"></i>
                        查看详情
                    </button>
                    ${canCancel ? `
                        <button class="btn btn-danger btn-sm" onclick="cancelAppointment(${appointment.id})">
                            <i class="fas fa-times"></i>
                            取消预约
                        </button>
                    ` : ''}
                    ${appointment.status === 'completed' ? `
                        <button class="btn btn-primary btn-sm" onclick="writeReview(${appointment.id})">
                            <i class="fas fa-star"></i>
                            写评价
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

// 渲染分页
function renderPagination(response) {
    const wrapper = $('#paginationWrapper');
    
    if (response.total_pages <= 1) {
        wrapper.hide();
        return;
    }
    
    let html = '<div class="pagination">';
    
    // 上一页
    if (response.page > 1) {
        html += `<button class="page-btn" onclick="loadAppointments(${response.page - 1})">上一页</button>`;
    }
    
    // 页码
    for (let i = 1; i <= response.total_pages; i++) {
        if (i === response.page) {
            html += `<button class="page-btn active">${i}</button>`;
        } else {
            html += `<button class="page-btn" onclick="loadAppointments(${i})">${i}</button>`;
        }
    }
    
    // 下一页
    if (response.page < response.total_pages) {
        html += `<button class="page-btn" onclick="loadAppointments(${response.page + 1})">下一页</button>`;
    }
    
    html += '</div>';
    wrapper.html(html).show();
}

// 获取状态信息
function getStatusInfo(status) {
    const statusMap = {
        'pending': { text: '待确认', icon: 'fas fa-clock' },
        'confirmed': { text: '已确认', icon: 'fas fa-check-circle' },
        'completed': { text: '已完成', icon: 'fas fa-check-double' },
        'cancelled': { text: '已取消', icon: 'fas fa-times-circle' }
    };
    
    return statusMap[status] || { text: status, icon: 'fas fa-question-circle' };
}

// 查看预约详情
function viewAppointment(appointmentId) {
    // 这里可以实现预约详情查看
    console.log('View appointment:', appointmentId);
}

// 取消预约
function cancelAppointment(appointmentId) {
    currentAppointmentId = appointmentId;
    $('#cancelModal').show();
}

// 确认取消
function confirmCancel() {
    const reason = $('#cancelReason').val();
    const otherReason = $('#otherReason').val();
    const finalReason = reason === 'other' ? otherReason : reason;
    
    if (!finalReason) {
        showError('请选择或填写取消原因');
        return;
    }
    
    $.ajax({
        url: '/api/appointments.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'cancel',
            appointment_id: currentAppointmentId,
            cancel_reason: finalReason
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess('预约已取消');
                closeCancelModal();
                loadAppointments(currentPage);
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('网络错误，请稍后重试');
        }
    });
}

// 关闭取消模态框
function closeCancelModal() {
    $('#cancelModal').hide();
    $('#cancelReason').val('');
    $('#otherReason').val('');
    $('#otherReasonGroup').hide();
    currentAppointmentId = null;
}

// 关闭模态框
function closeModal() {
    $('#appointmentModal').hide();
}

// 格式化日期
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
    });
}

// 格式化时间
function formatTime(timeStr) {
    return timeStr.substring(0, 5);
}

// 显示成功消息
function showSuccess(message) {
    showMessage(message, 'success');
}

// 显示错误消息
function showError(message) {
    showMessage(message, 'error');
}

// 显示消息
function showMessage(message, type = 'info') {
    const toast = $(`<div class="message-toast message-${type}">${message}</div>`);
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php include '../templates/footer.php'; ?>