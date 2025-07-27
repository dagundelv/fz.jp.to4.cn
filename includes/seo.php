<?php
/**
 * SEO优化工具类
 * 提供结构化数据、元标签、站点地图等SEO功能
 */

class SEOManager {
    private static $instance = null;
    private $defaultMeta = [];
    private $openGraphData = [];
    private $twitterCardData = [];
    private $structuredData = [];
    
    private function __construct() {
        $this->initDefaults();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initDefaults() {
        $this->defaultMeta = [
            'charset' => 'UTF-8',
            'viewport' => 'width=device-width, initial-scale=1.0',
            'robots' => 'index, follow',
            'author' => SITE_NAME,
            'generator' => 'PHP Health Platform',
            'theme-color' => '#007bff'
        ];
    }
    
    /**
     * 生成页面元标签
     */
    public function generateMetaTags($pageData = []) {
        $meta = array_merge($this->defaultMeta, $pageData);
        $html = '';
        
        // 基础meta标签
        foreach ($meta as $name => $content) {
            if (in_array($name, ['charset', 'viewport'])) {
                if ($name === 'charset') {
                    $html .= "<meta charset=\"{$content}\">\n";
                } else {
                    $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
                }
            } elseif ($content) {
                $html .= "<meta name=\"{$name}\" content=\"" . htmlspecialchars($content) . "\">\n";
            }
        }
        
        // 规范化URL
        if (isset($meta['canonical'])) {
            $html .= "<link rel=\"canonical\" href=\"{$meta['canonical']}\">\n";
        }
        
        // Open Graph标签
        if (!empty($this->openGraphData)) {
            $html .= $this->generateOpenGraphTags();
        }
        
        // Twitter Card标签
        if (!empty($this->twitterCardData)) {
            $html .= $this->generateTwitterCardTags();
        }
        
        return $html;
    }
    
    /**
     * 设置Open Graph数据
     */
    public function setOpenGraph($data) {
        $this->openGraphData = array_merge([
            'og:site_name' => SITE_NAME,
            'og:type' => 'website',
            'og:locale' => 'zh_CN'
        ], $data);
        
        return $this;
    }
    
    /**
     * 生成Open Graph标签
     */
    private function generateOpenGraphTags() {
        $html = '';
        foreach ($this->openGraphData as $property => $content) {
            if ($content) {
                $html .= "<meta property=\"{$property}\" content=\"" . htmlspecialchars($content) . "\">\n";
            }
        }
        return $html;
    }
    
    /**
     * 设置Twitter Card数据
     */
    public function setTwitterCard($data) {
        $this->twitterCardData = array_merge([
            'twitter:card' => 'summary_large_image',
            'twitter:site' => '@' . SITE_NAME
        ], $data);
        
        return $this;
    }
    
    /**
     * 生成Twitter Card标签
     */
    private function generateTwitterCardTags() {
        $html = '';
        foreach ($this->twitterCardData as $name => $content) {
            if ($content) {
                $html .= "<meta name=\"{$name}\" content=\"" . htmlspecialchars($content) . "\">\n";
            }
        }
        return $html;
    }
    
    /**
     * 添加结构化数据
     */
    public function addStructuredData($type, $data) {
        $this->structuredData[] = array_merge([
            '@context' => 'https://schema.org',
            '@type' => $type
        ], $data);
        
        return $this;
    }
    
    /**
     * 生成结构化数据JSON-LD
     */
    public function generateStructuredData() {
        if (empty($this->structuredData)) {
            return '';
        }
        
        $html = "<script type=\"application/ld+json\">\n";
        
        if (count($this->structuredData) === 1) {
            $html .= json_encode($this->structuredData[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $html .= json_encode([
                '@context' => 'https://schema.org',
                '@graph' => $this->structuredData
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        $html .= "\n</script>\n";
        
        return $html;
    }
    
    /**
     * 生成医生页面结构化数据
     */
    public function generateDoctorSchema($doctor) {
        $schema = [
            '@type' => 'Person',
            'name' => $doctor['name'],
            'jobTitle' => $doctor['title'],
            'description' => $doctor['introduction'] ?? '',
            'url' => SITE_URL . '/doctors/detail.php?id=' . $doctor['id']
        ];
        
        if ($doctor['avatar']) {
            $schema['image'] = SITE_URL . $doctor['avatar'];
        }
        
        if (isset($doctor['hospital_name'])) {
            $schema['worksFor'] = [
                '@type' => 'Hospital',
                'name' => $doctor['hospital_name'],
                'address' => $doctor['hospital_address'] ?? ''
            ];
        }
        
        if ($doctor['specialties']) {
            $schema['knowsAbout'] = explode(',', $doctor['specialties']);
        }
        
        if ($doctor['rating']) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $doctor['rating'],
                'bestRating' => 5
            ];
        }
        
        return $this->addStructuredData('Person', $schema);
    }
    
    /**
     * 生成医院页面结构化数据
     */
    public function generateHospitalSchema($hospital) {
        $schema = [
            '@type' => 'Hospital',
            'name' => $hospital['name'],
            'description' => $hospital['description'] ?? '',
            'url' => SITE_URL . '/hospitals/detail.php?id=' . $hospital['id']
        ];
        
        if ($hospital['address']) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $hospital['address'],
                'addressLocality' => $hospital['city'] ?? '',
                'addressCountry' => 'CN'
            ];
        }
        
        if ($hospital['phone']) {
            $schema['telephone'] = $hospital['phone'];
        }
        
        if ($hospital['website']) {
            $schema['url'] = $hospital['website'];
        }
        
        $schema['medicalSpecialty'] = $hospital['specialties'] ?? '综合医疗';
        
        return $this->addStructuredData('Hospital', $schema);
    }
    
    /**
     * 生成文章页面结构化数据
     */
    public function generateArticleSchema($article) {
        $schema = [
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['summary'] ?? strip_tags(substr($article['content'], 0, 200)),
            'url' => SITE_URL . '/news/detail.php?id=' . $article['id'],
            'datePublished' => date('c', strtotime($article['publish_time'])),
            'dateModified' => date('c', strtotime($article['updated_at'] ?? $article['publish_time'])),
            'author' => [
                '@type' => 'Person',
                'name' => $article['author'] ?? SITE_NAME
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => SITE_URL . '/assets/images/logo.png'
                ]
            ]
        ];
        
        if ($article['featured_image']) {
            $schema['image'] = SITE_URL . $article['featured_image'];
        }
        
        if ($article['category_name']) {
            $schema['about'] = $article['category_name'];
        }
        
        return $this->addStructuredData('Article', $schema);
    }
    
    /**
     * 生成网站组织结构化数据
     */
    public function generateOrganizationSchema() {
        $schema = [
            '@type' => 'Organization',
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'logo' => SITE_URL . '/assets/images/logo.png',
            'description' => SITE_DESCRIPTION,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+86-400-123-4567',
                'contactType' => 'customer service',
                'areaServed' => 'CN',
                'availableLanguage' => 'Chinese'
            ],
            'sameAs' => [
                // 社交媒体链接
            ]
        ];
        
        return $this->addStructuredData('Organization', $schema);
    }
    
