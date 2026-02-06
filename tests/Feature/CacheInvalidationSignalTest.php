<?php

namespace SocialDept\AtpOrm\Tests\Feature;

use SocialDept\AtpOrm\Cache\CacheKeyGenerator;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\Signals\CacheInvalidationSignal;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSignals\Events\CommitEvent;
use SocialDept\AtpSignals\Events\SignalEvent;

class CacheInvalidationSignalTest extends TestCase
{
    private ArrayCacheProvider $cache;

    private CacheKeyGenerator $keyGen;

    private CacheInvalidationSignal $signal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ArrayCacheProvider;
        $this->keyGen = new CacheKeyGenerator('atp-orm');
        $this->signal = new CacheInvalidationSignal($this->cache, $this->keyGen);
    }

    public function test_event_types(): void
    {
        $this->assertSame(['commit'], $this->signal->eventTypes());
    }

    public function test_operations(): void
    {
        $this->assertSame(['create', 'update', 'delete'], $this->signal->operations());
    }

    public function test_handle_invalidates_record_cache(): void
    {
        // Pre-populate cache
        $recordKey = $this->keyGen->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rk1');
        $this->cache->put($recordKey, 'cached_record', 60);

        $listKey = $this->keyGen->forList('app.bsky.feed.post', 'did:plc:abc', ['limit' => 50]);
        $this->cache->put($listKey, 'cached_list', 60);

        // Keep a different collection's cache
        $otherKey = $this->keyGen->forRecord('app.bsky.actor.profile', 'did:plc:abc', 'self');
        $this->cache->put($otherKey, 'kept', 60);

        $event = new SignalEvent(
            did: 'did:plc:abc',
            timeUs: 1000000,
            kind: 'commit',
            commit: new CommitEvent(
                rev: 'rev123',
                operation: 'create',
                collection: 'app.bsky.feed.post',
                rkey: 'rk1',
            ),
        );

        $this->signal->handle($event);

        $this->assertNull($this->cache->get($recordKey));
        $this->assertNull($this->cache->get($listKey));
        $this->assertSame('kept', $this->cache->get($otherKey));
    }

    public function test_handle_skips_without_commit(): void
    {
        $recordKey = $this->keyGen->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rk1');
        $this->cache->put($recordKey, 'cached_record', 60);

        $event = new SignalEvent(
            did: 'did:plc:abc',
            timeUs: 1000000,
            kind: 'identity',
        );

        $this->signal->handle($event);

        // Cache should remain intact
        $this->assertSame('cached_record', $this->cache->get($recordKey));
    }

    public function test_handle_invalidates_on_delete(): void
    {
        $recordKey = $this->keyGen->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rk1');
        $this->cache->put($recordKey, 'cached_record', 60);

        $event = new SignalEvent(
            did: 'did:plc:abc',
            timeUs: 1000000,
            kind: 'commit',
            commit: new CommitEvent(
                rev: 'rev456',
                operation: 'delete',
                collection: 'app.bsky.feed.post',
                rkey: 'rk1',
            ),
        );

        $this->signal->handle($event);

        $this->assertNull($this->cache->get($recordKey));
    }
}
