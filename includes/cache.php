<?php
/**
 * 缓存管理类
 * 支持文件缓存和Redis缓存
 */

class CacheManager {
    private static $instance = null;
    private $cacheType;
    private $redis = null;
    private $cacheDir;
    
    private function __construct() {
        $this->cacheType = defined('CACHE_TYPE') ? CACHE_TYPE : 'file';
        $this->cacheDir = dirname(__DIR__) . '/cache/';
        
        // 确保缓存目录存在
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // 初始化Redis连接（如果可用）
        if ($this->cacheType === 'redis' && class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect(
                    defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
                    defined('REDIS_PORT') ? REDIS_PORT : 6379
                );
                if (defined('REDIS_PASSWORD') && REDIS_PASSWORD) {
                    $this->redis->auth(REDIS_PASSWORD);
                }
            } catch (Exception $e) {
                error_log("Redis连接失败: " . $e->getMessage());
                $this->cacheType = 'file'; // 降级到文件缓存
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 获取缓存
     */
    public function get($key) {
        $key = $this->sanitizeKey($key);
        
        if ($this->cacheType === 'redis' && $this->redis) {
            $data = $this->redis->get($key);
            return $data !== false ? json_decode($data, true) : null;
        } else {
            return $this->getFileCache($key);
        }
    }
    
    /**
     * 设置缓存
     */
    public function set($key, $value, $ttl = 3600) {
        $key = $this->sanitizeKey($key);
        
        if ($this->cacheType === 'redis' && $this->redis) {
            return $this->redis->setex($key, $ttl, json_encode($value));
        } else {
            return $this->setFileCache($key, $value, $ttl);
        }
    }
    
    /**
     * 删除缓存
     */
    public function delete($key) {
        $key = $this->sanitizeKey($key);
        
        if ($this->cacheType === 'redis' && $this->redis) {
            return $this->redis->del($key);
        } else {
            $file = $this->getCacheFilePath($key);
            return file_exists($file) ? unlink($file) : true;
        }
    }
    
    /**
     * 清除所有缓存
     */
    public function flush() {
        if ($this->cacheType === 'redis' && $this->redis) {
            return $this->redis->flushDB();
        } else {
            $files = glob($this->cacheDir . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            return true;
        }
    }
    
    /**
     * 获取或设置缓存（缓存不存在时执行回调）
     */
    public function remember($key, $callback, $ttl = 3600) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * 文件缓存实现
     */
    private function getFileCache($key) {
        $file = $this->getCacheFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || $data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    private function setFileCache($key, $value, $ttl) {
        $file = $this->getCacheFilePath($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($file, json_encode($data), LOCK_EX) !== false;
    }
    
    private function getCacheFilePath($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    private function sanitizeKey($key) {
        return preg_replace('/[^a-zA-Z0-9_\-:]/', '_', $key);
    }
    
    /**
     * 获取缓存统计信息
     */
    public function getStats() {
        if ($this->cacheType === 'redis' && $this->redis) {
            return $this->redis->info();
        } else {
            $files = glob($this->cacheDir . '*.cache');
            $totalSize = 0;
            $validFiles = 0;
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
                $data = json_decode(file_get_contents($file), true);
                if ($data && $data['expires'] >= time()) {
                    $validFiles++;
                }
            }
            
            return [
                'type' => 'file',
                'total_files' => count($files),
                'valid_files' => $validFiles,
                'total_size' => $totalSize,
                'cache_dir' => $this->cacheDir
            ];
        }
    }
}

/**
 * 缓存助手函数
 */
function cache_get($key) {
    return CacheManager::getInstance()->get($key);
}

function cache_set($key, $value, $ttl = 3600) {
    return CacheManager::getInstance()->set($key, $value, $ttl);
}

function cache_delete($key) {
    return CacheManager::getInstance()->delete($key);
}

function cache_remember($key, $callback, $ttl = 3600) {
    return CacheManager::getInstance()->remember($key, $callback, $ttl);
}

function cache_flush() {
    return CacheManager::getInstance()->flush();
}