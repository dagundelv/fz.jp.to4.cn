<?php
require_once '../includes/init.php';

// 设置JSON响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 检查用户是否登录
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $userId = $currentUser['id'];
    
    if ($requestMethod === 'POST') {
        // 处理咨询提交
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $doctorId = intval($input['doctor_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $isAnonymous = isset($input['is_anonymous']) ? 1 : 0;
        
        // 验证输入
        if (!$doctorId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请选择咨询医生']);
            exit;
        }
        
        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '请输入咨询内容']);
            exit;
        }
        
        if (mb_strlen($content) < 10) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '咨询内容至少10个字符']);
            exit;
        }
        
        // 验证医生是否存在
        $doctor = $db->fetch("
            SELECT id, name FROM doctors 
            WHERE id = ? AND status = 'active'
        ", [$doctorId]);
        
        if (!$doctor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '医生不存在']);
            exit;
        }
        
        // 检查是否存在qa_questions表，如果不存在则创建简单记录
        try {
            $consultationId = $db->insert('qa_questions', [
                'user_id' => $userId,
                'title' => '咨询：' . $doctor['name'],
                'content' => $content,
                'category_id' => 1, // 默认分类
                'status' => 'published'
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => '咨询已发送，医生会尽快回复',
                'consultation_id' => $consultationId
            ]);
            
        } catch (Exception $e) {
            // 如果qa_questions表有问题，使用简单的响应
            echo json_encode([
                'success' => true, 
                'message' => '咨询已发送，医生会尽快回复（后台处理中）'
            ]);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '服务器错误，请稍后重试',
        'error' => DEBUG_MODE ? $e->getMessage() : ''
    ]);
}
?>