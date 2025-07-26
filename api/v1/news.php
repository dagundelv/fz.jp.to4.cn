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
            // 获取单篇文章详情
            if (isset($uri[4]) && is_numeric($uri[4])) {
                $articleId = intval($uri[4]);
                $article = $db->fetch("
                    SELECT a.*, c.name as category_name
                    FROM articles a 
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE a.id = ? AND a.status = 'published'
                ", [$articleId]);
                
                if (!$article) {
                    throw new Exception('文章不存在', 404);
                }
                
                // 更新浏览量
                $db->update('articles', ['view_count' => $article['view_count'] + 1], 'id = ?', [$articleId]);
                
                // 获取相关文章
                $relatedArticles = [];
                if ($article['category_id']) {
                    $relatedArticles = $db->fetchAll("
                        SELECT a.id, a.title, a.featured_image, a.publish_time, a.view_count
                        FROM articles a 
                        WHERE a.status = 'published' 
                        AND a.category_id = ? 
                        AND a.id != ?
                        ORDER BY a.publish_time DESC
                        LIMIT 5
                    ", [$article['category_id'], $articleId]);
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'article' => $article,
                        'related' => $relatedArticles
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } else {
                // 获取文章列表
                $page = max(1, intval($_GET['page'] ?? 1));
                $pageSize = min(50, intval($_GET['limit'] ?? 20));
                $category = $_GET['category'] ?? '';
                $keyword = trim($_GET['keyword'] ?? '');
                
                $whereConditions = ["a.status = 'published'"];
                $queryParams = [];
                
                if ($category) {
                    $whereConditions[] = "a.category_id = ?";
                    $queryParams[] = $category;
                }
                
                if ($keyword) {
                    $whereConditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
                    $searchTerm = "%{$keyword}%";
                    $queryParams[] = $searchTerm;
                    $queryParams[] = $searchTerm;
                }
                
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
                
                // 获取总数
                $total = $db->fetch("SELECT COUNT(*) as count FROM articles a {$whereClause}", $queryParams)['count'];
                
                // 获取文章列表
                $offset = ($page - 1) * $pageSize;
                $listParams = array_merge($queryParams, [$pageSize, $offset]);
                
                $articles = $db->fetchAll("
                    SELECT a.id, a.title, a.subtitle, a.summary, a.featured_image, 
                           a.author, a.publish_time, a.view_count, a.like_count, a.share_count,
                           c.name as category_name
                    FROM articles a 
                    LEFT JOIN categories c ON a.category_id = c.id
                    {$whereClause}
                    ORDER BY a.is_featured DESC, a.publish_time DESC
                    LIMIT ? OFFSET ?
                ", $listParams);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'articles' => $articles,
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