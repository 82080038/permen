<?php
declare(strict_types=1);

namespace App\Cache;

use Redis;
use RedisException;

/**
 * Redis Cache Handler
 * 
 * Provides caching functionality using Redis for improved performance.
 * Supports key-value storage with TTL (time-to-live).
 */
class Cache
{
    private static ?Redis $redis = null;
    private static string $prefix = 'permen:';
    private static int $defaultTTL = 3600; // 1 hour default

    /**
     * Initialize Redis connection
     */
    private static function connect(): void
    {
        if (self::$redis !== null) {
            return;
        }

        try {
            self::$redis = new Redis();
            $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
            $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
            $password = $_ENV['REDIS_PASSWORD'] ?? null;
            $database = (int)($_ENV['REDIS_DATABASE'] ?? 0);

            self::$redis->connect($host, $port, 2);
            
            if ($password) {
                self::$redis->auth($password);
            }
            
            if ($database > 0) {
                self::$redis->select($database);
            }
        } catch (RedisException $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            self::$redis = null;
        }
    }

    /**
     * Check if Redis is available
     */
    public static function isAvailable(): bool
    {
        self::connect();
        return self::$redis !== null && self::$redis->ping() === '+PONG';
    }

    /**
     * Get value from cache
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public static function get(string $key)
    {
        if (!self::isAvailable()) {
            return null;
        }

        $value = self::$redis->get(self::$prefix . $key);
        
        if ($value === false) {
            return null;
        }

        return json_decode($value, true);
    }

    /**
     * Set value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null for default)
     * @return bool Success status
     */
    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        $ttl = $ttl ?? self::$defaultTTL;
        $serialized = json_encode($value);
        
        return self::$redis->setex(self::$prefix . $key, $ttl, $serialized);
    }

    /**
     * Delete value from cache
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete(string $key): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        return self::$redis->del(self::$prefix . $key) > 0;
    }

    /**
     * Clear all cache entries with prefix
     * 
     * @return bool Success status
     */
    public static function clear(): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        $keys = self::$redis->keys(self::$prefix . '*');
        
        if (empty($keys)) {
            return true;
        }

        return self::$redis->del($keys) > 0;
    }

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        return self::$redis->exists(self::$prefix . $key) > 0;
    }

    /**
     * Remember value - get from cache or compute and store
     * 
     * @param string $key Cache key
     * @param callable $callback Function to compute value if not cached
     * @param int|null $ttl Time to live in seconds
     * @return mixed Cached or computed value
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Increment a numeric value in cache
     * 
     * @param string $key Cache key
     * @param int $by Amount to increment
     * @return int|false New value or false on failure
     */
    public static function increment(string $key, int $by = 1)
    {
        if (!self::isAvailable()) {
            return false;
        }

        return self::$redis->incrBy(self::$prefix . $key, $by);
    }

    /**
     * Decrement a numeric value in cache
     * 
     * @param string $key Cache key
     * @param int $by Amount to decrement
     * @return int|false New value or false on failure
     */
    public static function decrement(string $key, int $by = 1)
    {
        if (!self::isAvailable()) {
            return false;
        }

        return self::$redis->decrBy(self::$prefix . $key, $by);
    }

    /**
     * Get multiple values at once
     * 
     * @param array $keys Array of cache keys
     * @return array Associative array of key => value
     */
    public static function getMultiple(array $keys): array
    {
        if (!self::isAvailable() || empty($keys)) {
            return [];
        }

        $prefixedKeys = array_map(fn($k) => self::$prefix . $k, $keys);
        $values = self::$redis->mget($prefixedKeys);
        
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] !== false ? json_decode($values[$i], true) : null;
        }
        
        return $result;
    }

    /**
     * Set multiple values at once
     * 
     * @param array $items Associative array of key => value
     * @param int|null $ttl Time to live in seconds
     * @return bool Success status
     */
    public static function setMultiple(array $items, ?int $ttl = null): bool
    {
        if (!self::isAvailable() || empty($items)) {
            return false;
        }

        $prefixedItems = [];
        foreach ($items as $key => $value) {
            $prefixedItems[self::$prefix . $key] = json_encode($value);
        }

        $result = self::$redis->mset($prefixedItems);
        
        if ($ttl !== null) {
            foreach (array_keys($items) as $key) {
                self::$redis->expire(self::$prefix . $key, $ttl);
            }
        }
        
        return $result;
    }
}
