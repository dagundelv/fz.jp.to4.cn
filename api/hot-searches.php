<?php
/**
 * 热门搜索API
 * 提供热门搜索关键词
 */
require_once '../includes/init.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600'); // 缓存1小时

try {
    $limit = min(intval($_GET['limit'] ?? 10), 20);
    
    // 使用缓存提升性能
    $cacheKey = 'hot_searches_' . $limit;
    $hotSearches = cache_remember($cacheKey, function() use ($db, $limit) {
        // 获取热门搜索关键词
        $searches = $db->fetchAll("
            SELECT 
                keyword,
                search_count,
                CASE 
                    WHEN search_count > 1000 THEN 1 
                    ELSE 0 
                END as is_hot,
                updated_at
            FROM search_keywords 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND LENGTH(keyword) >= 2
            ORDER BY search_count DESC, updated_at DESC
            LIMIT ?
        ", [$limit]);
        
        // 如果数据不足，添加一些默认热门搜索
        if (count($searches) < $limit) {
            $defaultSearches = [
                ['keyword' => '感冒发烧', 'search_count' => 800, 'is_hot' => 0],
                ['keyword' => '高血压', 'search_count' => 750, 'is_hot' => 0],
                ['keyword' => '糖尿病', 'search_count' => 700, 'is_hot' => 0],
                ['keyword' => '心脏病', 'search_count' => 650, 'is_hot' => 0],
                ['keyword' => '头痛', 'search_count' => 600, 'is_hot' => 0],
                ['keyword' => '咳嗽', 'search_count' => 550, 'is_hot' => 0],
                ['keyword' => '腹泻', 'search_count' => 500, 'is_hot' => 0],
                ['keyword' => '失眠', 'search_count' => 450, 'is_hot' => 0],
                ['keyword' => '三甲医院', 'search_count' => 400, 'is_hot' => 0],
                ['keyword' => '儿科专家', 'search_count' => 350, 'is_hot' => 0]
            ];
            
            // 合并现有搜索和默认搜索
            $existingKeywords = array_column($searches, 'keyword');
            foreach ($defaultSearches as $default) {
                if (!in_array($default['keyword'], $existingKeywords) && count($searches) < $limit) {
                    $searches[] = $default;
                }
            }
        }
        
        return array_slice($searches, 0, $limit);
    }, 3600); // 缓存1小时
    
    echo json_encode([
        'success' => true,
        'data' => $hotSearches,
        'total' => count($hotSearches)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '获取热门搜索失败',
        'error' => $e->getMessage()
    ]);
}
?>