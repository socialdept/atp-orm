<?php

namespace SocialDept\AtpOrm\Tests\Feature;

use Mockery;
use SocialDept\AtpOrm\Backlinks\BacklinkCollection;
use SocialDept\AtpOrm\Backlinks\BacklinkQuery;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\Data\GetBacklinksResponse;

class BacklinksIntegrationTest extends TestCase
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

    public function test_remote_record_backlinks_method(): void
    {
        $post = new FakePost();
        $post->setUri('at://did:plc:abc/app.bsky.feed.post/rk1');

        $query = $post->backlinks();

        $this->assertInstanceOf(BacklinkQuery::class, $query);
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rk1', $query->getSubject());
    }

    public function test_backlinks_likes_on_record(): void
    {
        $constellation = Mockery::mock(ConstellationClient::class);
        $this->app->instance(ConstellationClient::class, $constellation);

        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(function ($subject, $source) {
                return $subject === 'at://did:plc:abc/app.bsky.feed.post/rk1'
                    && $source === 'app.bsky.feed.like:subject.uri';
            })
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 5,
                'records' => [
                    ['did' => 'did:plc:user1', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk1'],
                    ['did' => 'did:plc:user2', 'collection' => 'app.bsky.feed.like', 'rkey' => 'lk2'],
                ],
                'cursor' => 'next_cursor',
            ]));

        $post = new FakePost();
        $post->setUri('at://did:plc:abc/app.bsky.feed.post/rk1');

        $likes = $post->backlinks()->likes();

        $this->assertInstanceOf(BacklinkCollection::class, $likes);
        $this->assertCount(2, $likes);
        $this->assertSame(5, $likes->total());
        $this->assertTrue($likes->hasMorePages());
        $this->assertSame('next_cursor', $likes->cursor());
    }

    public function test_backlinks_chained_query(): void
    {
        $constellation = Mockery::mock(ConstellationClient::class);
        $this->app->instance(ConstellationClient::class, $constellation);

        $constellation->shouldReceive('getBacklinks')
            ->once()
            ->withArgs(function ($subject, $source, $dids, $limit, $reverse) {
                return $source === 'app.bsky.feed.post:embed.record.uri'
                    && $limit === 10
                    && $reverse === true;
            })
            ->andReturn(GetBacklinksResponse::fromArray([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ]));

        $post = new FakePost();
        $post->setUri('at://did:plc:abc/app.bsky.feed.post/rk1');

        $quotes = $post->backlinks()
            ->source('app.bsky.feed.post', 'embed.record.uri')
            ->limit(10)
            ->reverse()
            ->get();

        $this->assertInstanceOf(BacklinkCollection::class, $quotes);
        $this->assertCount(0, $quotes);
    }
}
