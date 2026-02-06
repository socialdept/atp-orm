<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use Mockery;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\CreateRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\GetRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\ListRecordsResponse;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;
use SocialDept\AtpOrm\Exceptions\RecordNotFoundException;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\Query\Builder;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpResolver\Facades\Resolver;

class BuilderTest extends TestCase
{
    private ArrayCacheProvider $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ArrayCacheProvider;
        $this->app->instance(CacheProvider::class, $this->cache);

        // Clear static client cache between tests
        $reflection = new \ReflectionClass(Builder::class);
        $prop = $reflection->getProperty('clientCache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a fake client bound to the atp-client container key.
     */
    private function mockAtpFacade(): Mockery\MockInterface
    {
        $repoClient = Mockery::mock();

        $fakeAtproto = new \stdClass;
        $fakeAtproto->repo = $repoClient;

        $fakeClient = new \stdClass;
        $fakeClient->atproto = $fakeAtproto;

        $manager = new class($fakeClient) {
            private object $client;

            public function __construct(object $client)
            {
                $this->client = $client;
            }

            public function as(string $actor): object
            {
                return $this->client;
            }

            public function public(?string $service = null): object
            {
                return $this->client;
            }
        };

        $this->app->instance('atp-client', $manager);

        return $repoClient;
    }

    public function test_get_returns_remote_collection(): void
    {
        Resolver::shouldReceive('handleToDid')->never();
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('listRecords')
            ->once()
            ->andReturn(ListRecordsResponse::fromArray([
                'records' => [
                    [
                        'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                        'cid' => 'cid1',
                        'value' => ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
                    ],
                ],
                'cursor' => null,
            ]));

        $result = FakePost::for('did:plc:abc')->get();

        $this->assertInstanceOf(RemoteCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('Hello', $result->first()->text);
    }

    public function test_find_returns_single_record(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('getRecord')
            ->once()
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'cid1',
                'value' => ['text' => 'Found', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $result = FakePost::for('did:plc:abc')->find('rk1');

        $this->assertInstanceOf(FakePost::class, $result);
        $this->assertSame('Found', $result->text);
        $this->assertSame('rk1', $result->getRkey());
    }

    public function test_find_returns_null_on_error(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('getRecord')
            ->once()
            ->andThrow(new \RuntimeException('Not found'));

        $result = FakePost::for('did:plc:abc')->find('nonexistent');

        $this->assertNull($result);
    }

    public function test_find_or_fail_throws(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('getRecord')
            ->once()
            ->andThrow(new \RuntimeException('Not found'));

        $this->expectException(RecordNotFoundException::class);
        FakePost::for('did:plc:abc')->findOrFail('nonexistent');
    }

    public function test_find_caches_result(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('getRecord')
            ->once() // Only called once, second call hits cache
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'cid1',
                'value' => ['text' => 'Cached', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $result1 = FakePost::for('did:plc:abc')->find('rk1');
        $result2 = FakePost::for('did:plc:abc')->find('rk1');

        $this->assertSame('Cached', $result1->text);
        $this->assertSame('Cached', $result2->text);
    }

    public function test_fresh_bypasses_cache(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:abc')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('getRecord')
            ->twice() // Called both times because fresh bypasses cache
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'cid1',
                'value' => ['text' => 'Fresh', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        FakePost::for('did:plc:abc')->find('rk1');
        $result = FakePost::for('did:plc:abc')->fresh()->find('rk1');

        $this->assertSame('Fresh', $result->text);
    }

    public function test_create_requires_auth(): void
    {
        $this->expectException(ReadOnlyException::class);

        FakePost::for('did:plc:abc')->create(['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z']);
    }

    public function test_create_with_auth(): void
    {
        $repoClient = $this->mockAtpFacade();

        $repoClient->shouldReceive('createRecord')
            ->once()
            ->andReturn(CreateRecordResponse::fromArray([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/newrkey',
                'cid' => 'newcid',
            ]));

        $result = FakePost::as('did:plc:abc')->create([
            'text' => 'New post',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertInstanceOf(FakePost::class, $result);
        $this->assertTrue($result->exists());
        $this->assertSame('newrkey', $result->getRkey());
    }

    public function test_handle_resolution(): void
    {
        Resolver::shouldReceive('handleToDid')
            ->with('alice.bsky.social')
            ->andReturn('did:plc:alice');

        Resolver::shouldReceive('resolvePds')
            ->with('did:plc:alice')
            ->andReturn('https://pds.example.com');

        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('listRecords')
            ->once()
            ->andReturn(ListRecordsResponse::fromArray([
                'records' => [],
            ]));

        $result = FakePost::for('alice.bsky.social')->get();

        $this->assertCount(0, $result);
    }

    public function test_limit(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->andReturn('https://pds.example.com');

        $capturedLimit = null;
        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('listRecords')
            ->once()
            ->andReturnUsing(function () use (&$capturedLimit) {
                $args = func_get_args();
                $capturedLimit = $args[2] ?? null;

                return ListRecordsResponse::fromArray(['records' => []]);
            });

        FakePost::for('did:plc:abc')->limit(10)->get();

        $this->assertSame(10, $capturedLimit);
    }

    public function test_limit_capped_at_max(): void
    {
        Resolver::shouldReceive('resolvePds')
            ->andReturn('https://pds.example.com');

        $capturedLimit = null;
        $repoClient = $this->mockAtpFacade();
        $repoClient->shouldReceive('listRecords')
            ->once()
            ->andReturnUsing(function () use (&$capturedLimit) {
                $args = func_get_args();
                $capturedLimit = $args[2] ?? null;

                return ListRecordsResponse::fromArray(['records' => []]);
            });

        FakePost::for('did:plc:abc')->limit(500)->get();

        $this->assertSame(100, $capturedLimit);
    }

    public function test_invalidate_flushes_cache(): void
    {
        // Pre-populate cache
        $this->cache->put('atp-orm:app.bsky.feed.post:did:plc:abc:rk1', 'cached', 60);
        $this->cache->put('atp-orm:app.bsky.feed.post:did:plc:abc:list:hash', 'cached', 60);
        $this->cache->put('atp-orm:other.collection:did:plc:abc:rk1', 'kept', 60);

        FakePost::for('did:plc:abc')->invalidate();

        $this->assertNull($this->cache->get('atp-orm:app.bsky.feed.post:did:plc:abc:rk1'));
        $this->assertNull($this->cache->get('atp-orm:app.bsky.feed.post:did:plc:abc:list:hash'));
        $this->assertSame('kept', $this->cache->get('atp-orm:other.collection:did:plc:abc:rk1'));
    }
}
