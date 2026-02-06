<?php

namespace SocialDept\AtpOrm\Providers;

use SocialDept\AtpOrm\Contracts\CacheProvider;

class ArrayCacheProvider implements CacheProvider
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    protected array $store = [];

    public function get(string $key): mixed
    {
        if (! isset($this->store[$key])) {
            return null;
        }

        if ($this->store[$key]['expires_at'] < time()) {
            unset($this->store[$key]);

            return null;
        }

        return $this->store[$key]['value'];
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $this->store[$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }

    public function flush(string $prefix): void
    {
        foreach (array_keys($this->store) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->store[$key]);
            }
        }
    }

    /**
     * Get the raw store for testing/inspection.
     */
    public function getStore(): array
    {
        return $this->store;
    }
}
