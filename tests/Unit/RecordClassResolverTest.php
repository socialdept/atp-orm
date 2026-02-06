<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use SocialDept\AtpOrm\Support\RecordClassResolver;
use SocialDept\AtpOrm\Tests\TestCase;

class RecordClassResolverTest extends TestCase
{
    public function test_resolves_known_generated_nsid(): void
    {
        $result = RecordClassResolver::resolve('app.bsky.feed.post');

        $this->assertSame('SocialDept\\AtpSchema\\Generated\\App\\Bsky\\Feed\\Post', $result);
    }

    public function test_returns_null_for_unknown_nsid(): void
    {
        $result = RecordClassResolver::resolve('com.nonexistent.fake.record');

        $this->assertNull($result);
    }

    public function test_app_namespace_checked_before_generated(): void
    {
        config()->set('atp-orm.generated.app_namespace', 'SocialDept\\AtpSchema\\Generated');
        config()->set('atp-orm.generated.schema_namespace', 'NonExistent\\Namespace');

        $result = RecordClassResolver::resolve('app.bsky.feed.post');

        $this->assertSame('SocialDept\\AtpSchema\\Generated\\App\\Bsky\\Feed\\Post', $result);
    }

    public function test_falls_through_to_generated_namespace(): void
    {
        config()->set('atp-orm.generated.app_namespace', 'NonExistent\\AppNamespace');
        config()->set('atp-orm.generated.schema_namespace', 'SocialDept\\AtpSchema\\Generated');

        $result = RecordClassResolver::resolve('app.bsky.feed.post');

        $this->assertSame('SocialDept\\AtpSchema\\Generated\\App\\Bsky\\Feed\\Post', $result);
    }
}
