<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Cache\CacheKeyGenerator;

class CacheKeyGeneratorTest extends TestCase
{
    private CacheKeyGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CacheKeyGenerator('atp-orm');
    }

    public function test_for_record(): void
    {
        $key = $this->generator->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rkey123');

        $this->assertSame('atp-orm:app.bsky.feed.post:did:plc:abc:rkey123', $key);
    }

    public function test_for_list(): void
    {
        $params = ['limit' => 50, 'cursor' => null, 'reverse' => false];
        $key = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', $params);

        $this->assertStringStartsWith('atp-orm:app.bsky.feed.post:did:plc:abc:list:', $key);
    }

    public function test_for_list_different_params_different_keys(): void
    {
        $key1 = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', ['limit' => 25]);
        $key2 = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', ['limit' => 50]);

        $this->assertNotSame($key1, $key2);
    }

    public function test_for_list_same_params_same_key(): void
    {
        $params = ['limit' => 50, 'cursor' => 'abc'];
        $key1 = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', $params);
        $key2 = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', $params);

        $this->assertSame($key1, $key2);
    }

    public function test_for_repo(): void
    {
        $key = $this->generator->forRepo('app.bsky.feed.post', 'did:plc:abc');

        $this->assertSame('atp-orm:app.bsky.feed.post:did:plc:abc:repo', $key);
    }

    public function test_scope_prefix(): void
    {
        $prefix = $this->generator->scopePrefix('app.bsky.feed.post', 'did:plc:abc');

        $this->assertSame('atp-orm:app.bsky.feed.post:did:plc:abc:', $prefix);
    }

    public function test_scope_prefix_matches_record_key(): void
    {
        $prefix = $this->generator->scopePrefix('app.bsky.feed.post', 'did:plc:abc');
        $recordKey = $this->generator->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rkey1');

        $this->assertTrue(str_starts_with($recordKey, $prefix));
    }

    public function test_scope_prefix_matches_list_key(): void
    {
        $prefix = $this->generator->scopePrefix('app.bsky.feed.post', 'did:plc:abc');
        $listKey = $this->generator->forList('app.bsky.feed.post', 'did:plc:abc', ['limit' => 50]);

        $this->assertTrue(str_starts_with($listKey, $prefix));
    }

    public function test_custom_prefix(): void
    {
        $generator = new CacheKeyGenerator('custom');
        $key = $generator->forRecord('app.bsky.feed.post', 'did:plc:abc', 'rkey1');

        $this->assertStringStartsWith('custom:', $key);
    }
}