    /**
     * 生成面包屑导航结构化数据
     */
    public function generateBreadcrumbSchema($breadcrumbs) {
        $items = [];
        
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['title'],
                'item' => isset($breadcrumb['url']) ? SITE_URL . $breadcrumb['url'] : null
            ];
        }
        
        $schema = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
        
        return $this->addStructuredData('BreadcrumbList', $schema);
    }
    
    /**
     * 生成FAQ结构化数据
     */
    public function generateFAQSchema($faqs) {
        $questions = [];
        
        foreach ($faqs as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        $schema = [
            '@type' => 'FAQPage',
            'mainEntity' => $questions
        ];
        
        return $this->addStructuredData('FAQPage', $schema);
    }
    
    /**
     * 生成搜索框结构化数据
     */
    public function generateSearchBoxSchema() {
        $schema = [
            '@type' => 'WebSite',
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => SITE_URL . '/search.php?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        return $this->addStructuredData('WebSite', $schema);
    }
    
    /**
     * 清理和重置数据
     */
    public function reset() {
        $this->openGraphData = [];
        $this->twitterCardData = [];
        $this->structuredData = [];
        return $this;
    }
}

/**
 * SEO助手函数
 */
function seo() {
    return SEOManager::getInstance();
}

/**
 * 生成优化的页面标题
 */
function generateSEOTitle($title, $suffix = true) {
    $title = trim($title);
    
    if ($suffix && strpos($title, SITE_NAME) === false) {
        $title .= ' - ' . SITE_NAME;
    }
    
    // 限制标题长度
    if (mb_strlen($title) > 60) {
        $title = mb_substr($title, 0, 57) . '...';
    }
    
    return $title;
}

/**
 * 生成优化的页面描述
 */
function generateSEODescription($description, $maxLength = 160) {
    $description = strip_tags($description);
    $description = preg_replace('/\s+/', ' ', trim($description));
    
    if (mb_strlen($description) > $maxLength) {
        $description = mb_substr($description, 0, $maxLength - 3) . '...';
    }
    
    return $description;
}

/**
 * 生成页面关键词
 */
function generateSEOKeywords($keywords, $maxCount = 10) {
    if (is_string($keywords)) {
        $keywords = explode(',', $keywords);
    }
    
    $keywords = array_map('trim', $keywords);
    $keywords = array_filter($keywords);
    $keywords = array_unique($keywords);
    $keywords = array_slice($keywords, 0, $maxCount);
    
    return implode(', ', $keywords);
}

/**
 * 生成规范化URL
 */
function generateCanonicalUrl($path = null) {
    if ($path === null) {
        $path = $_SERVER['REQUEST_URI'];
    }
    
    // 移除查询参数（除了重要的SEO参数）
    $allowedParams = ['id', 'page', 'category', 'tag'];
    $urlParts = parse_url($path);
    
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $params);
        $filteredParams = array_intersect_key($params, array_flip($allowedParams));
        
        if (!empty($filteredParams)) {
            $urlParts['query'] = http_build_query($filteredParams);
        } else {
            unset($urlParts['query']);
        }
    }
    
    $canonicalPath = $urlParts['path'];
    if (isset($urlParts['query'])) {
        $canonicalPath .= '?' . $urlParts['query'];
    }
    
    return SITE_URL . $canonicalPath;
}

/**
 * 生成页面元数据
 */
function generatePageMeta($data) {
    $meta = [
        'title' => generateSEOTitle($data['title'] ?? ''),
        'description' => generateSEODescription($data['description'] ?? ''),
        'keywords' => generateSEOKeywords($data['keywords'] ?? []),
        'canonical' => generateCanonicalUrl($data['canonical'] ?? null),
        'robots' => $data['robots'] ?? 'index, follow'
    ];
    
    // 如果有图片，添加图片meta
    if (isset($data['image'])) {
        $meta['image'] = $data['image'];
    }
    
    return $meta;
}
?>