<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

try {
    switch ($method) {
        case 'GET':
            // 获取单个医生详情
            if (isset($uri[4]) && is_numeric($uri[4])) {
                $doctorId = intval($uri[4]);
                $doctor = $db->fetch("
                    SELECT d.*, h.name as hospital_name, h.address as hospital_address,
                           c.name as category_name
                    FROM doctors d 
                    LEFT JOIN hospitals h ON d.hospital_id = h.id
                    LEFT JOIN categories c ON d.category_id = c.id
                    WHERE d.id = ? AND d.status = 'active'
                ", [$doctorId]);
                
                if (!$doctor) {
                    throw new Exception('医生信息不存在', 404);
                }
                
                // 更新浏览量
                $db->update('doctors', ['view_count' => $doctor['view_count'] + 1], 'id = ?', [$doctorId]);
                
                // 获取医生的问答回复
                $answers = $db->fetchAll("
                    SELECT a.id, a.content, a.created_at, q.title as question_title, q.id as question_id
                    FROM qa_answers a
                    LEFT JOIN qa_questions q ON a.question_id = q.id
                    WHERE a.doctor_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ", [$doctorId]);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'doctor' => $doctor,
                        'recent_answers' => $answers
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } else {
                // 获取医生列表
                $page = max(1, intval($_GET['page'] ?? 1));
                $pageSize = min(50, intval($_GET['limit'] ?? 20));
                $category = $_GET['category'] ?? '';
                $city = $_GET['city'] ?? '';
                $hospital = $_GET['hospital'] ?? '';
                $keyword = trim($_GET['keyword'] ?? '');
                
                $whereConditions = ["d.status = 'active'", "h.status = 'active'"];
                $queryParams = [];
                
                if ($category) {
                    $whereConditions[] = "d.category_id = ?";
                    $queryParams[] = $category;
                }
                
                if ($city) {
                    $whereConditions[] = "h.city = ?";
                    $queryParams[] = $city;
                }
                
                if ($hospital) {
                    $whereConditions[] = "d.hospital_id = ?";
                    $queryParams[] = $hospital;
                }
                
                if ($keyword) {
                    $whereConditions[] = "(d.name LIKE ? OR d.title LIKE ? OR d.specialties LIKE ?)";
                    $searchTerm = "%{$keyword}%";
                    $queryParams[] = $searchTerm;
                    $queryParams[] = $searchTerm;
                    $queryParams[] = $searchTerm;
                }
                
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
                
                // 获取总数
                $total = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM doctors d 
                    LEFT JOIN hospitals h ON d.hospital_id = h.id 
                    {$whereClause}
                ", $queryParams)['count'];
                
                // 获取医生列表
                $offset = ($page - 1) * $pageSize;
                $listParams = array_merge($queryParams, [$pageSize, $offset]);
                
                $doctors = $db->fetchAll("
                    SELECT d.id, d.name, d.title, d.avatar, d.specialties, d.introduction,
                           d.experience, d.rating, COALESCE(d.consultation_fee, 0) as consultation_fee, d.view_count,
                           h.name as hospital_name, h.level as hospital_level,
                           c.name as category_name
                    FROM doctors d 
                    LEFT JOIN hospitals h ON d.hospital_id = h.id
                    LEFT JOIN categories c ON d.category_id = c.id
                    {$whereClause}
                    ORDER BY d.rating DESC, d.view_count DESC
                    LIMIT ? OFFSET ?
                ", $listParams);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'doctors' => $doctors,
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $pageSize,
                            'total' => $total,
                            'total_pages' => ceil($total / $pageSize)
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            throw new Exception('不支持的请求方法', 405);
    }
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $e->getCode() ?: 500,
            'message' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>