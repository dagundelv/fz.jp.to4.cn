<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

try {
    switch ($method) {
        case 'POST':
            $action = $uri[4] ?? '';
            
            switch ($action) {
                case 'login':
                    $email = $input['email'] ?? '';
                    $password = $input['password'] ?? '';
                    
                    if (!$email || !$password) {
                        throw new Exception('邮箱和密码不能为空', 400);
                    }
                    
                    // 验证用户
                    $user = $db->fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
                    
                    if (!$user || !verifyPassword($password, $user['password'])) {
                        throw new Exception('邮箱或密码错误', 401);
                    }
                    
                    // 生成API Token
                    $token = generateApiToken($user['id']);
                    
                    // 更新最后登录时间
                    $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'email' => $user['email'],
                                'real_name' => $user['real_name'],
                                'avatar' => $user['avatar'],
                                'role' => $user['role']
                            ]
                        ],
                        'message' => '登录成功'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'register':
                    $username = trim($input['username'] ?? '');
                    $email = trim($input['email'] ?? '');
                    $password = $input['password'] ?? '';
                    $real_name = trim($input['real_name'] ?? '');
                    
                    // 验证输入
                    if (!$username || !$email || !$password) {
                        throw new Exception('用户名、邮箱和密码不能为空', 400);
                    }
                    
                    if (!isValidEmail($email)) {
                        throw new Exception('邮箱格式不正确', 400);
                    }
                    
                    if (strlen($password) < 6) {
                        throw new Exception('密码长度不能少于6位', 400);
                    }
                    
                    // 检查重复
                    if ($db->exists('users', 'username = ?', [$username])) {
                        throw new Exception('用户名已存在', 400);
                    }
                    
                    if ($db->exists('users', 'email = ?', [$email])) {
                        throw new Exception('邮箱已被注册', 400);
                    }
                    
                    // 创建用户
                    $userId = $db->insert('users', [
                        'username' => $username,
                        'email' => $email,
                        'password' => hashPassword($password),
                        'real_name' => $real_name,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // 生成API Token
                    $token = generateApiToken($userId);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'token' => $token,
                            'user' => [
                                'id' => $userId,
                                'username' => $username,
                                'email' => $email,
                                'real_name' => $real_name,
                                'role' => 'user'
                            ]
                        ],
                        'message' => '注册成功'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'logout':
                    $token = getBearerToken();
                    if ($token) {
                        // 将token加入黑名单（这里简化处理）
                        // 实际应用中应该将token存储在Redis或数据库中
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => '退出成功'
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    throw new Exception('未知的操作类型', 400);
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

// 生成API Token
function generateApiToken($userId) {
    $payload = [
        'user_id' => $userId,
        'issued_at' => time(),
        'expires_at' => time() + (7 * 24 * 60 * 60) // 7天过期
    ];
    return base64_encode(json_encode($payload));
}

// 获取Bearer Token
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// 获取Authorization头
function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
?>