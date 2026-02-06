<?php

namespace SocialDept\AtpOrm\Providers;

use SocialDept\AtpOrm\Contracts\CacheProvider;

class FileCacheProvider implements CacheProvider
{
    protected string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? config('atp-orm.cache.file_path', storage_path('app/atp-orm-cache'));

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function get(string $key): mixed
    {
        $path = $this->path($key);

        if (! file_exists($path)) {
            return null;
        }

        $data = unserialize(file_get_contents($path));

        if ($data['expires_at'] < time()) {
            unlink($path);

            return null;
        }

        return $data['value'];
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'key' => $key,
        ];

        file_put_contents($this->path($key), serialize($data));
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): void
    {
        $path = $this->path($key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function flush(string $prefix): void
    {
        $files = glob($this->storagePath.'/*.cache');

        if (! $files) {
            return;
        }

        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));

            if (isset($data['key']) && str_starts_with($data['key'], $prefix)) {
                unlink($file);
            }
        }
    }

    protected function path(string $key): string
    {
        return $this->storagePath.'/'.hash('sha256', $key).'.cache';
    }
}
