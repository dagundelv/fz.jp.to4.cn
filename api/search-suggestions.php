<?php
/**
 * 搜索建议API
 * 提供实时搜索建议功能
 */
require_once '../includes/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $query = trim($_GET['q'] ?? '');
    $limit = min(intval($_GET['limit'] ?? 8), 20); // 最多20条建议
    
    if (empty($query) || mb_strlen($query) < 2) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        exit;
    }
    
    // 使用缓存避免频繁查询
    $cacheKey = 'search_suggestions_' . md5($query) . '_' . $limit;
    $suggestions = cache_remember($cacheKey, function() use ($db, $query, $limit) {
        $results = [];
        $queryPattern = '%' . $query . '%';
        
        // 搜索医生
        $doctors = $db->fetchAll("
            SELECT 'doctor' as type, id, name, title, 
                   CONCAT(name, ' - ', title) as display_text,
                   '/doctors/detail.php?id=' as url_prefix
            FROM doctors 
            WHERE (name LIKE ? OR title LIKE ?) AND status = 'active'
            ORDER BY 
                CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                view_count DESC
            LIMIT ?
        ", [$queryPattern, $queryPattern, $query . '%', $limit]);
        
        foreach ($doctors as $doctor) {
            $results[] = [
                'type' => 'doctor',
                'text' => $doctor['display_text'],
                'url' => $doctor['url_prefix'] . $doctor['id'],
                'icon' => 'fas fa-user-md',
                'category' => '医生'
            ];
        }
        
        // 搜索医院
        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $hospitals = $db->fetchAll("
                SELECT 'hospital' as type, id, name, level,
                       CONCAT(name, ' - ', level) as display_text,
                       '/hospitals/detail.php?id=' as url_prefix
                FROM hospitals 
                WHERE name LIKE ? AND status = 'active'
                ORDER BY 
                    CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                    level DESC
                LIMIT ?
            ", [$queryPattern, $query . '%', $remaining]);
            
            foreach ($hospitals as $hospital) {
                $results[] = [
                    'type' => 'hospital',
                    'text' => $hospital['display_text'],
                    'url' => $hospital['url_prefix'] . $hospital['id'],
                    'icon' => 'fas fa-hospital',
                    'category' => '医院'
                ];
            }
        }
        
        // 搜索疾病
        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $diseases = $db->fetchAll("
                SELECT 'disease' as type, id, name, category,
                       CONCAT(name, ' - ', category) as display_text,
                       '/diseases/detail.php?id=' as url_prefix
                FROM diseases 
                WHERE (name LIKE ? OR category LIKE ?) AND status = 'active'
                ORDER BY 
                    CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                    view_count DESC
                LIMIT ?
            ", [$queryPattern, $queryPattern, $query . '%', $remaining]);
            
            foreach ($diseases as $disease) {
                $results[] = [
                    'type' => 'disease',
                    'text' => $disease['display_text'],
                    'url' => $disease['url_prefix'] . $disease['id'],
                    'icon' => 'fas fa-stethoscope',
                    'category' => '疾病'
                ];
            }
        }
        
        // 搜索科室
        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $categories = $db->fetchAll("
                SELECT 'category' as type, id, name,
                       name as display_text,
                       '/doctors/?category=' as url_prefix
                FROM categories 
                WHERE name LIKE ? AND status = 'active'
                ORDER BY 
                    CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                    sort_order ASC
                LIMIT ?
            ", [$queryPattern, $query . '%', $remaining]);
            
            foreach ($categories as $category) {
                $results[] = [
                    'type' => 'category',
                    'text' => $category['display_text'],
                    'url' => $category['url_prefix'] . $category['id'],
                    'icon' => 'fas fa-list',
                    'category' => '科室'
                ];
            }
        }
        
        return $results;
    }, 300); // 缓存5分钟
    
    // 记录搜索关键词（用于热门搜索统计）
    if (!empty($suggestions)) {
        $db->query("
            INSERT INTO search_keywords (keyword, search_count, created_at) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            search_count = search_count + 1, 
            updated_at = NOW()
        ", [$query]);
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'query' => $query
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '搜索建议获取失败',
        'error' => $e->getMessage()
    ]);
}
?>