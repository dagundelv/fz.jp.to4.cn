<?php
require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// 获取搜索查询
$query = trim($_GET['q'] ?? '');

if (empty($query) || strlen($query) < 2) {
    jsonResponse(['success' => false, 'message' => '搜索关键词太短']);
}

try {
    $suggestions = getSearchSuggestions($query);
    jsonResponse([
        'success' => true,
        'data' => $suggestions
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => '获取搜索建议失败'
    ], 500);
}

function getSearchSuggestions($query) {
    global $db;
    
    $searchQuery = "%{$query}%";
    $results = [];
    
    // 搜索医院建议（限制5个）
    $hospitalSql = "
        SELECT id, name, level, city
        FROM hospitals 
        WHERE status = 'active' 
        AND (name LIKE ? OR address LIKE ?)
        ORDER BY name LIKE ? DESC, rating DESC
        LIMIT 5
    ";
    
    $hospitals = $db->fetchAll($hospitalSql, [
        $searchQuery, $searchQuery, "%{$query}%"
    ]);
    
    if ($hospitals) {
        $results['hospitals'] = $hospitals;
    }
    
    // 搜索医生建议（限制5个）
    $doctorSql = "
        SELECT d.id, d.name, d.title, h.name as hospital_name
        FROM doctors d 
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        WHERE d.status = 'active' AND h.status = 'active'
        AND (d.name LIKE ? OR d.specialties LIKE ?)
        ORDER BY d.name LIKE ? DESC, d.rating DESC
        LIMIT 5
    ";
    
    $doctors = $db->fetchAll($doctorSql, [
        $searchQuery, $searchQuery, "%{$query}%"
    ]);
    
    if ($doctors) {
        $results['doctors'] = $doctors;
    }
    
    // 搜索疾病建议（限制5个）
    $diseaseSql = "
        SELECT d.id, d.name, c.name as category_name
        FROM diseases d 
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.status = 'active'
        AND (d.name LIKE ? OR d.alias LIKE ?)
        ORDER BY d.name LIKE ? DESC, d.view_count DESC
        LIMIT 5
    ";
    
    $diseases = $db->fetchAll($diseaseSql, [
        $searchQuery, $searchQuery, "%{$query}%"
    ]);
    
    if ($diseases) {
        $results['diseases'] = $diseases;
    }
    
    // 搜索文章建议（限制3个）
    $articleSql = "
        SELECT id, title, publish_time
        FROM articles 
        WHERE status = 'published'
        AND (title LIKE ? OR tags LIKE ?)
        ORDER BY title LIKE ? DESC, publish_time DESC
        LIMIT 3
    ";
    
    $articles = $db->fetchAll($articleSql, [
        $searchQuery, $searchQuery, "%{$query}%"
    ]);
    
    if ($articles) {
        $results['articles'] = $articles;
    }
    
    // 获取热门搜索关键词（限制5个）
    $keywordSql = "
        SELECT keyword, search_count
        FROM search_keywords 
        WHERE keyword LIKE ?
        ORDER BY search_count DESC
        LIMIT 5
    ";
    
    $keywords = $db->fetchAll($keywordSql, [$searchQuery]);
    
    if ($keywords) {
        $results['keywords'] = $keywords;
    }
    
    return $results;
}
?>