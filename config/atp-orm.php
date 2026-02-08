<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Provider
    |--------------------------------------------------------------------------
    |
    | The class responsible for caching remote records. Uses the same strategy
    | pattern as atp-client's CredentialProvider. Swap this to any class that
    | implements the CacheProvider contract.
    |
    */

    'cache_provider' => env(
        'ATP_ORM_CACHE_PROVIDER',
        \SocialDept\AtpOrm\Providers\LaravelCacheProvider::class
    ),

    'cache' => [

        // Default TTL in seconds (0 = no caching)
        'default_ttl' => env('ATP_ORM_CACHE_TTL', 300),

        // Per-collection TTL overrides
        'ttls' => [
            // 'app.bsky.feed.post' => 60,
            // 'app.bsky.actor.profile' => 3600,
        ],

        // Key prefix
        'prefix' => 'atp-orm',

        // LaravelCacheProvider: which Laravel cache store to use (null = default)
        'store' => env('ATP_ORM_CACHE_STORE'),

        // FileCacheProvider: storage path
        'file_path' => storage_path('app/atp-orm-cache'),

        // Automatic cache invalidation via firehose/jetstream
        'invalidation' => [
            'enabled' => env('ATP_ORM_CACHE_INVALIDATION', false),
            'collections' => null, // null = auto from registered models
            'dids' => null,        // null = all
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Record Source
    |--------------------------------------------------------------------------
    |
    | Where to fetch individual records from. Options:
    | - 'pds': Fetch directly from the user's Personal Data Server (default)
    | - 'slingshot': Fetch from Slingshot cache (faster, but may be stale)
    |
    | You can also override per-query with ->viaSlingshot() on the builder.
    |
    */

    'record_source' => env('ATP_ORM_RECORD_SOURCE', 'pds'),

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
