<?php
/**
 * Simple Cache Implementation
 * Provides file-based caching for API responses and database queries
 */

class SimpleCache
{
    private static $cacheDir;
    private static $defaultTTL = 300; // 5 minutes

    public static function init()
    {
        self::$cacheDir = dirname(__DIR__) . '/cache';
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Get cached value
     */
    public static function get($key)
    {
        self::init();
        $filename = self::$cacheDir . '/' . md5($key) . '.cache';

        if (!file_exists($filename)) {
            return null;
        }

        $data = @file_get_contents($filename);
        if ($data === false) {
            return null;
        }

        $cache = @unserialize($data);
        if ($cache === false) {
            return null;
        }

        // Check if expired
        if (isset($cache['expires']) && $cache['expires'] < time()) {
            @unlink($filename);
            return null;
        }

        return $cache['value'] ?? null;
    }

    /**
     * Set cached value
     */
    public static function set($key, $value, $ttl = null)
    {
        self::init();
        $ttl = $ttl ?? self::$defaultTTL;
        $filename = self::$cacheDir . '/' . md5($key) . '.cache';

        $cache = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        return @file_put_contents($filename, serialize($cache)) !== false;
    }

    /**
     * Delete cached value
     */
    public static function delete($key)
    {
        self::init();
        $filename = self::$cacheDir . '/' . md5($key) . '.cache';
        return @unlink($filename);
    }

    /**
     * Clear all cache
     */
    public static function clear()
    {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    /**
     * Remember - Get from cache or execute callback and cache result
     */
    public static function remember($key, $callback, $ttl = null)
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }
}
