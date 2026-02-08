<?php

namespace SocialDept\AtpOrm\Tests\Unit\Backlinks;

use Mockery;
use SocialDept\AtpOrm\Backlinks\BacklinkCollection;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSupport\Microcosm\Data\BacklinkReference;
use SocialDept\AtpSupport\Microcosm\Data\GetRecordResponse;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;

class BacklinkCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(CacheProvider::class, new ArrayCacheProvider());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRefs(int $count): array
    {
        $refs = [];
        for ($i = 1; $i <= $count; $i++) {
            $refs[] = new BacklinkReference(
                did: "did:plc:user{$i}",
                collection: 'app.bsky.feed.like',
                rkey: "lk{$i}",
            );
        }

        return $refs;
    }

    public function test_count_and_iteration(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(3), null, 3);

        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->total());
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }
        $this->assertCount(3, $items);
    }

    public function test_first_and_last(): void
    {
        $refs = $this->makeRefs(3);
        $collection = new BacklinkCollection($refs);

        $this->assertSame('did:plc:user1', $collection->first()->did);
        $this->assertSame('did:plc:user3', $collection->last()->did);
    }

    public function test_cursor_and_pagination(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(2), 'cursor123', 10);

        $this->assertSame('cursor123', $collection->cursor());
        $this->assertTrue($collection->hasMorePages());

        $noCursor = new BacklinkCollection($this->makeRefs(2), null, 2);
        $this->assertFalse($noCursor->hasMorePages());
    }

    public function test_uris(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(2));

        $uris = $collection->uris();

        $this->assertSame('at://did:plc:user1/app.bsky.feed.like/lk1', $uris[0]);
        $this->assertSame('at://did:plc:user2/app.bsky.feed.like/lk2', $uris[1]);
    }

    public function test_dids(): void
    {
        $refs = [
            new BacklinkReference('did:plc:user1', 'app.bsky.feed.like', 'lk1'),
            new BacklinkReference('did:plc:user1', 'app.bsky.feed.like', 'lk2'),
            new BacklinkReference('did:plc:user2', 'app.bsky.feed.like', 'lk3'),
        ];
        $collection = new BacklinkCollection($refs);

        $dids = $collection->dids();

        $this->assertCount(2, $dids);
        $this->assertContains('did:plc:user1', $dids->all());
        $this->assertContains('did:plc:user2', $dids->all());
    }

    public function test_filter(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(3), null, 3);

        $filtered = $collection->filter(fn (BacklinkReference $ref) => $ref->did !== 'did:plc:user2');

        $this->assertCount(2, $filtered);
        $this->assertSame(3, $filtered->total());
    }

    public function test_map(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(2));

        $mapped = $collection->map(fn (BacklinkReference $ref) => $ref->did);

        $this->assertSame(['did:plc:user1', 'did:plc:user2'], $mapped->all());
    }

    public function test_to_array(): void
    {
        $collection = new BacklinkCollection($this->makeRefs(1));

        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertSame('did:plc:user1', $array[0]['did']);
        $this->assertSame('app.bsky.feed.like', $array[0]['collection']);
        $this->assertSame('lk1', $array[0]['rkey']);
        $this->assertSame('at://did:plc:user1/app.bsky.feed.like/lk1', $array[0]['uri']);
    }

    public function test_empty_collection(): void
    {
        $collection = new BacklinkCollection();

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
        $this->assertSame(0, $collection->total());
    }

    public function test_hydrate_fetches_via_slingshot(): void
    {
        $slingshot = Mockery::mock(SlingshotClient::class);
        $this->app->instance(SlingshotClient::class, $slingshot);

        $slingshot->shouldReceive('getRecord')
            ->once()
            ->with('did:plc:user1', 'app.bsky.feed.post', 'rk1')
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:user1/app.bsky.feed.post/rk1',
                'cid' => 'cid1',
                'value' => ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $refs = [new BacklinkReference('did:plc:user1', 'app.bsky.feed.post', 'rk1')];
        $collection = new BacklinkCollection($refs, null, 1);

        $hydrated = $collection->hydrate(FakePost::class);

        $this->assertInstanceOf(RemoteCollection::class, $hydrated);
        $this->assertCount(1, $hydrated);
        $this->assertSame('Hello', $hydrated->first()->text);
    }

    public function test_hydrate_skips_failed_records(): void
    {
        $slingshot = Mockery::mock(SlingshotClient::class);
        $this->app->instance(SlingshotClient::class, $slingshot);

        $slingshot->shouldReceive('getRecord')
            ->with('did:plc:user1', 'app.bsky.feed.post', 'rk1')
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:user1/app.bsky.feed.post/rk1',
                'cid' => 'cid1',
                'value' => ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $slingshot->shouldReceive('getRecord')
            ->with('did:plc:user2', 'app.bsky.feed.post', 'rk2')
            ->andThrow(new \RuntimeException('Not found'));

        $refs = [
            new BacklinkReference('did:plc:user1', 'app.bsky.feed.post', 'rk1'),
            new BacklinkReference('did:plc:user2', 'app.bsky.feed.post', 'rk2'),
        ];
        $collection = new BacklinkCollection($refs, null, 2);

        $hydrated = $collection->hydrate(FakePost::class);

        $this->assertCount(1, $hydrated);
    }

    public function test_hydrate_empty_collection(): void
    {
        $collection = new BacklinkCollection();

        $hydrated = $collection->hydrate(FakePost::class);

        $this->assertInstanceOf(RemoteCollection::class, $hydrated);
        $this->assertCount(0, $hydrated);
    }
}
