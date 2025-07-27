<?php
/**
 * 缓存清理工具
 * 仅管理员可用
 */
require_once '../includes/init.php';

// 检查是否为管理员访问
if (!isset($_SESSION['admin_user']) || empty($_SESSION['admin_user'])) {
    http_response_code(403);
    exit('Access Denied');
}

$result = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'clear_all':
                cache_flush();
                $result = ['success' => true, 'message' => '所有缓存已清除'];
                break;
                
            case 'clear_specific':
                $keys = $_POST['keys'] ?? [];
                $cleared = 0;
                foreach ($keys as $key) {
                    if (cache_delete($key)) {
                        $cleared++;
                    }
                }
                $result = ['success' => true, 'message' => "已清除 {$cleared} 个缓存项"];
                break;
                
            default:
                $result = ['success' => false, 'message' => '无效的操作'];
        }
    }
    
    // 获取缓存统计
    $cacheStats = CacheManager::getInstance()->getStats();
    
} catch (Exception $e) {
    $result = ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
}

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>缓存管理 - 后台管理</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-number { display: block; font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .actions { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .cache-keys { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>缓存管理</h1>
        
        <div id="message"></div>
        
        <!-- 缓存统计 -->
        <h3>缓存统计</h3>
        <div class="stats-grid">
            <?php if (is_array($cacheStats)): ?>
                <?php if (isset($cacheStats['type'])): ?>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo ucfirst($cacheStats['type']); ?></span>
                        <div class="stat-label">缓存类型</div>
                    </div>
                    <?php if ($cacheStats['type'] === 'file'): ?>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $cacheStats['total_files'] ?? 0; ?></span>
                            <div class="stat-label">总文件数</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $cacheStats['valid_files'] ?? 0; ?></span>
                            <div class="stat-label">有效文件</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo round(($cacheStats['total_size'] ?? 0) / 1024 / 1024, 2); ?>MB</span>
                            <div class="stat-label">总大小</div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="stat-card">
                        <span class="stat-number">Redis</span>
                        <div class="stat-label">缓存类型</div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- 操作按钮 -->
        <h3>缓存操作</h3>
        <div class="actions">
            <button class="btn btn-danger" onclick="clearAllCache()">清除所有缓存</button>
            <button class="btn btn-primary" onclick="location.reload()">刷新页面</button>
        </div>
        
        <!-- 常见缓存键 -->
        <h3>常见缓存键</h3>
        <div class="cache-keys">
            <p><strong>数据缓存：</strong></p>
            <ul>
                <li>hot_questions_* - 热门问题</li>
                <li>category_tree - 分类树</li>
                <li>popular_searches_* - 热门搜索</li>
                <li>site_stats - 网站统计</li>
                <li>categories_* - 分类列表</li>
                <li>related_doctors_* - 相关医生</li>
                <li>hospital_doctors_* - 医院医生</li>
            </ul>
            <p><strong>用户缓存：</strong></p>
            <ul>
                <li>user_appointments_* - 用户预约</li>
            </ul>
        </div>
    </div>

    <script>
        function clearAllCache() {
            if (!confirm('确定要清除所有缓存吗？此操作不可撤销。')) {
                return;
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=clear_all'
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                showMessage('操作失败：' + error.message, 'error');
            });
        }
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>