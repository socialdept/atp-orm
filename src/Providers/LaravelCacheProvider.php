<?php

namespace SocialDept\AtpOrm\Providers;

use Illuminate\Support\Facades\Cache;
use SocialDept\AtpOrm\Contracts\CacheProvider;

class LaravelCacheProvider implements CacheProvider
{
    protected string $trackingKey = 'atp-orm:tracked-keys';

    protected function store(): \Illuminate\Contracts\Cache\Repository
    {
        $storeName = config('atp-orm.cache.store');

        return Cache::store($storeName);
    }

    public function get(string $key): mixed
    {
        return $this->store()->get($key);
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $this->store()->put($key, $value, $ttl);
        $this->trackKey($key);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function forget(string $key): void
    {
        $this->store()->forget($key);
        $this->untrackKey($key);
    }

    public function flush(string $prefix): void
    {
        $trackedKeys = $this->store()->get($this->trackingKey, []);

        foreach ($trackedKeys as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->store()->forget($key);
            }
        }

        $remaining = array_filter($trackedKeys, fn (string $key) => ! str_starts_with($key, $prefix));
        $this->store()->put($this->trackingKey, array_values($remaining), 86400);
    }

    protected function trackKey(string $key): void
    {
        $tracked = $this->store()->get($this->trackingKey, []);

        if (! in_array($key, $tracked)) {
            $tracked[] = $key;
            $this->store()->put($this->trackingKey, $tracked, 86400);
        }
    }

    protected function untrackKey(string $key): void
    {
        $tracked = $this->store()->get($this->trackingKey, []);
        $tracked = array_values(array_filter($tracked, fn (string $k) => $k !== $key));
        $this->store()->put($this->trackingKey, $tracked, 86400);
    }
}
