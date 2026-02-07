<?php

namespace SocialDept\AtpOrm;

use Illuminate\Support\ServiceProvider;
use SocialDept\AtpOrm\Cache\CacheKeyGenerator;
use SocialDept\AtpOrm\Console\MakeRemoteRecordCommand;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Loader\RepoLoader;
use SocialDept\AtpOrm\Support\RecordHydrator;

class AtpOrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atp-orm.php', 'atp-orm');

        $this->app->singleton(CacheKeyGenerator::class, function () {
            return new CacheKeyGenerator(config('atp-orm.cache.prefix', 'atp-orm'));
        });

        $this->app->singleton(RecordHydrator::class);

        $this->app->singleton(CacheProvider::class, function () {
            $providerClass = config('atp-orm.cache_provider');

            return new $providerClass();
        });

        $this->registerRepoLoader();
    }

    public function boot(): void
    {
        $this->registerCacheInvalidationSignal();

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    protected function registerRepoLoader(): void
    {
        $this->app->singleton(RepoLoader::class);
    }

    protected function registerCacheInvalidationSignal(): void
    {
        if (! config('atp-orm.cache.invalidation.enabled', false)) {
            return;
        }

        if (! class_exists(\SocialDept\AtpSignals\Signals\Signal::class)) {
            return;
        }

        // The signal will be auto-discovered by atp-signals if placed in app/Signals/,
        // or manually registered in signal config. We just make it resolvable.
        $this->app->singleton(\SocialDept\AtpOrm\Signals\CacheInvalidationSignal::class, function ($app) {
            return new \SocialDept\AtpOrm\Signals\CacheInvalidationSignal(
                $app->make(CacheProvider::class),
                $app->make(CacheKeyGenerator::class),
            );
        });
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__.'/../config/atp-orm.php' => config_path('atp-orm.php'),
        ], 'atp-orm-config');

        $this->publishes([
            __DIR__.'/../stubs/remote-record.stub' => base_path('stubs/remote-record.stub'),
        ], 'atp-orm-stubs');

        $this->commands([
            MakeRemoteRecordCommand::class,
        ]);
    }

    public function provides(): array
    {
        return [
            CacheKeyGenerator::class,
            RecordHydrator::class,
            CacheProvider::class,
            RepoLoader::class,
        ];
    }
}
