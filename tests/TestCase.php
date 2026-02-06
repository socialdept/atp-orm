<?php

namespace SocialDept\AtpOrm\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpOrm\AtpOrmServiceProvider;
use SocialDept\AtpResolver\ResolverServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ResolverServiceProvider::class,
            AtpClientServiceProvider::class,
            AtpOrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('atp-orm.cache_provider', \SocialDept\AtpOrm\Providers\ArrayCacheProvider::class);
        $app['config']->set('atp-orm.cache.default_ttl', 300);
        $app['config']->set('atp-orm.cache.prefix', 'atp-orm');
        $app['config']->set('atp-orm.query.default_limit', 50);
        $app['config']->set('atp-orm.query.max_limit', 100);
        $app['config']->set('atp-orm.events.enabled', true);
    }
}
