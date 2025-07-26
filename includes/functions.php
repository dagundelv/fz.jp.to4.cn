<?php
// 通用函数库

// 安全输出HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 获取当前用户信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
}

// 重定向
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// 生成随机字符串
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// 密码加密
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 格式化时间
function formatTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', $time);
    }
}

// 格式化数字
function formatNumber($number) {
    $number = intval($number);
    
    if ($number >= 100000000) {
        return round($number / 100000000, 1) . '亿';
    } elseif ($number >= 10000) {
        return round($number / 10000, 1) . '万';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'k';
    } else {
        return number_format($number);
    }
}

// 时间前描述（与formatTime类似，但更短）
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 172800) { // 48小时内
        return floor($diff / 86400) . '天前';
    } else {
        return date('m-d', $time);
    }
}

// 截取字符串
function truncate($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
}

// 验证邮箱
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 验证手机号
function isValidPhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

// 生成分页HTML
function generatePagination($currentPage, $totalPages, $baseUrl, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // 上一页
    if ($currentPage > 1) {
        $prevParams = array_merge($params, ['page' => $currentPage - 1]);
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($prevParams) . '" class="prev">上一页</a>';
    }
    
    // 页码
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $firstParams = array_merge($params, ['page' => 1]);
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($firstParams) . '">1</a>';
        if ($start > 2) {
            $html .= '<span class="dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $pageParams = array_merge($params, ['page' => $i]);
            $html .= '<a href="' . $baseUrl . '?' . http_build_query($pageParams) . '">' . $i . '</a>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="dots">...</span>';
        }
        $lastParams = array_merge($params, ['page' => $totalPages]);
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($lastParams) . '">' . $totalPages . '</a>';
    }
    
    // 下一页
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($params, ['page' => $currentPage + 1]);
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($nextParams) . '" class="next">下一页</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// 上传文件
function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = MAX_FILE_SIZE) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制'];
    }
    
    // 检查文件类型
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => '不允许的文件类型'];
    }
    
    // 生成文件名
    $fileName = date('Y/m/d/') . uniqid() . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $fileName;
    $uploadDir = dirname($uploadPath);
    
    // 创建目录
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'filename' => $fileName,
            'url' => UPLOAD_URL . $fileName
        ];
    } else {
        return ['success' => false, 'message' => '文件保存失败'];
    }
}

// 发送JSON响应
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取客户端IP
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// 记录搜索关键词
function recordSearchKeyword($keyword, $category = null, $resultCount = 0) {
    $db = Database::getInstance();
    
    // 检查关键词是否已存在
    $existing = $db->fetch(
        "SELECT id, search_count FROM search_keywords WHERE keyword = ?",
        [$keyword]
    );
    
    if ($existing) {
        // 更新搜索次数
        $db->update(
            'search_keywords',
            [
                'search_count' => $existing['search_count'] + 1,
                'result_count' => $resultCount,
                'category' => $category
            ],
            'id = ?',
            [$existing['id']]
        );
    } else {
        // 插入新关键词
        $db->insert('search_keywords', [
            'keyword' => $keyword,
            'search_count' => 1,
            'result_count' => $resultCount,
            'category' => $category
        ]);
    }
}

// 获取热门搜索关键词
function getHotSearchKeywords($limit = 10, $category = null) {
    $db = Database::getInstance();
    
    $sql = "SELECT keyword, search_count FROM search_keywords";
    $params = [];
    
    if ($category) {
        $sql .= " WHERE category = ? OR category IS NULL";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY search_count DESC LIMIT ?";
    $params[] = $limit;
    
    return $db->fetchAll($sql, $params);
}

// 清理HTML标签
function stripTags($content) {
    return strip_tags($content, '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>');
}

// 生成面包屑导航
function generateBreadcrumb($items) {
    $html = '<div class="breadcrumb">';
    $html .= '<a href="/">首页</a>';
    
    foreach ($items as $item) {
        $html .= ' > ';
        if (isset($item['url'])) {
            $html .= '<a href="' . h($item['url']) . '">' . h($item['title']) . '</a>';
        } else {
            $html .= '<span>' . h($item['title']) . '</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

// 检查是否为管理员
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// 要求管理员权限
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/user/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// 获取最新文章
function getLatestArticles($limit = 6) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.status = 'published' 
        ORDER BY a.created_at DESC 
        LIMIT ?
    ", [$limit]);
}

// 获取推荐医生
function getFeaturedDoctors($limit = 8) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT d.*, h.name as hospital_name, c.name as category_name 
        FROM doctors d 
        LEFT JOIN hospitals h ON d.hospital_id = h.id 
        LEFT JOIN categories c ON d.category_id = c.id 
        WHERE d.status = 'active' 
        ORDER BY d.rating DESC, d.created_at DESC 
        LIMIT ?
    ", [$limit]);
}

// 获取热门问题
function getHotQuestions($limit = 6) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT q.*, u.username, c.name as category_name,
               (SELECT COUNT(*) FROM qa_answers WHERE question_id = q.id AND status = 'published') as answer_count
        FROM qa_questions q 
        LEFT JOIN users u ON q.user_id = u.id 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.status = 'published' 
        ORDER BY q.view_count DESC, q.created_at DESC 
        LIMIT ?
    ", [$limit]);
}

// 获取分类树
function getCategoryTree() {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT * FROM categories 
        WHERE status = 'active' 
        ORDER BY parent_id, sort_order, name
    ");
}

// 获取热门搜索
function getPopularSearches($limit = 8) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT keyword, search_count 
        FROM search_keywords 
        WHERE search_count > 1 
        ORDER BY search_count DESC 
        LIMIT ?
    ", [$limit]);
}

// 获取网站统计
function getSiteStats() {
    $db = Database::getInstance();
    return [
        'doctors' => $db->fetch("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")['count'],
        'hospitals' => $db->fetch("SELECT COUNT(*) as count FROM hospitals WHERE status = 'active'")['count'],
        'articles' => $db->fetch("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
        'questions' => $db->fetch("SELECT COUNT(*) as count FROM qa_questions WHERE status = 'published'")['count'],
        'users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count']
    ];
}

// 获取分类列表
function getCategories($parentId = 0) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
        [$parentId]
    );
}

// 根据ID获取分类
function getCategoryById($id) {
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$id]);
}
?>