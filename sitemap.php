<?php
/**
 * XML站点地图生成器
 * 自动生成网站的XML站点地图
 */
require_once 'includes/init.php';

// 设置内容类型
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // 缓存1小时

// 使用缓存避免频繁生成
$sitemap = cache_remember('sitemap_xml', function() use ($db) {
    $urls = [];
    $baseUrl = SITE_URL;
    
    // 主要页面
    $staticPages = [
        ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/doctors/', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/hospitals/', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/diseases/', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => $baseUrl . '/news/', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/qa/', 'priority' => '0.8', 'changefreq' => 'daily']
    ];
    
    $urls = array_merge($urls, $staticPages);
    
    // 医生页面
    $doctors = $db->fetchAll("
        SELECT id, updated_at 
        FROM doctors 
        WHERE status = 'active' 
        ORDER BY view_count DESC 
        LIMIT 5000
    ");
    
    foreach ($doctors as $doctor) {
        $urls[] = [
            'loc' => $baseUrl . '/doctors/detail.php?id=' . $doctor['id'],
            'lastmod' => date('c', strtotime($doctor['updated_at'])),
            'priority' => '0.7',
            'changefreq' => 'weekly'
        ];
    }
    
    // 医院页面
    $hospitals = $db->fetchAll("
        SELECT id, updated_at 
        FROM hospitals 
        WHERE status = 'active' 
        ORDER BY view_count DESC 
        LIMIT 2000
    ");
    
    foreach ($hospitals as $hospital) {
        $urls[] = [
            'loc' => $baseUrl . '/hospitals/detail.php?id=' . $hospital['id'],
            'lastmod' => date('c', strtotime($hospital['updated_at'])),
            'priority' => '0.7',
            'changefreq' => 'weekly'
        ];
    }
    
    // 疾病页面
    $diseases = $db->fetchAll("
        SELECT id, updated_at 
        FROM diseases 
        WHERE status = 'active' 
        ORDER BY view_count DESC 
        LIMIT 3000
    ");
    
    foreach ($diseases as $disease) {
        $urls[] = [
            'loc' => $baseUrl . '/diseases/detail.php?id=' . $disease['id'],
            'lastmod' => date('c', strtotime($disease['updated_at'])),
            'priority' => '0.6',
            'changefreq' => 'monthly'
        ];
    }
    
    // 新闻文章页面
    $articles = $db->fetchAll("
        SELECT id, updated_at 
        FROM articles 
        WHERE status = 'published' 
        ORDER BY publish_time DESC 
        LIMIT 1000
    ");
    
    foreach ($articles as $article) {
        $urls[] = [
            'loc' => $baseUrl . '/news/detail.php?id=' . $article['id'],
            'lastmod' => date('c', strtotime($article['updated_at'])),
            'priority' => '0.6',
            'changefreq' => 'monthly'
        ];
    }
    
    // 问答页面
    $questions = $db->fetchAll("
        SELECT id, updated_at 
        FROM qa_questions 
        WHERE status = 'published' 
        ORDER BY view_count DESC 
        LIMIT 1000
    ");
    
    foreach ($questions as $question) {
        $urls[] = [
            'loc' => $baseUrl . '/qa/detail.php?id=' . $question['id'],
            'lastmod' => date('c', strtotime($question['updated_at'])),
            'priority' => '0.5',
            'changefreq' => 'monthly'
        ];
    }
    
    // 分类页面
    $categories = $db->fetchAll("
        SELECT id, updated_at 
        FROM categories 
        WHERE status = 'active' AND parent_id = 0
    ");
    
    foreach ($categories as $category) {
        $urls[] = [
            'loc' => $baseUrl . '/doctors/?category=' . $category['id'],
            'lastmod' => date('c', strtotime($category['updated_at'])),
            'priority' => '0.6',
            'changefreq' => 'weekly'
        ];
    }
    
    return generateSitemapXML($urls);
}, 3600); // 缓存1小时

echo $sitemap;

/**
 * 生成XML站点地图
 */
function generateSitemapXML($urls) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
        
        if (isset($url['lastmod'])) {
            $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
        }
        
        if (isset($url['changefreq'])) {
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
        }
        
        if (isset($url['priority'])) {
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
        }
        
        $xml .= "  </url>\n";
    }
    
    $xml .= '</urlset>';
    
    return $xml;
}
?>