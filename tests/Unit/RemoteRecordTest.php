<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\Fixtures\FakePostData;

class RemoteRecordTest extends TestCase
{
    public function test_collection_and_record_class(): void
    {
        $post = new FakePost();

        $this->assertSame('app.bsky.feed.post', $post->getCollection());
        $this->assertSame(FakePostData::class, $post->getRecordClass());
    }

    public function test_set_and_get_did(): void
    {
        $post = new FakePost();
        $post->setDid('did:plc:abc');

        $this->assertSame('did:plc:abc', $post->getDid());
    }

    public function test_set_uri_extracts_did_and_rkey(): void
    {
        $post = new FakePost();
        $post->setUri('at://did:plc:abc/app.bsky.feed.post/rkey123');

        $this->assertSame('did:plc:abc', $post->getDid());
        $this->assertSame('rkey123', $post->getRkey());
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rkey123', $post->getUri());
    }

    public function test_exists_defaults_to_false(): void
    {
        $post = new FakePost();

        $this->assertFalse($post->exists());
    }

    public function test_set_exists(): void
    {
        $post = new FakePost();
        $post->setExists(true);

        $this->assertTrue($post->exists());
    }

    public function test_set_record(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertSame('Hello', $post->text);
        $this->assertFalse($post->isDirty());
    }

    public function test_to_dto(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $dto = $post->toDto();
        $this->assertInstanceOf(FakePostData::class, $dto);
        $this->assertSame('Hello', $dto->text);
    }

    public function test_to_dto_with_dirty(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);
        $post->text = 'Updated';

        $dto = $post->toDto();
        $this->assertInstanceOf(FakePostData::class, $dto);
        $this->assertSame('Updated', $dto->text);
    }

    public function test_to_array(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $array = $post->toArray();
        $this->assertSame('Hello', $array['text']);
        $this->assertSame('2024-01-01T00:00:00Z', $array['createdAt']);
    }

    public function test_to_json(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $json = $post->toJson();
        $decoded = json_decode($json, true);
        $this->assertSame('Hello', $decoded['text']);
    }

    public function test_array_access(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertSame('Hello', $post['text']);
        $this->assertTrue(isset($post['text']));
        $this->assertFalse(isset($post['nonexistent']));

        $post['text'] = 'Changed';
        $this->assertSame('Changed', $post['text']);

        unset($post['text']);
        $this->assertSame('Hello', $post['text']); // Falls back to original
    }

    public function test_json_serializable(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $json = json_encode($post);
        $decoded = json_decode($json, true);
        $this->assertSame('Hello', $decoded['text']);
    }

    public function test_save_without_auth_throws(): void
    {
        $post = new FakePost();

        $this->expectException(ReadOnlyException::class);
        $post->save();
    }

    public function test_delete_without_auth_throws(): void
    {
        $post = new FakePost();

        $this->expectException(ReadOnlyException::class);
        $post->delete();
    }

    public function test_delete_nonexistent_returns_false(): void
    {
        $post = new FakePost();
        $post->setAuthenticatedDid('did:plc:abc');

        $this->assertFalse($post->delete());
    }

    public function test_cache_ttl(): void
    {
        $post = new FakePost();

        $this->assertSame(300, $post->getCacheTtl());
    }

    public function test_set_cid(): void
    {
        $post = new FakePost();
        $post->setCid('bafyreigz');

        $this->assertSame('bafyreigz', $post->getCid());
    }

    public function test_authenticated_did(): void
    {
        $post = new FakePost();
        $this->assertNull($post->getAuthenticatedDid());

        $post->setAuthenticatedDid('did:plc:auth');
        $this->assertSame('did:plc:auth', $post->getAuthenticatedDid());
    }
}
