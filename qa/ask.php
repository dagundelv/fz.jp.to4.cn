<?php
require_once '../includes/init.php';

// 检查用户是否登录
if (!isLoggedIn()) {
    header('Location: /user/login.php?redirect=' . urlencode('/qa/ask.php'));
    exit;
}

// 设置页面信息
$pageTitle = "提出问题 - 健康问答 - " . SITE_NAME;
$pageDescription = "向专业医生和健康专家提出您的健康问题，获得专业解答和建议";
$pageKeywords = "提问,健康咨询,在线问诊,医学问答";
$currentPage = 'qa';

// 获取分类数据
$categories = getCategories(0);

// 处理表单提交
$submitError = '';
$submitSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    // 验证输入
    if (empty($title)) {
        $submitError = '问题标题不能为空';
    } elseif (mb_strlen($title) < 5) {
        $submitError = '问题标题至少需要5个字符';
    } elseif (mb_strlen($title) > 200) {
        $submitError = '问题标题不能超过200个字符';
    } elseif (empty($content)) {
        $submitError = '问题描述不能为空';
    } elseif (mb_strlen($content) < 10) {
        $submitError = '问题描述至少需要10个字符';
    } elseif (!$categoryId) {
        $submitError = '请选择问题分类';
    } else {
        try {
            $questionId = $db->query("
                INSERT INTO qa_questions (user_id, category_id, title, content, is_anonymous, is_urgent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [$currentUser['id'], $categoryId, $title, $content, $isAnonymous, $isUrgent]);
            
            $submitSuccess = true;
            // 重定向到问题详情页
            header('Location: /qa/detail.php?id=' . $questionId);
            exit;
            
        } catch (Exception $e) {
            $submitError = '提交失败，请稍后重试';
        }
    }
}

// 获取预填充的疾病名称（如果有）
$prefilledDisease = $_GET['disease'] ?? '';

// 添加页面特定的CSS
$pageCSS = ['/assets/css/qa.css'];

include '../templates/header.php';
?>

