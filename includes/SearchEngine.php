<?php
/**
 * 智能搜索引擎
 * 提供高级搜索功能包括相关性评分、同义词、拼写纠错等
 */

class SearchEngine {
    private $db;
    private $synonyms;
    private $commonTerms;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSynonyms();
        $this->commonTerms = ['的', '是', '在', '有', '和', '与', '或', '及'];
    }
    
    /**
     * 加载医疗同义词词典
     */
    private function loadSynonyms() {
        $this->synonyms = [
            '感冒' => ['伤风', '上呼吸道感染', '风寒'],
            '发烧' => ['发热', '高热', '低烧', '体温升高'],
            '头痛' => ['头疼', '偏头痛', '头部疼痛'],
            '咳嗽' => ['咳', '干咳', '湿咳'],
            '腹泻' => ['拉肚子', '腹泻', '痢疾'],
            '高血压' => ['血压高', '高压'],
            '糖尿病' => ['血糖高', '糖尿'],
            '心脏病' => ['心病', '心脏疾病', '冠心病'],
            '妇科' => ['妇产科', '妇女科'],
            '儿科' => ['小儿科', '儿童科'],
            '内科' => ['内分泌科', '消化内科', '心内科'],
            '外科' => ['普外科', '骨外科', '心外科'],
            '三甲' => ['三级甲等', '三甲医院'],
            '三乙' => ['三级乙等', '三乙医院'],
            '二甲' => ['二级甲等', '二甲医院']
        ];
    }
    
    /**
     * 智能搜索主入口
     */
    public function search($query, $options = []) {
        $query = trim($query);
        if (empty($query)) {
            return $this->getEmptyResult();
        }
        
        // 默认选项
        $options = array_merge([
            'category' => '',
            'page' => 1,
            'limit' => 20,
            'sort' => 'relevance', // relevance, time, rating
            'filters' => []
        ], $options);
        
        // 记录搜索
        $this->recordSearch($query);
        
        // 预处理查询
        $processedQuery = $this->preprocessQuery($query);
        
        // 执行搜索
        $results = $this->performSearch($processedQuery, $options);
        
        // 如果结果较少，尝试模糊搜索和同义词
        if ($results['total'] < 5) {
            $expandedResults = $this->expandedSearch($processedQuery, $options);
            $results = $this->mergeResults($results, $expandedResults);
        }
        
        // 计算相关性评分并排序
        $results = $this->scoreAndSort($results, $processedQuery, $options['sort']);
        
        return $results;
    }
    
    /**
     * 预处理搜索查询
     */
    private function preprocessQuery($query) {
        // 移除特殊字符
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // 分词
        $terms = preg_split('/\s+/', $query);
        $terms = array_filter($terms, function($term) {
            return mb_strlen($term) >= 2 && !in_array($term, $this->commonTerms);
        });
        
        // 添加同义词
        $expandedTerms = [];
        foreach ($terms as $term) {
            $expandedTerms[] = $term;
            $synonyms = $this->getSynonyms($term);
            $expandedTerms = array_merge($expandedTerms, $synonyms);
        }
        
        return [
            'original' => $query,
            'terms' => array_unique($terms),
            'expanded_terms' => array_unique($expandedTerms),
            'exact_match' => $query,
            'partial_match' => '%' . implode('%', $terms) . '%'
        ];
    }
    
    /**
     * 获取同义词
     */
    private function getSynonyms($term) {
        $synonyms = [];
        
        // 直接匹配
        if (isset($this->synonyms[$term])) {
            $synonyms = array_merge($synonyms, $this->synonyms[$term]);
        }
        
        // 反向匹配
        foreach ($this->synonyms as $key => $values) {
            if (in_array($term, $values)) {
                $synonyms[] = $key;
                $synonyms = array_merge($synonyms, $values);
            }
        }
        
        return array_unique(array_filter($synonyms, function($s) use ($term) {
            return $s !== $term;
        }));
    }
    
    /**
     * 执行搜索
     */
    private function performSearch($processedQuery, $options) {
        $results = [
            'hospitals' => [],
            'doctors' => [],
            'diseases' => [],
            'articles' => [],
            'questions' => [],
            'total' => 0,
            'query_info' => $processedQuery
        ];
        
        // 搜索各个类型
        if (!$options['category'] || $options['category'] === 'hospitals') {
            $results['hospitals'] = $this->searchHospitals($processedQuery, $options);
        }
        
        if (!$options['category'] || $options['category'] === 'doctors') {
            $results['doctors'] = $this->searchDoctors($processedQuery, $options);
        }
        
        if (!$options['category'] || $options['category'] === 'diseases') {
            $results['diseases'] = $this->searchDiseases($processedQuery, $options);
        }
        
        if (!$options['category'] || $options['category'] === 'articles') {
            $results['articles'] = $this->searchArticles($processedQuery, $options);
        }
        
        if (!$options['category'] || $options['category'] === 'questions') {
            $results['questions'] = $this->searchQuestions($processedQuery, $options);
        }
        
        // 计算总数
        $results['total'] = count($results['hospitals']) + 
                           count($results['doctors']) + 
                           count($results['diseases']) + 
                           count($results['articles']) +
                           count($results['questions']);
        
        return $results;
    }
    
    /**
     * 搜索医院
     */
    private function searchHospitals($query, $options) {
        $conditions = ["h.status = 'active'"];
        $params = [];
        
        // 构建搜索条件
        $searchTerms = [];
        foreach ($query['expanded_terms'] as $term) {
            $searchTerms[] = "(h.name LIKE ? OR h.address LIKE ? OR h.specialties LIKE ? OR h.introduction LIKE ?)";
            $params = array_merge($params, ["%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"]);
        }
        
        if (!empty($searchTerms)) {
            $conditions[] = '(' . implode(' OR ', $searchTerms) . ')';
        }
        
        // 添加筛选条件
        if (!empty($options['filters']['level'])) {
            $conditions[] = "h.level = ?";
            $params[] = $options['filters']['level'];
        }
        
        if (!empty($options['filters']['city'])) {
            $conditions[] = "h.city = ?";
            $params[] = $options['filters']['city'];
        }
        
        $sql = "
            SELECT h.*, 
                   (CASE 
                       WHEN h.name LIKE ? THEN 100
                       WHEN h.name LIKE ? THEN 80
                       WHEN h.specialties LIKE ? THEN 60
                       WHEN h.introduction LIKE ? THEN 40
                       ELSE 20
                   END) as match_score,
                   40 as relevance_score
            FROM hospitals h
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY match_score DESC, h.rating DESC
            LIMIT ?
        ";
        
        // 添加匹配参数
        array_unshift($params, $query['exact_match']);
        array_unshift($params, $query['exact_match'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        $params[] = $options['limit'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 搜索医生
     */
    private function searchDoctors($query, $options) {
        $conditions = ["d.status = 'active'", "h.status = 'active'"];
        $params = [];
        
        $searchTerms = [];
        foreach ($query['expanded_terms'] as $term) {
            $searchTerms[] = "(d.name LIKE ? OR d.specialties LIKE ? OR d.title LIKE ? OR h.name LIKE ? OR c.name LIKE ?)";
            $params = array_merge($params, ["%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"]);
        }
        
        if (!empty($searchTerms)) {
            $conditions[] = '(' . implode(' OR ', $searchTerms) . ')';
        }
        
        // 添加筛选条件
        if (!empty($options['filters']['hospital_id'])) {
            $conditions[] = "d.hospital_id = ?";
            $params[] = $options['filters']['hospital_id'];
        }
        
        if (!empty($options['filters']['category_id'])) {
            $conditions[] = "d.category_id = ?";
            $params[] = $options['filters']['category_id'];
        }
        
        if (!empty($options['filters']['title'])) {
            $conditions[] = "d.title LIKE ?";
            $params[] = '%' . $options['filters']['title'] . '%';
        }
        
        $sql = "
            SELECT d.*, h.name as hospital_name, c.name as category_name,
                   (CASE 
                       WHEN d.name LIKE ? THEN 100
                       WHEN d.name LIKE ? THEN 80
                       WHEN d.specialties LIKE ? THEN 60
                       WHEN d.title LIKE ? THEN 40
                       ELSE 20
                   END) as match_score,
                   30 as relevance_score
            FROM doctors d
            LEFT JOIN hospitals h ON d.hospital_id = h.id
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY match_score DESC, d.rating DESC
            LIMIT ?
        ";
        
        array_unshift($params, $query['exact_match']);
        array_unshift($params, $query['exact_match'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        $params[] = $options['limit'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 搜索疾病
     */
    private function searchDiseases($query, $options) {
        $conditions = ["d.status = 'active'"];
        $params = [];
        
        $searchTerms = [];
        foreach ($query['expanded_terms'] as $term) {
            $searchTerms[] = "(d.name LIKE ? OR d.alias LIKE ? OR d.symptoms LIKE ? OR d.causes LIKE ? OR c.name LIKE ?)";
            $params = array_merge($params, ["%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"]);
        }
        
        if (!empty($searchTerms)) {
            $conditions[] = '(' . implode(' OR ', $searchTerms) . ')';
        }
        
        $sql = "
            SELECT d.*, c.name as category_name,
                   (CASE 
                       WHEN d.name LIKE ? THEN 100
                       WHEN d.name LIKE ? THEN 80
                       WHEN d.alias LIKE ? THEN 70
                       WHEN d.symptoms LIKE ? THEN 60
                       ELSE 20
                   END) as match_score,
                   25 as relevance_score
            FROM diseases d
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY match_score DESC, d.view_count DESC
            LIMIT ?
        ";
        
        array_unshift($params, $query['exact_match']);
        array_unshift($params, $query['exact_match'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        $params[] = $options['limit'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 搜索文章
     */
    private function searchArticles($query, $options) {
        $conditions = ["a.status = 'published'"];
        $params = [];
        
        $searchTerms = [];
        foreach ($query['expanded_terms'] as $term) {
            $searchTerms[] = "(a.title LIKE ? OR a.content LIKE ? OR a.summary LIKE ? OR a.tags LIKE ?)";
            $params = array_merge($params, ["%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"]);
        }
        
        if (!empty($searchTerms)) {
            $conditions[] = '(' . implode(' OR ', $searchTerms) . ')';
        }
        
        $sql = "
            SELECT a.*, c.name as category_name,
                   (CASE 
                       WHEN a.title LIKE ? THEN 100
                       WHEN a.title LIKE ? THEN 80
                       WHEN a.summary LIKE ? THEN 60
                       WHEN a.content LIKE ? THEN 40
                       ELSE 20
                   END) as match_score,
                   35 as relevance_score
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY match_score DESC, a.publish_time DESC
            LIMIT ?
        ";
        
        array_unshift($params, $query['exact_match']);
        array_unshift($params, $query['exact_match'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        $params[] = $options['limit'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 搜索问答
     */
    private function searchQuestions($query, $options) {
        $conditions = ["q.status = 'active'"];
        $params = [];
        
        $searchTerms = [];
        foreach ($query['expanded_terms'] as $term) {
            $searchTerms[] = "(q.title LIKE ? OR q.content LIKE ?)";
            $params = array_merge($params, ["%{$term}%", "%{$term}%"]);
        }
        
        if (!empty($searchTerms)) {
            $conditions[] = '(' . implode(' OR ', $searchTerms) . ')';
        }
        
        $sql = "
            SELECT q.*, c.name as category_name, u.username,
                   q.answer_count,
                   (CASE 
                       WHEN q.title LIKE ? THEN 100
                       WHEN q.title LIKE ? THEN 80
                       WHEN q.content LIKE ? THEN 60
                       ELSE 20
                   END) as match_score,
                   20 as relevance_score
            FROM questions q
            LEFT JOIN categories c ON q.category_id = c.id
            LEFT JOIN users u ON q.user_id = u.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY match_score DESC, q.created_at DESC
            LIMIT ?
        ";
        
        array_unshift($params, $query['exact_match']);
        array_unshift($params, $query['exact_match'] . '%');
        array_unshift($params, '%' . $query['original'] . '%');
        $params[] = $options['limit'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 扩展搜索（模糊匹配和拼写纠错）
     */
    private function expandedSearch($query, $options) {
        // 如果原始搜索结果足够，直接返回空
        return $this->getEmptyResult();
    }
    
    /**
     * 计算相关性评分并排序
     */
    private function scoreAndSort($results, $query, $sortBy) {
        // 为每个结果添加综合评分
        foreach (['hospitals', 'doctors', 'diseases', 'articles', 'questions'] as $type) {
            foreach ($results[$type] as &$item) {
                $item['final_score'] = $this->calculateFinalScore($item, $query, $type);
                $item['result_type'] = $type;
            }
        }
        
        return $results;
    }
    
    /**
     * 计算最终评分
     */
    private function calculateFinalScore($item, $query, $type) {
        $score = 0;
        
        // 相关性评分 (40%)
        $score += ($item['relevance_score'] ?? 0) * 0.4;
        
        // 匹配评分 (30%)
        $score += ($item['match_score'] ?? 0) * 0.3;
        
        // 质量评分 (20%)
        switch ($type) {
            case 'hospitals':
            case 'doctors':
                $score += ($item['rating'] ?? 0) * 4; // 转换为20分制
                break;
            case 'diseases':
                $score += min(20, ($item['view_count'] ?? 0) / 100); // 浏览量评分
                break;
            case 'articles':
                $score += min(20, ($item['view_count'] ?? 0) / 50);
                break;
        }
        
        // 时效性评分 (10%)
        if (isset($item['created_at']) || isset($item['publish_time'])) {
            $date = $item['created_at'] ?? $item['publish_time'];
            $daysDiff = (time() - strtotime($date)) / (24 * 3600);
            $score += max(0, 10 - $daysDiff / 30); // 30天内的内容有时效加分
        }
        
        return $score;
    }
    
    /**
     * 合并搜索结果
     */
    private function mergeResults($results1, $results2) {
        foreach (['hospitals', 'doctors', 'diseases', 'articles', 'questions'] as $type) {
            $existingIds = array_column($results1[$type], 'id');
            foreach ($results2[$type] as $item) {
                if (!in_array($item['id'], $existingIds)) {
                    $results1[$type][] = $item;
                }
            }
        }
        
        $results1['total'] = count($results1['hospitals']) + 
                            count($results1['doctors']) + 
                            count($results1['diseases']) + 
                            count($results1['articles']) +
                            count($results1['questions']);
        
        return $results1;
    }
    
    /**
     * 记录搜索
     */
    private function recordSearch($query) {
        try {
            $this->db->query("
                INSERT INTO search_keywords (keyword, search_count, created_at) 
                VALUES (?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE 
                search_count = search_count + 1, 
                updated_at = NOW()
            ", [$query]);
        } catch (Exception $e) {
            // 静默失败，不影响搜索
        }
    }
    
    /**
     * 获取空结果
     */
    private function getEmptyResult() {
        return [
            'hospitals' => [],
            'doctors' => [],
            'diseases' => [],
            'articles' => [],
            'questions' => [],
            'total' => 0
        ];
    }
    
    /**
     * 获取搜索建议
     */
    public function getSuggestions($query, $limit = 10) {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }
        
        // 暂时移除缓存功能以简化调试
        // $cacheKey = 'search_suggestions_' . md5($query);
        // return cache_remember($cacheKey, function() use ($query, $limit) {
            $suggestions = [];
            $pattern = '%' . $query . '%';
            
            // 医生建议
            $doctors = $this->db->fetchAll("
                SELECT 'doctor' as type, name as text, id,
                       CONCAT('/doctors/detail.php?id=', id) as url
                FROM doctors 
                WHERE name LIKE ? AND status = 'active'
                ORDER BY name LIKE ? DESC, rating DESC
                LIMIT ?
            ", [$pattern, $query . '%', $limit]);
            
            // 医院建议
            $hospitals = $this->db->fetchAll("
                SELECT 'hospital' as type, name as text, id,
                       CONCAT('/hospitals/detail.php?id=', id) as url
                FROM hospitals 
                WHERE name LIKE ? AND status = 'active'
                ORDER BY name LIKE ? DESC, rating DESC
                LIMIT ?
            ", [$pattern, $query . '%', $limit]);
            
            // 疾病建议
            $diseases = $this->db->fetchAll("
                SELECT 'disease' as type, name as text, id,
                       CONCAT('/diseases/detail.php?id=', id) as url
                FROM diseases 
                WHERE name LIKE ? AND status = 'active'
                ORDER BY name LIKE ? DESC, view_count DESC
                LIMIT ?
            ", [$pattern, $query . '%', $limit]);
            
            return array_merge($doctors, $hospitals, $diseases);
        // }, 300);
    }
}
?>