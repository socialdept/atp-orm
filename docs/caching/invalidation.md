# Invalidation

Part of the [atp-orm docs](../introduction.md#documentation). Clearing cached records from the firehose as they change, through atp-signals.

Cached results go stale when a record changes on the network without going through the package. With [atp-signals](https://github.com/socialdept/atp-signals) installed, the package can clear them as the changes arrive.

```bash
composer require socialdept/atp-signals
```

Turn invalidation on:

```env
ATP_ORM_CACHE_INVALIDATION=true
```

Then register the package's signal with atp-signals, in `config/atp-signals.php`. atp-signals only discovers signals in the app's own `app/Signals` directory, so this one has to be listed:

```php
'signals' => [
    \SocialDept\AtpOrm\Signals\CacheInvalidationSignal::class,
],
```

And run the atp-signals consumer:

```bash
php artisan signal:consume
```

For every record created, updated or deleted in a commit, the signal clears everything cached for that record's collection in its repo: the record itself, listings and CAR exports.

## Narrowing it down

By default the signal follows every collection and every DID. To follow fewer, list them in the config:

```php
// config/atp-orm.php
'cache' => [
    'invalidation' => [
        'enabled' => env('ATP_ORM_CACHE_INVALIDATION', false),
        'collections' => ['app.bsky.feed.post'], // null = all collections
        'dids' => null,                          // null = all DIDs
    ],
],
```
