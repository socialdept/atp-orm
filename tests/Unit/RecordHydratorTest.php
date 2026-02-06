<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use SocialDept\AtpClient\Data\Responses\Atproto\Repo\ListRecordsResponse;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\Support\RecordHydrator;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\TestCase;

class RecordHydratorTest extends TestCase
{
    private RecordHydrator $hydrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hydrator = new RecordHydrator();
    }

    public function test_hydrate_one(): void
    {
        $record = $this->hydrator->hydrateOne(
            FakePost::class,
            ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
            'at://did:plc:abc/app.bsky.feed.post/rk1',
            'bafyreigz',
        );

        $this->assertInstanceOf(FakePost::class, $record);
        $this->assertSame('Hello', $record->text);
        $this->assertSame('did:plc:abc', $record->getDid());
        $this->assertSame('rk1', $record->getRkey());
        $this->assertSame('bafyreigz', $record->getCid());
        $this->assertTrue($record->exists());
    }

    public function test_hydrate_one_with_auth_did(): void
    {
        $record = $this->hydrator->hydrateOne(
            FakePost::class,
            ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
            'at://did:plc:abc/app.bsky.feed.post/rk1',
            null,
            'did:plc:auth',
        );

        $this->assertSame('did:plc:auth', $record->getAuthenticatedDid());
    }

    public function test_hydrate_many(): void
    {
        $response = ListRecordsResponse::fromArray([
            'records' => [
                [
                    'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                    'cid' => 'cid1',
                    'value' => ['text' => 'First', 'createdAt' => '2024-01-01T00:00:00Z'],
                ],
                [
                    'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk2',
                    'cid' => 'cid2',
                    'value' => ['text' => 'Second', 'createdAt' => '2024-01-02T00:00:00Z'],
                ],
            ],
            'cursor' => 'next_cursor',
        ]);

        $collection = $this->hydrator->hydrateMany(FakePost::class, $response);

        $this->assertInstanceOf(RemoteCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertSame('next_cursor', $collection->cursor());

        $first = $collection->first();
        $this->assertSame('First', $first->text);
        $this->assertSame('rk1', $first->getRkey());
    }

    public function test_hydrate_from_repo(): void
    {
        $records = [
            ['rkey' => 'rk1', 'cid' => 'cid1', 'value' => ['text' => 'Post 1', 'createdAt' => '2024-01-01T00:00:00Z']],
            ['rkey' => 'rk2', 'cid' => 'cid2', 'value' => ['text' => 'Post 2', 'createdAt' => '2024-01-02T00:00:00Z']],
        ];

        $collection = $this->hydrator->hydrateFromRepo(
            FakePost::class,
            $records,
            'did:plc:abc',
        );

        $this->assertInstanceOf(RemoteCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertNull($collection->cursor());

        $first = $collection->first();
        $this->assertSame('Post 1', $first->text);
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rk1', $first->getUri());
    }

    public function test_hydrate_many_empty(): void
    {
        $response = ListRecordsResponse::fromArray([
            'records' => [],
        ]);

        $collection = $this->hydrator->hydrateMany(FakePost::class, $response);

        $this->assertCount(0, $collection);
        $this->assertNull($collection->cursor());
    }
}
