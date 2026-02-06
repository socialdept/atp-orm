<?php

namespace SocialDept\AtpOrm\Cache;

class CacheKeyGenerator
{
    protected string $prefix;

    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? config('atp-orm.cache.prefix', 'atp-orm');
    }

    public function forRecord(string $collection, string $did, string $rkey): string
    {
        return "{$this->prefix}:{$collection}:{$did}:{$rkey}";
    }

    public function forList(string $collection, string $did, array $params): string
    {
        $hash = md5(serialize($params));

        return "{$this->prefix}:{$collection}:{$did}:list:{$hash}";
    }

    public function forRepo(string $collection, string $did): string
    {
        return "{$this->prefix}:{$collection}:{$did}:repo";
    }

    public function scopePrefix(string $collection, string $did): string
    {
        return "{$this->prefix}:{$collection}:{$did}:";
    }
}
