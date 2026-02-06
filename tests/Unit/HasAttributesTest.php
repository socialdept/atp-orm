<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\Fixtures\FakePostData;

class HasAttributesTest extends TestCase
{
    public function test_get_attribute_from_record(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello world',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertSame('Hello world', $post->getAttribute('text'));
        $this->assertSame('2024-01-01T00:00:00Z', $post->getAttribute('createdAt'));
    }

    public function test_set_attribute_marks_dirty(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertFalse($post->isDirty());

        $post->setAttribute('text', 'Updated');

        $this->assertTrue($post->isDirty());
        $this->assertTrue($post->isDirty('text'));
        $this->assertFalse($post->isDirty('createdAt'));
    }

    public function test_dirty_overrides_original(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Original',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $post->setAttribute('text', 'Dirty');

        $this->assertSame('Dirty', $post->getAttribute('text'));
        $this->assertSame('Original', $post->getOriginal('text'));
    }

    public function test_get_dirty_returns_only_changed(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $post->setAttribute('text', 'Changed');

        $this->assertSame(['text' => 'Changed'], $post->getDirty());
    }

    public function test_get_merged_attributes(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $post->setAttribute('text', 'Updated');

        $merged = $post->getMergedAttributes();
        $this->assertSame('Updated', $merged['text']);
        $this->assertSame('2024-01-01T00:00:00Z', $merged['createdAt']);
    }

    public function test_sync_original_resets_dirty(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $post->setAttribute('text', 'Changed');
        $this->assertTrue($post->isDirty());

        $post->syncOriginal();
        $this->assertFalse($post->isDirty());
    }

    public function test_magic_get_and_set(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertSame('Hello', $post->text);

        $post->text = 'Changed via magic';
        $this->assertSame('Changed via magic', $post->text);
    }

    public function test_magic_isset(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertTrue(isset($post->text));
        $this->assertFalse(isset($post->nonexistent));
    }

    public function test_is_clean(): void
    {
        $post = new FakePost();
        $data = FakePostData::fromArray([
            'text' => 'Hello',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);

        $this->assertTrue($post->isClean());
        $this->assertTrue($post->isClean('text'));

        $post->text = 'Changed';

        $this->assertFalse($post->isClean());
        $this->assertFalse($post->isClean('text'));
        $this->assertTrue($post->isClean('createdAt'));
    }
}
