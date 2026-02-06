<?php

namespace SocialDept\AtpOrm\Signals;

use SocialDept\AtpOrm\Cache\CacheKeyGenerator;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpSignals\Events\SignalEvent;
use SocialDept\AtpSignals\Signals\Signal;

class CacheInvalidationSignal extends Signal
{
    public function __construct(
        protected CacheProvider $cache,
        protected CacheKeyGenerator $keyGenerator,
    ) {
    }

    public function eventTypes(): array
    {
        return ['commit'];
    }

    public function collections(): ?array
    {
        return config('atp-orm.cache.invalidation.collections');
    }

    public function operations(): ?array
    {
        return ['create', 'update', 'delete'];
    }

    public function dids(): ?array
    {
        return config('atp-orm.cache.invalidation.dids');
    }

    public function handle(SignalEvent $event): void
    {
        if (! $event->commit) {
            return;
        }

        $commit = $event->commit;
        $did = $event->did;
        $collection = $commit->collection;
        $rkey = $commit->rkey;

        // Forget the specific record
        if ($rkey) {
            $this->cache->forget(
                $this->keyGenerator->forRecord($collection, $did, $rkey)
            );
        }

        // Flush all list/repo caches for this scope
        $this->cache->flush(
            $this->keyGenerator->scopePrefix($collection, $did)
        );
    }
}
