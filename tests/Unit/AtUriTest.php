<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Support\AtUri;

class AtUriTest extends TestCase
{
    public function test_parse_valid_uri(): void
    {
        $uri = AtUri::parse('at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3abc123');

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $uri->did);
        $this->assertSame('app.bsky.feed.post', $uri->collection);
        $this->assertSame('3abc123', $uri->rkey);
    }

    public function test_parse_invalid_uri_returns_null(): void
    {
        $this->assertNull(AtUri::parse('not-a-uri'));
        $this->assertNull(AtUri::parse('at://did:plc:abc'));
        $this->assertNull(AtUri::parse('https://example.com'));
        $this->assertNull(AtUri::parse(''));
    }

    public function test_parse_uri_missing_rkey_returns_null(): void
    {
        $this->assertNull(AtUri::parse('at://did:plc:abc/app.bsky.feed.post'));
    }

    public function test_to_string(): void
    {
        $uri = AtUri::make('did:plc:abc', 'app.bsky.feed.post', 'rkey123');

        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rkey123', (string) $uri);
    }

    public function test_parse_roundtrip(): void
    {
        $original = 'at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3abc123';
        $parsed = AtUri::parse($original);

        $this->assertSame($original, (string) $parsed);
    }

    public function test_make_constructor(): void
    {
        $uri = AtUri::make('did:web:example.com', 'app.bsky.actor.profile', 'self');

        $this->assertSame('did:web:example.com', $uri->did);
        $this->assertSame('app.bsky.actor.profile', $uri->collection);
        $this->assertSame('self', $uri->rkey);
    }
}
