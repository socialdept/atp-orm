<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Loader\RepoLoader;

class RepoLoaderTest extends TestCase
{
    public function test_repo_loader_class_exists(): void
    {
        $this->assertTrue(class_exists(RepoLoader::class));
    }

    public function test_parse_car_for_collection_returns_empty_for_empty_blocks(): void
    {
        $loader = new RepoLoader();

        $reflection = new \ReflectionClass($loader);
        $method = $reflection->getMethod('findMstRoot');
        $method->setAccessible(true);

        $result = $method->invoke($loader, []);

        $this->assertNull($result);
    }
}
