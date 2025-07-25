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
?>