<?php
require_once '../includes/init.php';

// 如果已经登录，重定向到首页
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

// 设置页面信息
$pageTitle = "用户注册 - " . SITE_NAME;
$pageDescription = "注册账户，享受个性化的健康服务";
$pageKeywords = "用户注册,账户注册,健康服务";

// 处理注册表单提交
$registerError = '';
$registerSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $agreeTerms = isset($_POST['agree_terms']);
    
    // 验证输入
    if (empty($username)) {
        $registerError = '请输入用户名';
    } elseif (mb_strlen($username) < 3) {
        $registerError = '用户名至少需要3个字符';
    } elseif (mb_strlen($username) > 20) {
        $registerError = '用户名不能超过20个字符';
    } elseif (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
        $registerError = '用户名只能包含字母、数字、下划线和中文';
    } elseif (empty($email)) {
        $registerError = '请输入邮箱地址';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = '请输入有效的邮箱地址';
    } elseif (empty($password)) {
        $registerError = '请输入密码';
    } elseif (mb_strlen($password) < 6) {
        $registerError = '密码至少需要6个字符';
    } elseif ($password !== $confirmPassword) {
        $registerError = '两次输入的密码不一致';
    } elseif (!$agreeTerms) {
        $registerError = '请阅读并同意服务条款';
    } else {
        // 检查用户名和邮箱是否已存在
        $existingUser = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        
        if ($existingUser) {
            $registerError = '用户名或邮箱已存在';
        } else {
            try {
                // 创建新用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userId = $db->query("
                    INSERT INTO users (username, email, password, created_at) 
                    VALUES (?, ?, ?, NOW())
                ", [$username, $email, $hashedPassword]);
                
                $registerSuccess = true;
                
                // 自动登录
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                
                // 重定向到首页
                header('Location: /?welcome=1');
                exit;
                
            } catch (Exception $e) {
                $registerError = '注册失败，请稍后重试';
            }
        }
    }
}

include '../templates/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>
                        <i class="fas fa-user-plus"></i>
                        用户注册
                    </h1>
                    <p>创建您的账户，开启健康生活之旅</p>
                </div>
                
                <?php if ($registerError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo h($registerError); ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            用户名 <span class="required">*</span>
                        </label>
                        <input type="text" name="username" id="username" class="form-control" 
                               placeholder="请输入用户名（3-20个字符）"
                               value="<?php echo h($_POST['username'] ?? ''); ?>" required>
                        <div class="form-help">
                            用户名将作为您的唯一标识，支持中文、英文、数字和下划线
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            邮箱地址 <span class="required">*</span>
                        </label>
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="请输入有效的邮箱地址"
                               value="<?php echo h($_POST['email'] ?? ''); ?>" required>
                        <div class="form-help">
                            邮箱将用于账户验证和重要通知，请确保能够正常接收邮件
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            密码 <span class="required">*</span>
                        </label>
                        <div class="password-input">
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="请输入密码（至少6个字符）" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'password-eye')">
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            确认密码 <span class="required">*</span>
                        </label>
                        <div class="password-input">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                   placeholder="请再次输入密码" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'confirm-eye')">
                                <i class="fas fa-eye" id="confirm-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="password-match"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree_terms" value="1" required>
                            <span class="checkbox-text">
                                我已阅读并同意 
                                <a href="/terms.php" target="_blank">《服务条款》</a> 
                                和 
                                <a href="/privacy.php" target="_blank">《隐私政策》</a>
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary btn-block btn-large">
                        <i class="fas fa-user-plus"></i>
                        立即注册
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>已有账户？ <a href="/user/login.php">立即登录</a></p>
                </div>
            </div>
            
            <div class="auth-benefits">
                <h2>加入我们的健康社区</h2>
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>专业医生解答</h3>
                            <p>三甲医院主任医师在线解答您的健康问题</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>健康社区交流</h3>
                            <p>与千万用户分享健康经验，互帮互助</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>权威医院资源</h3>
                            <p>全国知名医院信息，便捷预约挂号服务</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>隐私安全保障</h3>
                            <p>严格保护用户隐私，安全可靠的健康数据</p>
                        </div>
                    </div>
                </div>
                
                <div class="community-stats">
                    <h3>社区数据</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number">10万+</span>
                            <span class="stat-label">注册用户</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">5000+</span>
                            <span class="stat-label">专业医生</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">100万+</span>
                            <span class="stat-label">健康问答</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">99%</span>
                            <span class="stat-label">用户满意度</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 继承登录页面的基础样式 */
.required {
    color: #ef4444;
}

.form-help {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

.password-strength {
    margin-top: 8px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    transition: all 0.3s;
}

.password-strength::after {
    content: '';
    height: 100%;
    display: block;
    border-radius: 2px;
    transition: all 0.3s;
}

.password-strength.weak::after {
    width: 33.33%;
    background: #ef4444;
}

.password-strength.medium::after {
    width: 66.66%;
    background: #f59e0b;
}

.password-strength.strong::after {
    width: 100%;
    background: #10b981;
}

.password-match {
    margin-top: 6px;
    font-size: 12px;
    height: 16px;
}

.password-match.match {
    color: #10b981;
}

.password-match.no-match {
    color: #ef4444;
}

.community-stats {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.community-stats h3 {
    margin: 0 0 20px 0;
    color: white;
    font-size: 20px;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.checkbox-text a {
    color: #4f46e5;
    text-decoration: none;
}

.checkbox-text a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-number {
        font-size: 20px;
    }
}
</style>

<script>
function togglePassword(inputId, eyeId) {
    const passwordInput = document.getElementById(inputId);
    const passwordEye = document.getElementById(eyeId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordEye.classList.remove('fa-eye');
        passwordEye.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordEye.classList.remove('fa-eye-slash');
        passwordEye.classList.add('fa-eye');
    }
}

// 密码强度检测
function checkPasswordStrength(password) {
    let strength = 0;
    
    // 长度检查
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // 复杂度检查
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return Math.min(strength, 3);
}

// 密码匹配检查
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchElement = document.getElementById('password-match');
    
    if (confirmPassword === '') {
        matchElement.textContent = '';
        matchElement.className = 'password-match';
        return;
    }
    
    if (password === confirmPassword) {
        matchElement.textContent = '✓ 密码匹配';
        matchElement.className = 'password-match match';
    } else {
        matchElement.textContent = '✗ 密码不匹配';
        matchElement.className = 'password-match no-match';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthElement = document.getElementById('password-strength');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        
        if (password === '') {
            strengthElement.className = 'password-strength';
            return;
        }
        
        switch (strength) {
            case 0:
            case 1:
                strengthElement.className = 'password-strength weak';
                break;
            case 2:
                strengthElement.className = 'password-strength medium';
                break;
            case 3:
            default:
                strengthElement.className = 'password-strength strong';
                break;
        }
        
        checkPasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    
    // 表单验证
    document.querySelector('.auth-form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const agreeTerms = document.querySelector('input[name="agree_terms"]').checked;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('两次输入的密码不一致');
            return;
        }
        
        if (!agreeTerms) {
            e.preventDefault();
            alert('请阅读并同意服务条款');
            return;
        }
    });
});
</script>

<?php include '../templates/footer.php'; ?>