<div class="qa-ask-page">
    <!-- 面包屑导航 -->
    <div class="breadcrumb-section">
        <div class="container">
            <?php
            $breadcrumbs = [
                ['title' => '健康问答', 'url' => '/qa/'],
                ['title' => '提出问题']
            ];
            echo generateBreadcrumb($breadcrumbs);
            ?>
        </div>
    </div>
    
    <div class="container">
        <div class="ask-layout">
            <!-- 主要内容区 -->
            <main class="ask-main-content">
                <div class="ask-header">
                    <h1>
                        <i class="fas fa-question-circle"></i>
                        提出您的健康问题
                    </h1>
                    <p>详细描述您的问题，专业医生和健康专家会为您提供解答</p>
                </div>
                
                <?php if ($submitError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo h($submitError); ?>
                    </div>
                <?php endif; ?>
                
                <div class="ask-form-card">
                    <form class="ask-form" method="POST">
                        <!-- 问题标题 -->
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i>
                                问题标题 <span class="required">*</span>
                            </label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   placeholder="请简洁明了地描述您的问题（5-200字符）"
                                   value="<?php echo h($_POST['title'] ?? ''); ?>" 
                                   maxlength="200" required>
                            <div class="form-help">
                                好的标题能帮助您更快获得准确的回答。例如："胸闷气短是什么原因？"
                            </div>
                            <div class="char-counter">
                                <span id="title-counter">0</span>/200
                            </div>
                        </div>
                        
                        <!-- 问题分类 -->
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-tag"></i>
                                问题分类 <span class="required">*</span>
                            </label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">请选择问题所属的科室分类</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo h($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-help">
                                选择正确的分类能让相关专业的医生更容易找到您的问题
                            </div>
                        </div>
                        
                        <!-- 问题描述 -->
                        <div class="form-group">
                            <label for="content" class="form-label">
                                <i class="fas fa-edit"></i>
                                详细描述 <span class="required">*</span>
                            </label>
                            <textarea name="content" id="content" class="form-control" rows="10" 
                                      placeholder="请详细描述您的问题，包括：&#10;1. 具体症状表现&#10;2. 症状持续时间&#10;3. 是否有诱发因素&#10;4. 之前的治疗情况&#10;5. 其他相关信息" 
                                      required><?php echo h($_POST['content'] ?? ($prefilledDisease ? "我想了解关于{$prefilledDisease}的相关问题：\n\n" : '')); ?></textarea>
                            <div class="form-help">
                                详细的描述能帮助医生更准确地理解您的情况，提供更有针对性的建议
                            </div>
                            <div class="char-counter">
                                <span id="content-counter">0</span> 字符（至少10个字符）
                            </div>
                        </div>
                        
                        <!-- 问题选项 -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-cog"></i>
                                问题选项
                            </label>
                            <div class="form-options">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_anonymous" value="1" 
                                           <?php echo isset($_POST['is_anonymous']) ? 'checked' : ''; ?>>
                                    <span class="checkbox-text">
                                        <i class="fas fa-user-secret"></i>
                                        匿名提问
                                    </span>
                                    <small>选择匿名提问将不会显示您的用户名</small>
                                </label>
                                
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_urgent" value="1"
                                           <?php echo isset($_POST['is_urgent']) ? 'checked' : ''; ?>>
                                    <span class="checkbox-text">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        紧急问题
                                    </span>
                                    <small>标记为紧急问题将优先获得回答</small>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 提交按钮 -->
                        <div class="form-actions">
                            <button type="submit" name="submit_question" class="btn btn-primary btn-large">
                                <i class="fas fa-paper-plane"></i>
                                提交问题
                            </button>
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-undo"></i>
                                重置表单
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            
            <!-- 侧边栏 -->
            <aside class="ask-sidebar">
                <!-- 提问指南 -->
                <div class="sidebar-widget ask-guide">
                    <h3 class="widget-title">
                        <i class="fas fa-lightbulb"></i>
                        提问指南
                    </h3>
                    <div class="guide-content">
                        <div class="guide-item">
                            <h4><i class="fas fa-check-circle"></i> 如何写好问题标题？</h4>
                            <ul>
                                <li>简洁明了，直接说明问题</li>
                                <li>避免"求助"、"急"等无效词汇</li>
                                <li>包含关键症状或疾病名称</li>
                            </ul>
                        </div>
                        
                        <div class="guide-item">
                            <h4><i class="fas fa-check-circle"></i> 如何详细描述问题？</h4>
                            <ul>
                                <li>症状的具体表现和程度</li>
                                <li>症状出现的时间和频率</li>
                                <li>可能的诱发因素</li>
                                <li>已经采取的治疗措施</li>
                                <li>年龄、性别等基本信息</li>
                            </ul>
                        </div>
                        
                        <div class="guide-item">
                            <h4><i class="fas fa-shield-alt"></i> 保护隐私安全</h4>
                            <ul>
                                <li>不要透露真实姓名和联系方式</li>
                                <li>可以选择匿名提问</li>
                                <li>避免上传包含个人信息的图片</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- 常见问题示例 -->
                <div class="sidebar-widget question-examples">
                    <h3 class="widget-title">
                        <i class="fas fa-list"></i>
                        问题示例
                    </h3>
                    <div class="examples-list">
                        <div class="example-item">
                            <h5>症状咨询类</h5>
                            <p>"最近一周经常头痛，特别是下午，是什么原因？"</p>
                        </div>
                        
                        <div class="example-item">
                            <h5>疾病了解类</h5>
                            <p>"高血压患者在饮食上需要注意什么？"</p>
                        </div>
                        
                        <div class="example-item">
                            <h5>治疗咨询类</h5>
                            <p>"胃炎吃药两周了，还是不舒服，该怎么办？"</p>
                        </div>
                        
                        <div class="example-item">
                            <h5>预防保健类</h5>
                            <p>"如何预防颈椎病？有哪些有效的锻炼方法？"</p>
                        </div>
                    </div>
                </div>
                
                <!-- 专家介绍 -->
                <div class="sidebar-widget expert-intro">
                    <h3 class="widget-title">
                        <i class="fas fa-user-md"></i>
                        专家团队
                    </h3>
                    <div class="expert-content">
                        <p>我们的专家团队包括：</p>
                        <ul>
                            <li><strong>主任医师</strong> - 三甲医院科室主任</li>
                            <li><strong>副主任医师</strong> - 临床经验丰富的专家</li>
                            <li><strong>主治医师</strong> - 各科室骨干医生</li>
                            <li><strong>健康顾问</strong> - 营养、康复等专业人士</li>
                        </ul>
                        <p>您的问题将会得到专业、及时的解答。</p>
                    </div>
                </div>
                
                <!-- 免责声明 -->
                <div class="sidebar-widget disclaimer">
                    <h3 class="widget-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        重要提醒
                    </h3>
                    <div class="disclaimer-content">
                        <ul>
                            <li>网上咨询仅供参考，不能替代面诊</li>
                            <li>如有紧急情况，请立即就医或拨打120</li>
                            <li>请勿在平台上售卖药品或医疗器械</li>
                            <li>保护个人隐私，谨慎分享敏感信息</li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 字符计数
    function updateCharCounter(input, counter, min = 0) {
        const length = input.val().length;
        counter.text(length);
        
        if (min > 0) {
            if (length < min) {
                input.addClass('error');
                counter.parent().addClass('error');
            } else {
                input.removeClass('error');
                counter.parent().removeClass('error');
            }
        }
    }
    
    // 标题字符计数
    $('#title').on('input', function() {
        updateCharCounter($(this), $('#title-counter'));
    });
    
    // 内容字符计数
    $('#content').on('input', function() {
        updateCharCounter($(this), $('#content-counter'), 10);
    });
    
    // 初始化字符计数
    updateCharCounter($('#title'), $('#title-counter'));
    updateCharCounter($('#content'), $('#content-counter'), 10);
    
    // 表单验证
    $('.ask-form').on('submit', function(e) {
        let hasError = false;
        
        // 验证标题
        const title = $('#title').val().trim();
        if (title.length < 5) {
            hasError = true;
            $('#title').addClass('error').focus();
            showMessage('问题标题至少需要5个字符', 'error');
        }
        
        // 验证内容
        const content = $('#content').val().trim();
        if (content.length < 10) {
            hasError = true;
            $('#content').addClass('error');
            if (!hasError) $('#content').focus();
            showMessage('问题描述至少需要10个字符', 'error');
        }
        
        // 验证分类
        const categoryId = $('#category_id').val();
        if (!categoryId) {
            hasError = true;
            $('#category_id').addClass('error');
            if (!hasError) $('#category_id').focus();
            showMessage('请选择问题分类', 'error');
        }
        
        if (hasError) {
            e.preventDefault();
        }
    });
    
    // 移除错误样式
    $('.form-control').on('focus', function() {
        $(this).removeClass('error');
    });
    
    // 重置表单
    $('button[type="reset"]').on('click', function() {
        setTimeout(() => {
            updateCharCounter($('#title'), $('#title-counter'));
            updateCharCounter($('#content'), $('#content-counter'), 10);
            $('.form-control').removeClass('error');
        }, 10);
    });
    
    // 智能分类建议
    $('#title, #content').on('input', function() {
        const text = ($('#title').val() + ' ' + $('#content').val()).toLowerCase();
        
        // 简单的关键词匹配建议分类
        const keywords = {
            1: ['感冒', '发烧', '咳嗽', '胸闷', '心脏', '高血压', '糖尿病'],
            2: ['手术', '伤口', '骨折', '外伤', '疼痛'],
            3: ['月经', '怀孕', '妇科', '白带', '乳腺'],
            4: ['小孩', '儿童', '婴儿', '发育', '疫苗'],
            5: ['关节', '腰痛', '颈椎', '骨头', '肌肉'],
            6: ['皮肤', '过敏', '湿疹', '痘痘', '瘙痒']
        };
        
        for (const [categoryId, words] of Object.entries(keywords)) {
            if (words.some(word => text.includes(word))) {
                if (!$('#category_id').val()) {
                    $('#category_id').val(categoryId);
                    break;
                }
            }
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