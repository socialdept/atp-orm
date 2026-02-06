<?php

namespace SocialDept\AtpOrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpOrm\Providers\ArrayCacheProvider;
use SocialDept\AtpOrm\Providers\FileCacheProvider;

class CacheProviderTest extends TestCase
{
    // --- ArrayCacheProvider ---

    public function test_array_provider_get_returns_null_when_empty(): void
    {
        $cache = new ArrayCacheProvider();

        $this->assertNull($cache->get('nonexistent'));
    }

    public function test_array_provider_put_and_get(): void
    {
        $cache = new ArrayCacheProvider();
        $cache->put('key1', 'value1', 60);

        $this->assertSame('value1', $cache->get('key1'));
    }

    public function test_array_provider_has(): void
    {
        $cache = new ArrayCacheProvider();
        $cache->put('key1', 'value1', 60);

        $this->assertTrue($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function test_array_provider_forget(): void
    {
        $cache = new ArrayCacheProvider();
        $cache->put('key1', 'value1', 60);
        $cache->forget('key1');

        $this->assertNull($cache->get('key1'));
    }

    public function test_array_provider_flush_by_prefix(): void
    {
        $cache = new ArrayCacheProvider();
        $cache->put('prefix:a', 'val1', 60);
        $cache->put('prefix:b', 'val2', 60);
        $cache->put('other:c', 'val3', 60);

        $cache->flush('prefix:');

        $this->assertNull($cache->get('prefix:a'));
        $this->assertNull($cache->get('prefix:b'));
        $this->assertSame('val3', $cache->get('other:c'));
    }

    public function test_array_provider_expired_entries_return_null(): void
    {
        $cache = new ArrayCacheProvider();
        $cache->put('key1', 'value1', -1); // Already expired

        $this->assertNull($cache->get('key1'));
    }

    public function test_array_provider_stores_complex_values(): void
    {
        $cache = new ArrayCacheProvider();
        $value = ['nested' => ['data' => true], 'count' => 42];
        $cache->put('key1', $value, 60);

        $this->assertSame($value, $cache->get('key1'));
    }

    // --- FileCacheProvider ---

    private string $tempDir;

    private function tempDir(): string
    {
        if (! isset($this->tempDir)) {
            $this->tempDir = sys_get_temp_dir().'/atp-orm-test-cache-'.uniqid();
        }

        return $this->tempDir;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    public function test_file_provider_put_and_get(): void
    {
        $cache = new FileCacheProvider($this->tempDir());
        $cache->put('key1', 'value1', 60);

        $this->assertSame('value1', $cache->get('key1'));
    }

    public function test_file_provider_returns_null_when_missing(): void
    {
        $cache = new FileCacheProvider($this->tempDir());

        $this->assertNull($cache->get('nonexistent'));
    }

    public function test_file_provider_has(): void
    {
        $cache = new FileCacheProvider($this->tempDir());
        $cache->put('key1', 'value1', 60);

        $this->assertTrue($cache->has('key1'));
        $this->assertFalse($cache->has('key2'));
    }

    public function test_file_provider_forget(): void
    {
        $cache = new FileCacheProvider($this->tempDir());
        $cache->put('key1', 'value1', 60);
        $cache->forget('key1');

        $this->assertNull($cache->get('key1'));
    }

    public function test_file_provider_expired_entries_return_null(): void
    {
        $cache = new FileCacheProvider($this->tempDir());
        $cache->put('key1', 'value1', -1);

        $this->assertNull($cache->get('key1'));
    }

    public function test_file_provider_flush_by_prefix(): void
    {
        $cache = new FileCacheProvider($this->tempDir());
        $cache->put('prefix:a', 'val1', 60);
        $cache->put('prefix:b', 'val2', 60);
        $cache->put('other:c', 'val3', 60);

        $cache->flush('prefix:');

        $this->assertNull($cache->get('prefix:a'));
        $this->assertNull($cache->get('prefix:b'));
        $this->assertSame('val3', $cache->get('other:c'));
    }

    public function test_file_provider_creates_directory(): void
    {
        $dir = $this->tempDir().'/nested/dir';
        $cache = new FileCacheProvider($dir);
        $cache->put('key1', 'val', 60);

        $this->assertTrue(is_dir($dir));
        $this->assertSame('val', $cache->get('key1'));

        // Cleanup nested dirs
        unlink(glob($dir.'/*')[0]);
        rmdir($dir);
        rmdir($this->tempDir().'/nested');
    }
}
