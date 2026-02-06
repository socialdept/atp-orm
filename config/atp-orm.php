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

    'query' => [
        'default_limit' => 50,
        'max_limit' => 100,
    ],

    'events' => [
        'enabled' => true,
    ],

    'pds' => [
        'public_service' => env('ATP_PUBLIC_SERVICE_URL', 'https://public.api.bsky.app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Record Class Resolution
    |--------------------------------------------------------------------------
    |
    | Configure how collection NSIDs are resolved to PHP Data classes.
    | The resolver checks namespaces in order — app lexicons first, then
    | the bundled pre-generated classes from atp-schema.
    |
    */

    'generated' => [
        // Namespace for app-level lexicon classes (checked first)
        'app_namespace' => env('ATP_ORM_APP_NAMESPACE', 'App\\Lexicons'),

        // Namespace for bundled pre-generated classes (checked second)
        'schema_namespace' => env('ATP_ORM_SCHEMA_NAMESPACE', 'SocialDept\\AtpSchema\\Generated'),
    ],

    'generators' => [
        'path' => 'app/Remote',
    ],

];
