<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\Tests\Fixtures\FakePost;
use SocialDept\AtpOrm\Tests\Fixtures\FakePostData;

class RemoteCollectionTest extends TestCase
{
    private function makePost(string $text, string $rkey): FakePost
    {
        $post = new FakePost;
        $data = FakePostData::fromArray([
            'text' => $text,
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);
        $post->setRecord($data);
        $post->setUri("at://did:plc:abc/app.bsky.feed.post/{$rkey}");
        $post->setExists(true);

        return $post;
    }

    public function test_count(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('First', 'rk1'),
            $this->makePost('Second', 'rk2'),
        ]);

        $this->assertCount(2, $collection);
    }

    public function test_first_and_last(): void
    {
        $post1 = $this->makePost('First', 'rk1');
        $post2 = $this->makePost('Last', 'rk2');
        $collection = new RemoteCollection([$post1, $post2]);

        $this->assertSame($post1, $collection->first());
        $this->assertSame($post2, $collection->last());
    }

    public function test_is_empty(): void
    {
        $empty = new RemoteCollection([]);
        $notEmpty = new RemoteCollection([$this->makePost('Test', 'rk1')]);

        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($empty->isNotEmpty());
        $this->assertFalse($notEmpty->isEmpty());
        $this->assertTrue($notEmpty->isNotEmpty());
    }

    public function test_cursor(): void
    {
        $collection = new RemoteCollection([], 'cursor_abc');

        $this->assertSame('cursor_abc', $collection->cursor());
        $this->assertTrue($collection->hasMorePages());
    }

    public function test_no_cursor(): void
    {
        $collection = new RemoteCollection([]);

        $this->assertNull($collection->cursor());
        $this->assertFalse($collection->hasMorePages());
    }

    public function test_to_array(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('Hello', 'rk1'),
            $this->makePost('World', 'rk2'),
        ]);

        $array = $collection->toArray();
        $this->assertCount(2, $array);
        $this->assertSame('Hello', $array[0]['text']);
        $this->assertSame('World', $array[1]['text']);
    }

    public function test_map(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('Hello', 'rk1'),
            $this->makePost('World', 'rk2'),
        ]);

        $texts = $collection->map(fn (FakePost $post) => $post->text);

        $this->assertSame(['Hello', 'World'], $texts->all());
    }

    public function test_filter(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('Hello', 'rk1'),
            $this->makePost('World', 'rk2'),
        ]);

        $filtered = $collection->filter(fn (FakePost $post) => $post->text === 'Hello');

        $this->assertCount(1, $filtered);
        $this->assertSame('Hello', $filtered->first()->text);
    }

    public function test_each(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('A', 'rk1'),
            $this->makePost('B', 'rk2'),
        ]);

        $result = [];
        $collection->each(function (FakePost $post) use (&$result) {
            $result[] = $post->text;
        });

        $this->assertSame(['A', 'B'], $result);
    }

    public function test_pluck(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('Hello', 'rk1'),
            $this->makePost('World', 'rk2'),
        ]);

        $texts = $collection->pluck('text');
        $this->assertSame(['Hello', 'World'], $texts->all());
    }

    public function test_iterable(): void
    {
        $posts = [
            $this->makePost('A', 'rk1'),
            $this->makePost('B', 'rk2'),
        ];
        $collection = new RemoteCollection($posts);

        $result = [];
        foreach ($collection as $post) {
            $result[] = $post->text;
        }

        $this->assertSame(['A', 'B'], $result);
    }

    public function test_all(): void
    {
        $posts = [
            $this->makePost('A', 'rk1'),
            $this->makePost('B', 'rk2'),
        ];
        $collection = new RemoteCollection($posts);

        $this->assertSame($posts, $collection->all());
    }

    public function test_to_collection(): void
    {
        $collection = new RemoteCollection([
            $this->makePost('Test', 'rk1'),
        ]);

        $laravelCollection = $collection->toCollection();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $laravelCollection);
        $this->assertCount(1, $laravelCollection);
    }

    public function test_next_page_returns_null_without_context(): void
    {
        $collection = new RemoteCollection([], 'cursor');

        $this->assertNull($collection->nextPage());
    }
}
