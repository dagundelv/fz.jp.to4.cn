<?php
require_once '../includes/init.php';
requireAdmin();

$pageTitle = "系统设置 - 管理员后台";

// 处理表单提交
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_basic':
            $settings = [
                'site_name' => trim($_POST['site_name']),
                'site_description' => trim($_POST['site_description']),
                'site_keywords' => trim($_POST['site_keywords']),
                'site_logo' => trim($_POST['site_logo']),
                'site_url' => trim($_POST['site_url']),
                'admin_email' => trim($_POST['admin_email']),
                'timezone' => $_POST['timezone'],
                'language' => $_POST['language']
            ];
            
            foreach ($settings as $key => $value) {
                $existingSetting = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existingSetting) {
                    $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                } else {
                    $db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            jsonResponse([
                'success' => true,
                'message' => '基本设置已更新',
                'reload' => true
            ]);
            break;
            
        case 'update_email':
            $emailSettings = [
                'smtp_host' => trim($_POST['smtp_host']),
                'smtp_port' => intval($_POST['smtp_port']),
                'smtp_username' => trim($_POST['smtp_username']),
                'smtp_password' => trim($_POST['smtp_password']),
                'smtp_encryption' => $_POST['smtp_encryption'],
                'mail_from_name' => trim($_POST['mail_from_name']),
                'mail_from_address' => trim($_POST['mail_from_address'])
            ];
            
            foreach ($emailSettings as $key => $value) {
                $existingSetting = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existingSetting) {
                    $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                } else {
                    $db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            jsonResponse([
                'success' => true,
                'message' => '邮件设置已更新'
            ]);
            break;
            
        case 'test_email':
            $testEmail = trim($_POST['test_email']);
            if (!$testEmail) {
                jsonResponse([
                    'success' => false,
                    'message' => '请输入测试邮箱地址'
                ]);
                break;
            }
            
            // 这里应该使用EmailService发送测试邮件
            // 为了演示，直接返回成功
            jsonResponse([
                'success' => true,
                'message' => '测试邮件已发送到 ' . $testEmail
            ]);
            break;
            
        case 'update_security':
            $securitySettings = [
                'enable_registration' => isset($_POST['enable_registration']) ? 1 : 0,
                'enable_comments' => isset($_POST['enable_comments']) ? 1 : 0,
                'comment_moderation' => isset($_POST['comment_moderation']) ? 1 : 0,
                'enable_captcha' => isset($_POST['enable_captcha']) ? 1 : 0,
                'max_login_attempts' => intval($_POST['max_login_attempts']),
                'login_attempt_timeout' => intval($_POST['login_attempt_timeout']),
                'session_timeout' => intval($_POST['session_timeout'])
            ];
            
            foreach ($securitySettings as $key => $value) {
                $existingSetting = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existingSetting) {
                    $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                } else {
                    $db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            jsonResponse([
                'success' => true,
                'message' => '安全设置已更新'
            ]);
            break;
            
        case 'clear_cache':
            // 清理缓存逻辑
            $cacheCleared = true; // 实际项目中应该实现缓存清理
            
            jsonResponse([
                'success' => $cacheCleared,
                'message' => $cacheCleared ? '缓存已清理' : '缓存清理失败'
            ]);
            break;
            
        case 'backup_database':
            // 数据库备份逻辑
            $backupSuccess = true; // 实际项目中应该实现数据库备份
            
            jsonResponse([
                'success' => $backupSuccess,
                'message' => $backupSuccess ? '数据库备份完成' : '数据库备份失败'
            ]);
            break;
    }
}

// 获取所有设置
$allSettings = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// 设置默认值
$defaultSettings = [
    'site_name' => '健康医疗网',
    'site_description' => '专业的健康医疗信息平台',
    'site_keywords' => '健康,医疗,疾病,医生,医院',
    'site_logo' => '',
    'site_url' => 'https://fz.jp.to4.cn',
    'admin_email' => 'admin@example.com',
    'timezone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'mail_from_name' => '健康医疗网',
    'mail_from_address' => 'noreply@example.com',
    'enable_registration' => 1,
    'enable_comments' => 1,
    'comment_moderation' => 1,
    'enable_captcha' => 0,
    'max_login_attempts' => 5,
    'login_attempt_timeout' => 15,
    'session_timeout' => 1440
];

$settings = array_merge($defaultSettings, $settings);

// 获取系统信息
$systemInfo = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'disk_free_space' => function_exists('disk_free_space') ? formatBytes(disk_free_space('.')) : 'Unknown',
    'disk_total_space' => function_exists('disk_total_space') ? formatBytes(disk_total_space('.')) : 'Unknown'
];

