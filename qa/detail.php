<?php
require_once '../includes/init.php';

// 获取问题ID
$questionId = intval($_GET['id'] ?? 0);
if (!$questionId) {
    header('Location: /qa/');
    exit;
}

// 获取问题详细信息
$question = $db->fetch("
    SELECT q.*, c.name as category_name, u.username, u.avatar as user_avatar
    FROM qa_questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    LEFT JOIN users u ON q.user_id = u.id
    WHERE q.id = ?
", [$questionId]);

if (!$question) {
    header('HTTP/1.0 404 Not Found');
    include '../templates/404.php';
    exit;
}

// 更新浏览次数
$db->query("UPDATE qa_questions SET view_count = view_count + 1 WHERE id = ?", [$questionId]);

// 设置页面信息
$pageTitle = $question['title'] . " - 健康问答 - " . SITE_NAME;
$pageDescription = strip_tags($question['content'] ?? $question['title']);
$pageKeywords = "健康问答," . $question['title'] . "," . $question['category_name'];
$currentPage = 'qa';

// 获取回答列表
$answers = $db->fetchAll("
    SELECT a.*, 
           d.name as doctor_name, d.title as doctor_title, d.avatar as doctor_avatar,
           d.hospital_id, h.name as hospital_name,
           u.username, u.avatar as user_avatar
    FROM qa_answers a 
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.question_id = ?
    ORDER BY a.is_best DESC, a.created_at ASC
", [$questionId]);

// 获取相关问题
$relatedQuestions = $db->fetchAll("
    SELECT q.*, c.name as category_name,
           (SELECT COUNT(*) FROM qa_answers a WHERE a.question_id = q.id) as answer_count
    FROM qa_questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE q.category_id = ? AND q.id != ?
    ORDER BY q.view_count DESC, q.created_at DESC
    LIMIT 5
", [$question['category_id'], $questionId]);

// 处理回答提交
$submitError = '';
$submitSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    if (!isLoggedIn()) {
        $submitError = '请先登录后再回答问题';
    } else {
        $content = trim($_POST['content'] ?? '');
        $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        
        if (empty($content)) {
            $submitError = '回答内容不能为空';
        } elseif (mb_strlen($content) < 10) {
            $submitError = '回答内容至少需要10个字符';
        } else {
            try {
                $db->query("
                    INSERT INTO qa_answers (question_id, user_id, content, is_anonymous, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ", [$questionId, $currentUser['id'], $content, $isAnonymous]);
                
                // 更新问题的回答数量
                $db->query("
                    UPDATE qa_questions 
                    SET answer_count = (SELECT COUNT(*) FROM qa_answers WHERE question_id = ?)
                    WHERE id = ?
                ", [$questionId, $questionId]);
                
                $submitSuccess = true;
                // 重新获取回答列表
                $answers = $db->fetchAll("
                    SELECT a.*, 
                           d.name as doctor_name, d.title as doctor_title, d.avatar as doctor_avatar,
                           d.hospital_id, h.name as hospital_name,
                           u.username, u.avatar as user_avatar
                    FROM qa_answers a 
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN hospitals h ON d.hospital_id = h.id
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE a.question_id = ?
                    ORDER BY a.is_best DESC, a.created_at ASC
                ", [$questionId]);
                
            } catch (Exception $e) {
                $submitError = '提交失败，请稍后重试';
            }
        }
    }
}

// 添加页面特定的CSS
$pageCSS = ['/assets/css/qa.css'];

include '../templates/header.php';
?>

<div class="qa-detail-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '健康问答', 'url' => '/qa/'],
                ['title' => $question['category_name'], 'url' => '/qa/?category=' . $question['category_id']],
                ['title' => truncate($question['title'], 50)]
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="qa-detail-layout">
            <!-- 主要内容区 -->
            <main class="qa-main-content">
                <!-- 问题详情卡片 -->
                <div class="question-detail-card">
                    <div class="question-header">
                        <h1 class="question-title"><?php echo h($question['title']); ?></h1>
                        
                        <div class="question-badges">
                            <?php if ($question['category_name']): ?>
                                <span class="category-badge">
                                    <i class="fas fa-tag"></i>
                                    <?php echo h($question['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (count($answers) > 0): ?>
                                <?php 
                                $hasExpertAnswer = false;
                                foreach ($answers as $answer) {
                                    if ($answer['doctor_name']) {
                                        $hasExpertAnswer = true;
                                        break;
                                    }
                                }
                                ?>
                                <?php if ($hasExpertAnswer): ?>
                                    <span class="status-badge status-expert">专家解答</span>
                                <?php else: ?>
                                    <span class="status-badge status-answered">已解答</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-badge status-pending">待解答</span>
                            <?php endif; ?>
                            
                            <?php if ($question['is_urgent']): ?>
                                <span class="urgent-badge">紧急</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-meta">
                            <div class="question-author">
                                <div class="author-avatar">
                                    <?php if ($question['user_avatar']): ?>
                                        <img src="<?php echo h($question['user_avatar']); ?>" 
                                             alt="<?php echo h($question['username']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="author-info">
                                    <span class="author-name">
                                        <?php echo h($question['username'] ?? '匿名用户'); ?>
                                    </span>
                                    <span class="question-time">
                                        发布于 <?php echo date('Y年m月d日 H:i', strtotime($question['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="question-stats">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($question['view_count']); ?>次浏览
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-comment"></i>
                                    <?php echo count($answers); ?>个回答
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($question['content']): ?>
                        <div class="question-content">
                            <div class="content-text">
                                <?php echo nl2br(h($question['content'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="question-actions">
                        <button class="btn btn-outline share-btn" 
                                data-title="<?php echo h($question['title']); ?>"
                                data-url="<?php echo getCurrentUrl(); ?>">
                            <i class="fas fa-share-alt"></i>
                            分享问题
                        </button>
                        
                        <button class="btn btn-outline favorite-btn" 
                                data-type="question" 
                                data-id="<?php echo $question['id']; ?>">
                            <i class="far fa-bookmark"></i>
                            收藏问题
                        </button>
                    </div>
                </div>
                
                <!-- 回答列表 -->
                <?php if ($answers): ?>
                    <div class="answers-section">
                        <h2 class="section-title">
                            <i class="fas fa-comments"></i>
                            <?php echo count($answers); ?>个回答
                        </h2>
                        
                        <div class="answers-list">
                            <?php foreach ($answers as $index => $answer): ?>
                                <div class="answer-card <?php echo $answer['is_best'] ? 'best-answer' : ''; ?>">
                                    <?php if ($answer['is_best']): ?>
                                        <div class="best-answer-badge">
                                            <i class="fas fa-check-circle"></i>
                                            最佳答案
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="answer-header">
                                        <div class="answerer-info">
                                            <div class="answerer-avatar">
                                                <?php if ($answer['doctor_avatar']): ?>
                                                    <img src="<?php echo h($answer['doctor_avatar']); ?>" 
                                                         alt="<?php echo h($answer['doctor_name']); ?>">
                                                <?php elseif ($answer['user_avatar']): ?>
                                                    <img src="<?php echo h($answer['user_avatar']); ?>" 
                                                         alt="<?php echo h($answer['username']); ?>">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="answerer-details">
                                                <h4 class="answerer-name">
                                                    <?php if ($answer['doctor_name']): ?>
                                                        <a href="/doctors/detail.php?id=<?php echo $answer['doctor_id']; ?>">
                                                            <?php echo h($answer['doctor_name']); ?>
                                                        </a>
                                                        <span class="doctor-badge">
                                                            <i class="fas fa-user-md"></i>
                                                            医生
                                                        </span>
                                                    <?php else: ?>
                                                        <?php echo h($answer['is_anonymous'] ? '匿名用户' : ($answer['username'] ?? '匿名用户')); ?>
                                                    <?php endif; ?>
                                                </h4>
                                                
                                                <?php if ($answer['doctor_title'] && $answer['hospital_name']): ?>
                                                    <div class="answerer-credentials">
                                                        <span class="title"><?php echo h($answer['doctor_title']); ?></span>
                                                        <span class="hospital">
                                                            <a href="/hospitals/detail.php?id=<?php echo $answer['hospital_id']; ?>">
                                                                <?php echo h($answer['hospital_name']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="answer-time">
                                            <?php echo timeAgo($answer['created_at']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="answer-content">
                                        <?php echo nl2br(h($answer['content'])); ?>
                                    </div>
                                    
                                    <div class="answer-actions">
                                        <button class="action-btn helpful-btn" 
                                                data-answer-id="<?php echo $answer['id']; ?>">
                                            <i class="fas fa-thumbs-up"></i>
                                            有用 (<?php echo rand(0, 20); ?>)
                                        </button>
                                        
                                        <button class="action-btn reply-btn" 
                                                data-answer-id="<?php echo $answer['id']; ?>">
                                            <i class="fas fa-reply"></i>
                                            回复
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 回答表单 -->
                <div class="answer-form-section" id="answer-form">
                    <h2 class="section-title">
                        <i class="fas fa-reply"></i>
                        我来回答
                    </h2>
                    
                    <?php if ($submitSuccess): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            回答提交成功！感谢您的解答。
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($submitError): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($submitError); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                        <form class="answer-form" method="POST">
                            <div class="form-group">
                                <label for="content">您的回答：</label>
                                <textarea name="content" id="content" rows="8" 
                                          placeholder="请详细回答问题，分享您的经验和知识..." 
                                          required></textarea>
                                <div class="form-help">
                                    请提供有用、准确的回答。如果您是医疗专业人士，请注明您的专业背景。
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_anonymous" value="1">
                                    <span class="checkbox-text">匿名回答</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="submit_answer" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    提交回答
                                </button>
                                <button type="reset" class="btn btn-outline">
                                    <i class="fas fa-undo"></i>
                                    重置
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <div class="login-message">
                                <i class="fas fa-sign-in-alt"></i>
                                <h3>请先登录</h3>
                                <p>登录后即可回答问题，帮助更多的人</p>
                            </div>
                            <div class="login-actions">
                                <a href="/user/login.php" class="btn btn-primary">立即登录</a>
                                <a href="/user/register.php" class="btn btn-outline">注册账号</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="qa-sidebar">
                <!-- 问题统计 -->
                <div class="sidebar-widget question-stats-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-chart-bar"></i>
                        问题统计
                    </h3>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-label">浏览次数</span>
                            <span class="stat-value"><?php echo number_format($question['view_count']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">回答数量</span>
                            <span class="stat-value"><?php echo count($answers); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">发布时间</span>
                            <span class="stat-value"><?php echo date('Y-m-d', strtotime($question['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- 相关问题 -->
                <?php if ($relatedQuestions): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-question-circle"></i>
                            相关问题
                        </h3>
                        <div class="related-questions">
                            <?php foreach ($relatedQuestions as $related): ?>
                                <div class="related-item">
                                    <h5>
                                        <a href="/qa/detail.php?id=<?php echo $related['id']; ?>">
                                            <?php echo h(truncate($related['title'], 60)); ?>
                                        </a>
                                    </h5>
                                    <div class="related-meta">
                                        <span class="answers">
                                            <i class="fas fa-comment"></i>
                                            <?php echo $related['answer_count']; ?>个回答
                                        </span>
                                        <span class="views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($related['view_count']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 快速操作 -->
                <div class="sidebar-widget quick-actions">
                    <h3 class="widget-title">
                        <i class="fas fa-bolt"></i>
                        快速操作
                    </h3>
                    <div class="action-buttons">
                        <a href="/qa/ask.php" class="action-btn">
                            <i class="fas fa-plus"></i>
                            <span>提出新问题</span>
                        </a>
                        
                        <a href="/doctors/?category=<?php echo $question['category_id']; ?>" 
                           class="action-btn">
                            <i class="fas fa-user-md"></i>
                            <span>找相关医生</span>
                        </a>
                        
                        <a href="/diseases/?category=<?php echo $question['category_id']; ?>" 
                           class="action-btn">
                            <i class="fas fa-book-medical"></i>
                            <span>查看疾病百科</span>
                        </a>
                    </div>
                </div>
                
                <!-- 健康提醒 -->
                <div class="sidebar-widget health-reminder">
                    <h3 class="widget-title">
                        <i class="fas fa-heartbeat"></i>
                        健康提醒
                    </h3>
                    <div class="reminder-content">
                        <div class="reminder-item">
                            <i class="fas fa-user-md"></i>
                            <p>网上回答仅供参考，如需治疗请及时就医</p>
                        </div>
                        <div class="reminder-item">
                            <i class="fas fa-shield-alt"></i>
                            <p>保护个人隐私，避免透露敏感信息</p>
                        </div>
                        <div class="reminder-item">
                            <i class="fas fa-phone"></i>
                            <p>紧急情况请拨打120急救电话</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 分享功能
    $('.share-btn').on('click', function() {
        const title = $(this).data('title');
        const url = $(this).data('url');
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            navigator.clipboard.writeText(url).then(function() {
                showMessage('链接已复制到剪贴板', 'success');
            }).catch(function() {
                showMessage('复制失败，请手动复制链接', 'error');
            });
        }
    });
    
    // 收藏功能
    $('.favorite-btn').on('click', function() {
        const $btn = $(this);
        const type = $btn.data('type');
        const id = $btn.data('id');
        
        if ($btn.hasClass('favorited')) {
            $btn.removeClass('favorited');
            $btn.find('i').removeClass('fas').addClass('far');
            $btn.html('<i class="far fa-bookmark"></i> 收藏问题');
            showMessage('已取消收藏', 'info');
        } else {
            $btn.addClass('favorited');
            $btn.find('i').removeClass('far').addClass('fas');
            $btn.html('<i class="fas fa-bookmark"></i> 已收藏');
            showMessage('收藏成功', 'success');
        }
    });
    
    // 有用按钮
    $('.helpful-btn').on('click', function() {
        const $btn = $(this);
        const answerId = $btn.data('answer-id');
        
        if (!$btn.hasClass('voted')) {
            $btn.addClass('voted');
            const currentCount = parseInt($btn.text().match(/\d+/)[0]);
            $btn.html('<i class="fas fa-thumbs-up"></i> 有用 (' + (currentCount + 1) + ')');
            showMessage('感谢您的反馈', 'success');
        }
    });
    
    // 回复按钮
    $('.reply-btn').on('click', function() {
        const answerId = $(this).data('answer-id');
        // 这里可以添加回复功能
        showMessage('回复功能开发中', 'info');
    });
    
    // 表单验证
    $('.answer-form').on('submit', function(e) {
        const content = $('#content').val().trim();
        if (content.length < 10) {
            e.preventDefault();
            showMessage('回答内容至少需要10个字符', 'error');
            $('#content').focus();
        }
    });
    
    // 字符计数
    $('#content').on('input', function() {
        const length = $(this).val().length;
        if (length < 10) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
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

// 获取当前页面URL
function getCurrentUrl() {
    return window.location.href;
}
</script>

<?php include '../templates/footer.php'; ?>