<?php

namespace SocialDept\AtpOrm\Tests\Feature;

use SocialDept\AtpOrm\Tests\TestCase;
use SocialDept\AtpSchema\SchemaServiceProvider;

class MakeRemoteRecordCommandTest extends TestCase
{
    private string $outputPath;

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            SchemaServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = $this->app->basePath('app/Remote');
    }

    protected function tearDown(): void
    {
        // Clean up any generated files
        if (is_dir($this->outputPath)) {
            $files = glob($this->outputPath.'/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
            @rmdir($this->outputPath);
        }

        parent::tearDown();
    }

    public function test_command_has_no_dto_option(): void
    {
        $command = $this->artisan('make:remote-record', [
            'name' => 'TestPost',
            '--collection' => 'app.bsky.feed.post',
            '--no-dto' => true,
        ]);

        $command->assertSuccessful();
    }

    public function test_skips_generation_when_class_already_resolved(): void
    {
        // app.bsky.feed.post resolves to the pre-generated DTO in atp-schema
        $this->artisan('make:remote-record', [
            'name' => 'ExistingPost',
            '--collection' => 'app.bsky.feed.post',
        ])
            ->doesntExpectOutputToContain('Generating DTO')
            ->assertSuccessful();

        $contents = file_get_contents($this->outputPath.'/ExistingPost.php');

        $this->assertStringContainsString(
            'SocialDept\AtpSchema\Generated\App\Bsky\Feed\Post',
            $contents,
        );
    }

    public function test_no_dto_flag_skips_generation_and_falls_back_to_convention(): void
    {
        $this->artisan('make:remote-record', [
            'name' => 'CustomThing',
            '--collection' => 'custom.nonexistent.thing',
            '--no-dto' => true,
        ])
            ->doesntExpectOutputToContain('Generating DTO')
            ->assertSuccessful();

        $contents = file_get_contents($this->outputPath.'/CustomThing.php');

        $this->assertStringContainsString(
            'SocialDept\AtpSchema\Generated\Custom\Nonexistent\Thing',
            $contents,
        );
    }

    public function test_attempts_dto_generation_for_unknown_collection(): void
    {
        $this->artisan('make:remote-record', [
            'name' => 'UnknownRecord',
            '--collection' => 'com.unknown.fake.record',
        ])
            ->expectsOutputToContain('Generating DTO')
            ->assertSuccessful();
    }
}
