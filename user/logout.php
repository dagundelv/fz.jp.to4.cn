<?php
require_once '../includes/init.php';

// 清除会话数据
if (isset($_SESSION['user_id'])) {
    // 清除记住我令牌
    if (isset($_COOKIE['remember_token'])) {
        $db->update('users', ['remember_token' => null], 'id = ?', [$_SESSION['user_id']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // 销毁会话
    session_destroy();
}

// 重定向到首页
header('Location: /?logout=1');
exit;
?>