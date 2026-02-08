<?php

namespace SocialDept\AtpOrm\Tests\Unit\Backlinks;

use Mockery;
use SocialDept\AtpOrm\Backlinks\BacklinkCollection;
use SocialDept\AtpOrm\Backlinks\BacklinkQuery;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\Data\GetBacklinksResponse;
use SocialDept\AtpSupport\Microcosm\Data\LinkSummary;

class BacklinkQueryTest extends TestCase
{
    private ArrayCacheProvider $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ArrayCacheProvider();
        $this->app->instance(CacheProvider::class, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockConstellation(): Mockery\MockInterface
    {
        $mock = Mockery::mock(ConstellationClient::class);
        $this->app->instance(ConstellationClient::class, $mock);

        return $mock;
    }

    public function test_source_with_separate_arguments(): void
    {
        $query = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri');

        $this->assertSame('app.bsky.feed.like', $query->getCollection());
        $this->assertSame('subject.uri', $query->getPath());
    }

    public function test_source_with_combined_format(): void
    {
        $query = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like:subject.uri');

        $this->assertSame('app.bsky.feed.like', $query->getCollection());
        $this->assertSame('subject.uri', $query->getPath());
    }

    public function test_get_returns_backlink_collection(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(function ($subject, $source, $dids, $limit, $reverse, $cursor) {
                return $subject === 'at://did:plc:abc/app.bsky.feed.post/rk1'
                    && $source === 'app.bsky.feed.like:subject.uri'
                    && $dids === null
                    && $limit === 16
                    && $reverse === false
                    && $cursor === null;
            })
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 2,
                'records' => [
                    ['did' => 'did:plc:user1', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk1'],
                    ['did' => 'did:plc:user2', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk2'],
                ],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri')
            ->get();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(2, $result->total());
    }

    public function test_count_returns_integer(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinksCount')
            ->once()
            ->with('at://did:plc:abc/app.bsky.feed.post/rk1', 'app.bsky.feed.like:subject.uri')
            ->andReturn(42);

        $count = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri')
            ->count();

        $this->assertSame(42, $count);
    }

    public function test_all_returns_link_summary(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getAllLinks')
            ->once()
            ->with('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->andReturn(LinkSummary::fromArray([
                'links' => [
                    'app.bsky.feed.like' => [
                        '.subject.uri' => ['records' => 10, 'distinct_dids' => 8],
                    ],
                ],
            ]));

        $summary = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')->all();

        $this->assertInstanceOf(LinkSummary::class, $summary);
        $this->assertSame(10, $summary->total());
    }

    public function test_likes_convenience_method(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source) => $source === 'app.bsky.feed.like:subject.uri')
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')->likes();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
    }

    public function test_quotes_convenience_method(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source) => $source === 'app.bsky.feed.post:embed.record.uri')
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')->quotes();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
    }

    public function test_replies_convenience_method(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source) => $source === 'app.bsky.feed.post:reply.parent.uri')
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')->replies();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
    }

    public function test_reposts_convenience_method(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source) => $source === 'app.bsky.feed.repost:subject.uri')
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')->reposts();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
    }

    public function test_followers_convenience_method(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source) => $source === 'app.bsky.graph.follow:subject')
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('did:plc:abc')->followers();

        $this->assertInstanceOf(BacklinkCollection::class, $result);
    }

    public function test_pagination_params(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(function ($subject, $source, $dids, $limit, $reverse, $cursor) {
                return $limit === 25
                    && $reverse === true
                    && $cursor === 'abc123';
            })
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri')
            ->limit(25)
            ->reverse()
            ->after('abc123')
            ->get();

        $this->assertCount(0, $result);
    }

    public function test_did_filter(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source, $dids) => $dids === ['did:plc:user1', 'did:plc:user2'])
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri')
            ->did(['did:plc:user1', 'did:plc:user2'])
            ->get();

        $this->assertCount(0, $result);
    }

    public function test_get_caches_result(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 1,
                'records' => [
                    ['did' => 'did:plc:user1', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk1'],
                ],
                'cursor' => null,
            ]));

        $query = fn () => BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri');

        $result1 = $query()->get();
        $result2 = $query()->get();

        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
    }

    public function test_fresh_bypasses_cache(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->twice()
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 1,
                'records' => [
                    ['did' => 'did:plc:user1', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk1'],
                ],
                'cursor' => null,
            ]));

        $query = fn () => BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri');

        $result1 = $query()->get();
        $result2 = $query()->fresh()->get();

        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
    }

    public function test_limit_capped_at_100(): void
    {
        $constellation = $this->mockConstellation();
        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(fn ($subject, $source, $dids, $limit) => $limit === 100)
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $result = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
            ->source('app.bsky.feed.like', 'subject.uri')
            ->limit(500)
            ->get();

        $this->assertCount(0, $result);
    }
}
