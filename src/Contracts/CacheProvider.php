<?php

namespace SocialDept\AtpOrm\Contracts;

interface CacheProvider
{
    /**
     * Get a cached value by key.
     */
    public function get(string $key): mixed;

    /**
     * Store a value with a TTL in seconds.
     */
    public function put(string $key, mixed $value, int $ttl): void;

    /**
     * Check if a key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove a specific key.
     */
    public function forget(string $key): void;

    /**
     * Remove all keys matching a scope prefix (for bulk invalidation).
     */
    public function flush(string $prefix): void;
}
