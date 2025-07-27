<?php
/**
 * 性能优化类
 * 包含页面压缩、资源压缩、延迟加载等功能
 */

class PerformanceOptimizer {
    private static $instance = null;
    private $startTime;
    private $memoryStart;
    
    private function __construct() {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 启用输出压缩
     */
    public static function enableGzipCompression() {
        if (!ob_get_level() && extension_loaded('zlib') && !headers_sent()) {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', '6');
        }
    }
    
    /**
     * 设置缓存头
     */
    public static function setCacheHeaders($type = 'static', $maxAge = 3600) {
        if (headers_sent()) return;
        
        switch ($type) {
            case 'static':
                // 静态资源缓存1天
                header('Cache-Control: public, max-age=86400');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
                break;
                
            case 'dynamic':
                // 动态内容缓存指定时间
                header('Cache-Control: public, max-age=' . $maxAge);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
                break;
                
            case 'no-cache':
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                break;
        }
        
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . md5($_SERVER['REQUEST_URI']) . '"');
    }
    
    /**
     * 压缩CSS
     */
    public static function compressCSS($css) {
        // 移除注释
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // 移除多余空白
        $css = preg_replace('/\s+/', ' ', $css);
        // 移除不必要的分号和空格
        $css = str_replace(['; ', ' {', '{ ', ' }', '} '], [';', '{', '{', '}', '}'], $css);
        // 移除前后空白
        return trim($css);
    }
    
    /**
     * 压缩JavaScript
     */
    public static function compressJS($js) {
        // 简单的JS压缩（移除注释和多余空白）
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // 移除块注释
        $js = preg_replace('/\/\/.*$/m', '', $js); // 移除行注释
        $js = preg_replace('/\s+/', ' ', $js); // 压缩空白
        return trim($js);
    }
    
    /**
     * 生成资源URL（带版本号）
     */
    public static function assetUrl($path, $addTimestamp = true) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
        $baseUrl = '//' . $_SERVER['HTTP_HOST'];
        
        if ($addTimestamp && file_exists($fullPath)) {
            $timestamp = filemtime($fullPath);
            $separator = strpos($path, '?') !== false ? '&' : '?';
            return $baseUrl . $path . $separator . 'v=' . $timestamp;
        }
        
        return $baseUrl . $path;
    }
    
    /**
     * 内联关键CSS
     */
    public static function inlineCriticalCSS($cssFile) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $cssFile;
        if (file_exists($fullPath)) {
            $css = file_get_contents($fullPath);
            $css = self::compressCSS($css);
            return '<style>' . $css . '</style>';
        }
        return '';
    }
    
    /**
     * 预加载重要资源
     */
    public static function preloadResource($url, $type = 'script') {
        if (headers_sent()) return;
        
        $as = $type === 'css' ? 'style' : $type;
        header("Link: <{$url}>; rel=preload; as={$as}", false);
    }
    
    /**
     * 延迟加载脚本
     */
    public static function deferScript($src, $attributes = []) {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<script src="' . htmlspecialchars($src) . '" defer' . $attrs . '></script>';
    }
    
    /**
     * 异步加载脚本
     */
    public static function asyncScript($src, $attributes = []) {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<script src="' . htmlspecialchars($src) . '" async' . $attrs . '></script>';
    }
    
    /**
     * 生成图片懒加载HTML
     */
    public static function lazyImage($src, $alt = '', $class = '', $placeholder = '/assets/images/placeholder.svg') {
        $class = $class ? $class . ' lazy-load' : 'lazy-load';
        
        return sprintf(
            '<img src="%s" data-src="%s" alt="%s" class="%s" loading="lazy">',
            htmlspecialchars($placeholder),
            htmlspecialchars($src),
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
    
    /**
     * 获取页面性能指标
     */
    public function getPerformanceMetrics() {
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();
        $memoryPeak = memory_get_peak_usage();
        
        return [
            'execution_time' => round(($endTime - $this->startTime) * 1000, 2), // 毫秒
            'memory_used' => round(($memoryEnd - $this->memoryStart) / 1024 / 1024, 2), // MB
            'memory_peak' => round($memoryPeak / 1024 / 1024, 2), // MB
            'queries_count' => isset($GLOBALS['db_queries']) ? $GLOBALS['db_queries'] : 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 输出性能调试信息
     */
    public function debugPerformance($enabled = false) {
        if (!$enabled || !isDev()) return;
        
        $metrics = $this->getPerformanceMetrics();
        
        echo "\n<!-- Performance Debug -->\n";
        echo "<!-- Execution Time: {$metrics['execution_time']}ms -->\n";
        echo "<!-- Memory Used: {$metrics['memory_used']}MB -->\n";
        echo "<!-- Memory Peak: {$metrics['memory_peak']}MB -->\n";
        echo "<!-- DB Queries: {$metrics['queries_count']} -->\n";
        echo "<!-- Generated: {$metrics['timestamp']} -->\n";
    }
    
    /**
     * 生成Web字体优化加载
     */
    public static function optimizedFontLoad($fontUrl, $fontDisplay = 'swap') {
        return sprintf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin><style>@font-face{font-display:%s;}</style>',
            htmlspecialchars($fontUrl),
            htmlspecialchars($fontDisplay)
        );
    }
    
    /**
     * 资源合并和压缩
     */
    public static function combineAssets($files, $type = 'css', $outputPath = null) {
        if (empty($files)) return '';
        
        $combined = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $lastModified = max($lastModified, filemtime($fullPath));
                
                if ($type === 'css') {
                    $combined .= self::compressCSS($content) . "\n";
                } else {
                    $combined .= self::compressJS($content) . "\n";
                }
            }
        }
        
        if ($outputPath) {
            $outputFile = $_SERVER['DOCUMENT_ROOT'] . $outputPath;
            file_put_contents($outputFile, $combined);
            touch($outputFile, $lastModified);
            return $outputPath . '?v=' . $lastModified;
        }
        
        return $combined;
    }
}

/**
 * 性能优化助手函数
 */
function perf() {
    return PerformanceOptimizer::getInstance();
}

function asset_url($path, $timestamp = true) {
    return PerformanceOptimizer::assetUrl($path, $timestamp);
}

function lazy_img($src, $alt = '', $class = '') {
    return PerformanceOptimizer::lazyImage($src, $alt, $class);
}

function compress_css($css) {
    return PerformanceOptimizer::compressCSS($css);
}

function compress_js($js) {
    return PerformanceOptimizer::compressJS($js);
}

/**
 * 初始化性能优化
 */
function init_performance_optimization() {
    // 启用Gzip压缩
    PerformanceOptimizer::enableGzipCompression();
    
    // 设置基本缓存头
    if (!isset($_SESSION) && strpos($_SERVER['REQUEST_URI'], '/admin/') === false) {
        PerformanceOptimizer::setCacheHeaders('dynamic', 1800); // 30分钟缓存
    }
    
    // 初始化性能监控
    PerformanceOptimizer::getInstance();
}