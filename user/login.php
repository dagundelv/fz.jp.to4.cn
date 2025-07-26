<?php
require_once '../includes/init.php';

// 如果已经登录，重定向到首页
if (isLoggedIn()) {
    $redirectUrl = $_GET['redirect'] ?? '/';
    header('Location: ' . $redirectUrl);
    exit;
}

// 设置页面信息
$pageTitle = "用户登录 - " . SITE_NAME;
$pageDescription = "登录您的账户，享受个性化的健康服务";
$pageKeywords = "用户登录,账户登录,健康服务";

// 处理登录表单提交
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username)) {
        $loginError = '请输入用户名或邮箱';
    } elseif (empty($password)) {
        $loginError = '请输入密码';
    } else {
        // 查找用户
        $user = $db->fetch("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ", [$username, $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // 登录成功
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // 更新最后登录时间
            $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            
            // 处理记住我功能
            if ($rememberMe) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/'); // 30天
                $db->update('users', ['remember_token' => $token], 'id = ?', [$user['id']]);
            }
            
            // 重定向
            $redirectUrl = $_GET['redirect'] ?? '/';
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $loginError = '用户名或密码错误';
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
                        <i class="fas fa-sign-in-alt"></i>
                        用户登录
                    </h1>
                    <p>登录您的账户，享受个性化健康服务</p>
                </div>
                
                <?php if ($loginError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo h($loginError); ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            用户名或邮箱
                        </label>
                        <input type="text" name="username" id="username" class="form-control" 
                               placeholder="请输入用户名或邮箱地址"
                               value="<?php echo h($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            密码
                        </label>
                        <div class="password-input">
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="请输入密码" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" value="1">
                            <span class="checkbox-text">记住我</span>
                        </label>
                        
                        <a href="/user/forgot-password.php" class="forgot-link">忘记密码？</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-block btn-large">
                        <i class="fas fa-sign-in-alt"></i>
                        立即登录
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>还没有账户？ <a href="/user/register.php">立即注册</a></p>
                </div>
                
                <!-- 演示账户 -->
                <div class="demo-accounts">
                    <h3>演示账户</h3>
                    <div class="demo-list">
                        <div class="demo-item">
                            <strong>普通用户：</strong>
                            <span>user@demo.com / demo123</span>
                        </div>
                        <div class="demo-item">
                            <strong>医生账户：</strong>
                            <span>doctor@demo.com / demo123</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="auth-benefits">
                <h2>登录后您可以</h2>
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>提问与回答</h3>
                            <p>向专业医生提问，分享您的健康经验</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>收藏与关注</h3>
                            <p>收藏喜欢的医生、医院和健康资讯</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>预约挂号</h3>
                            <p>在线预约挂号，享受便捷医疗服务</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="benefit-content">
                            <h3>在线咨询</h3>
                            <p>与专业医生在线交流，获得健康建议</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 用户认证页面样式 */
.auth-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 40px 0;
}

.auth-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

.auth-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 24px;
}

.auth-header h1 i {
    color: #4f46e5;
}

.auth-header p {
    color: #6b7280;
    margin: 0;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #1f2937;
    font-weight: 600;
    font-size: 14px;
}

.form-label i {
    color: #4f46e5;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.password-input {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}

.password-toggle:hover {
    color: #4f46e5;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-text {
    color: #6b7280;
    font-size: 14px;
}

.forgot-link {
    color: #4f46e5;
    text-decoration: none;
    font-size: 14px;
}

.forgot-link:hover {
    text-decoration: underline;
}

.btn-block {
    width: 100%;
    display: block;
    text-align: center;
}

.btn-large {
    padding: 15px 30px;
    font-size: 16px;
}

.auth-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.auth-footer p {
    color: #6b7280;
    margin: 0;
}

.auth-footer a {
    color: #4f46e5;
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.demo-accounts {
    margin-top: 30px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.demo-accounts h3 {
    margin: 0 0 15px 0;
    color: #1f2937;
    font-size: 16px;
    text-align: center;
}

.demo-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.demo-item {
    font-size: 13px;
    color: #6b7280;
}

.demo-item strong {
    color: #4f46e5;
}

.auth-benefits {
    color: white;
}

.auth-benefits h2 {
    margin: 0 0 30px 0;
    font-size: 28px;
    font-weight: 700;
}

.benefits-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.benefit-item {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.benefit-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.benefit-icon i {
    font-size: 20px;
    color: white;
}

.benefit-content h3 {
    margin: 0 0 8px 0;
    color: white;
    font-size: 18px;
}

.benefit-content p {
    margin: 0;
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    line-height: 1.5;
}

.error-message {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.error-message i {
    color: #ef4444;
}

/* 响应式设计 */
@media (max-width: 1024px) {
    .auth-wrapper {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .auth-benefits {
        order: -1;
        text-align: center;
    }
    
    .benefits-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .auth-card {
        padding: 30px;
    }
    
    .benefits-list {
        grid-template-columns: 1fr;
    }
    
    .benefit-item {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .auth-card {
        padding: 20px;
    }
    
    .form-options {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}
</style>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordEye = document.getElementById('password-eye');
    
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

// 演示账户快速填充
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.demo-item').forEach(item => {
        item.addEventListener('click', function() {
            const text = this.textContent;
            const match = text.match(/(\S+@\S+\.\S+)\s*\/\s*(\S+)/);
            if (match) {
                document.getElementById('username').value = match[1];
                document.getElementById('password').value = match[2];
            }
        });
    });
});
</script>

<?php include '../templates/footer.php'; ?>