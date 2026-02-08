<?php

namespace SocialDept\AtpOrm\Tests\Unit\Loader;

use Mockery;
use SocialDept\AtpOrm\Loader\SlingshotLoader;
use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSupport\Microcosm\Data\GetRecordResponse;
use SocialDept\AtpSupport\Microcosm\MicrocosmException;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;

class SlingshotLoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockSlingshot(): Mockery\MockInterface
    {
        return Mockery::mock(SlingshotClient::class);
    }

    public function test_get_record_returns_array(): void
    {
        $slingshot = $this->mockSlingshot();
        $slingshot->shouldReceive('getRecord')
            ->once()
            ->with('did:plc:abc', 'app.bsky.feed.post', 'rk1')
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'bafyreiabc',
                'value' => ['text' => 'Hello world', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $loader = new SlingshotLoader($slingshot);
        $result = $loader->getRecord('did:plc:abc', 'app.bsky.feed.post', 'rk1');

        $this->assertIsArray($result);
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rk1', $result['uri']);
        $this->assertSame('bafyreiabc', $result['cid']);
        $this->assertSame('Hello world', $result['value']['text']);
    }

    public function test_get_record_by_uri_returns_array(): void
    {
        $uri = 'at://did:plc:abc/app.bsky.feed.post/rk1';

        $slingshot = $this->mockSlingshot();
        $slingshot->shouldReceive('getRecordByUri')
            ->once()
            ->with($uri)
            ->andReturn(GetRecordResponse::fromArray([
                'uri' => $uri,
                'cid' => 'bafyreiabc',
                'value' => ['text' => 'Hello', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]));

        $loader = new SlingshotLoader($slingshot);
        $result = $loader->getRecordByUri($uri);

        $this->assertIsArray($result);
        $this->assertSame($uri, $result['uri']);
        $this->assertSame('bafyreiabc', $result['cid']);
    }

    public function test_get_record_propagates_exception(): void
    {
        $slingshot = $this->mockSlingshot();
        $slingshot->shouldReceive('getRecord')
            ->once()
            ->andThrow(MicrocosmException::requestFailed('/xrpc/com.atproto.repo.getRecord', 'Not found'));

        $loader = new SlingshotLoader($slingshot);

        $this->expectException(MicrocosmException::class);
        $loader->getRecord('did:plc:abc', 'app.bsky.feed.post', 'notfound');
    }
}