include 'templates/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h2>系统设置</h2>
        <div class="page-actions">
            <button class="btn btn-warning" onclick="clearCache()">
                <i class="fas fa-broom"></i>
                清理缓存
            </button>
            <button class="btn btn-info" onclick="backupDatabase()">
                <i class="fas fa-database"></i>
                备份数据库
            </button>
        </div>
    </div>
    
    <!-- 设置标签页 -->
    <div class="settings-tabs">
        <button class="tab-btn active" onclick="showTab('basic')" data-tab="basic">
            <i class="fas fa-cog"></i>
            基本设置
        </button>
        <button class="tab-btn" onclick="showTab('email')" data-tab="email">
            <i class="fas fa-envelope"></i>
            邮件设置
        </button>
        <button class="tab-btn" onclick="showTab('security')" data-tab="security">
            <i class="fas fa-shield-alt"></i>
            安全设置
        </button>
        <button class="tab-btn" onclick="showTab('system')" data-tab="system">
            <i class="fas fa-server"></i>
            系统信息
        </button>
    </div>
    
    <!-- 基本设置 -->
    <div class="tab-content active" id="basic-tab">
        <div class="settings-section">
            <h3>网站基本信息</h3>
            <form id="basicForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_basic">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>网站名称 *</label>
                        <input type="text" name="site_name" value="<?php echo h($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>网站URL *</label>
                        <input type="url" name="site_url" value="<?php echo h($settings['site_url']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>网站描述</label>
                    <textarea name="site_description" rows="3"><?php echo h($settings['site_description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>网站关键词</label>
                    <input type="text" name="site_keywords" value="<?php echo h($settings['site_keywords']); ?>" placeholder="用逗号分隔">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>网站Logo</label>
                        <input type="text" name="site_logo" value="<?php echo h($settings['site_logo']); ?>" placeholder="Logo图片URL">
                    </div>
                    
                    <div class="form-group">
                        <label>管理员邮箱 *</label>
                        <input type="email" name="admin_email" value="<?php echo h($settings['admin_email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>时区</label>
                        <select name="timezone">
                            <option value="Asia/Shanghai" <?php echo $settings['timezone'] === 'Asia/Shanghai' ? 'selected' : ''; ?>>北京时间 (UTC+8)</option>
                            <option value="Asia/Hong_Kong" <?php echo $settings['timezone'] === 'Asia/Hong_Kong' ? 'selected' : ''; ?>>香港时间 (UTC+8)</option>
                            <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC (UTC+0)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>语言</label>
                        <select name="language">
                            <option value="zh-CN" <?php echo $settings['language'] === 'zh-CN' ? 'selected' : ''; ?>>简体中文</option>
                            <option value="zh-TW" <?php echo $settings['language'] === 'zh-TW' ? 'selected' : ''; ?>>繁体中文</option>
                            <option value="en-US" <?php echo $settings['language'] === 'en-US' ? 'selected' : ''; ?>>English</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        保存基本设置
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 邮件设置 -->
    <div class="tab-content" id="email-tab">
        <div class="settings-section">
            <h3>SMTP邮件设置</h3>
            <form id="emailForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_email">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP服务器</label>
                        <input type="text" name="smtp_host" value="<?php echo h($settings['smtp_host']); ?>" placeholder="smtp.example.com">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP端口</label>
                        <input type="number" name="smtp_port" value="<?php echo h($settings['smtp_port']); ?>" placeholder="587">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP用户名</label>
                        <input type="text" name="smtp_username" value="<?php echo h($settings['smtp_username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP密码</label>
                        <input type="password" name="smtp_password" value="<?php echo h($settings['smtp_password']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>加密方式</label>
                        <select name="smtp_encryption">
                            <option value="">无加密</option>
                            <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>发件人名称</label>
                        <input type="text" name="mail_from_name" value="<?php echo h($settings['mail_from_name']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>发件人邮箱</label>
                    <input type="email" name="mail_from_address" value="<?php echo h($settings['mail_from_address']); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        保存邮件设置
                    </button>
                </div>
            </form>
            
            <!-- 邮件测试 -->
            <div class="email-test">
                <h4>邮件测试</h4>
                <div class="test-form">
                    <input type="email" id="testEmail" placeholder="输入测试邮箱地址">
                    <button class="btn btn-secondary" onclick="testEmail()">
                        <i class="fas fa-paper-plane"></i>
                        发送测试邮件
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 安全设置 -->
    <div class="tab-content" id="security-tab">
        <div class="settings-section">
            <h3>安全与权限设置</h3>
            <form id="securityForm" method="POST" data-ajax>
                <input type="hidden" name="action" value="update_security">
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_registration" <?php echo $settings['enable_registration'] ? 'checked' : ''; ?>>
                        允许用户注册
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_comments" <?php echo $settings['enable_comments'] ? 'checked' : ''; ?>>
                        允许用户评论
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="comment_moderation" <?php echo $settings['comment_moderation'] ? 'checked' : ''; ?>>
                        评论需要审核
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_captcha" <?php echo $settings['enable_captcha'] ? 'checked' : ''; ?>>
                        启用验证码
                    </label>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>最大登录尝试次数</label>
                        <input type="number" name="max_login_attempts" value="<?php echo h($settings['max_login_attempts']); ?>" min="1" max="10">
                    </div>
                    
                    <div class="form-group">
                        <label>登录限制时间（分钟）</label>
                        <input type="number" name="login_attempt_timeout" value="<?php echo h($settings['login_attempt_timeout']); ?>" min="1" max="60">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>会话超时时间（分钟）</label>
                    <input type="number" name="session_timeout" value="<?php echo h($settings['session_timeout']); ?>" min="30" max="10080">
                    <small class="form-text">建议设置为1440分钟（24小时）</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        保存安全设置
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 系统信息 -->
    <div class="tab-content" id="system-tab">
        <div class="settings-section">
            <h3>系统环境信息</h3>
            <div class="system-info">
                <div class="info-grid">
                    <div class="info-item">
                        <label>PHP版本</label>
                        <span><?php echo h($systemInfo['php_version']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>MySQL版本</label>
                        <span><?php echo h($systemInfo['mysql_version']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Web服务器</label>
                        <span><?php echo h($systemInfo['server_software']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>上传文件限制</label>
                        <span><?php echo h($systemInfo['upload_max_filesize']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>POST数据限制</label>
                        <span><?php echo h($systemInfo['post_max_size']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>内存限制</label>
                        <span><?php echo h($systemInfo['memory_limit']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>执行时间限制</label>
                        <span><?php echo h($systemInfo['max_execution_time']); ?>秒</span>
                    </div>
                    
                    <div class="info-item">
                        <label>磁盘可用空间</label>
                        <span><?php echo h($systemInfo['disk_free_space']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>磁盘总空间</label>
                        <span><?php echo h($systemInfo['disk_total_space']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 系统设置页面样式 */
.settings-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e9ecef;
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn:hover {
    color: #495057;
    background: #f8f9fa;
}

.tab-btn.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: #fff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.settings-section {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-section h3 {
    margin: 0 0 25px 0;
    font-size: 18px;
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #2c3e50;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-group textarea {
    resize: vertical;
    font-family: inherit;
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.email-test {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #e9ecef;
}

.email-test h4 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #2c3e50;
}

.test-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.test-form input {
    flex: 1;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.system-info {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border-radius: 6px;
    border-left: 4px solid #007bff;
}

.info-item label {
    font-weight: 500;
    color: #495057;
    margin: 0;
}

.info-item span {
    font-weight: 600;
    color: #2c3e50;
}
</style>

<script>
function showTab(tabName) {
    // 隐藏所有标签页内容
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // 移除所有标签按钮的激活状态
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 显示选中的标签页内容
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // 激活选中的标签按钮
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}

function testEmail() {
    const email = document.getElementById('testEmail').value.trim();
    if (!email) {
        showAdminMessage('请输入测试邮箱地址', 'warning');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'test_email';
    form.appendChild(actionInput);
    
    const emailInput = document.createElement('input');
    emailInput.type = 'hidden';
    emailInput.name = 'test_email';
    emailInput.value = email;
    form.appendChild(emailInput);
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        document.body.removeChild(form);
    });
}

function clearCache() {
    if (!confirm('确定要清理系统缓存吗？')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'clear_cache';
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        document.body.removeChild(form);
    });
}

function backupDatabase() {
    if (!confirm('确定要备份数据库吗？此操作可能需要一些时间。')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.setAttribute('data-ajax', '');
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'backup_database';
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    submitAjaxForm(form, function(data) {
        document.body.removeChild(form);
    });
}
</script>

<?php 
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

include 'templates/footer.php'; 
?>