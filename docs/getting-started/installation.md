# Installation

Part of the [atp-orm docs](../introduction.md#documentation). Installing the package and its config.

## Installing

```bash
composer require socialdept/atp-orm
```

The service provider registers itself through Laravel's package discovery.

## Configuration

The package works with its defaults. Publish the config to change them:

```bash
php artisan vendor:publish --tag=atp-orm-config
```

That writes `config/atp-orm.php`:

```php
return [
    'cache_provider' => env(
        'ATP_ORM_CACHE_PROVIDER',
        \SocialDept\AtpOrm\Providers\LaravelCacheProvider::class
    ),

    'cache' => [
        'default_ttl' => env('ATP_ORM_CACHE_TTL', 300), // seconds, 0 = no caching
        'ttls' => [
            // 'app.bsky.feed.post' => 60,
        ],
        'prefix' => 'atp-orm',
        'store' => env('ATP_ORM_CACHE_STORE'),           // LaravelCacheProvider, null = default store
        'file_path' => storage_path('app/atp-orm-cache'), // FileCacheProvider
        'invalidation' => [
            'enabled' => env('ATP_ORM_CACHE_INVALIDATION', false),
            'collections' => null,
            'dids' => null,
        ],
    ],

    'record_source' => env('ATP_ORM_RECORD_SOURCE', 'pds'), // 'pds' or 'slingshot'

    'query' => [
        'default_limit' => 50,
        'max_limit' => 100,
    ],

    'events' => [
        'enabled' => true,
    ],

    'generators' => [
        'path' => 'app/Remote',
    ],
];
```

- **`cache_provider`** and **`cache`**: where results are cached and for how long. See [Cache TTLs](../caching/ttls.md), [Invalidation](../caching/invalidation.md) and [Cache providers](../caching/providers.md).
- **`record_source`**: where `find()` fetches single records from. See [Record sources](../reading/sources.md).
- **`query`**: the page size a listing uses when it sets no `limit()`, and the most `limit()` accepts.
- **`events`**: whether the package fires its [events](../writing/events.md).
- **`generators.path`**: where `make:remote-record` writes classes. The namespace follows from the path, so `app/Remote` gives `App\Remote`.

Identity resolution, the public API fallback and the Microcosm service URLs are not set here. They come from the [atp-support](https://github.com/socialdept/atp-support) config, `config/atp-support.php`.

The class stub that `make:remote-record` uses can be published too, to `stubs/remote-record.stub`:

```bash
php artisan vendor:publish --tag=atp-orm-stubs
```
