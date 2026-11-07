# Cache providers

Part of the [atp-orm docs](../introduction.md#documentation). The three providers that ship, writing one of your own, and caching in tests.

The `cache_provider` setting (`ATP_ORM_CACHE_PROVIDER`) names the class that stores cached results:

| Provider | Stores results in |
|----------|-------------------|
| `LaravelCacheProvider` | A Laravel cache store, `cache.store` (`ATP_ORM_CACHE_STORE`), or the default store when that is `null`. This is the default. |
| `FileCacheProvider` | Files in `cache.file_path`, `storage/app/atp-orm-cache` by default. |
| `ArrayCacheProvider` | Memory, for the life of the process. |

All three live in `SocialDept\AtpOrm\Providers`. Every key starts with `cache.prefix`, `atp-orm` by default.

```php
// config/atp-orm.php
'cache_provider' => \SocialDept\AtpOrm\Providers\FileCacheProvider::class,
```

Clearing a collection in a repo removes every key with a given prefix, which a Laravel cache store cannot list. `LaravelCacheProvider` keeps the keys it has written in a list of its own, under `atp-orm:tracked-keys`, to know which to remove.

## Your own provider

A provider implements `SocialDept\AtpOrm\Contracts\CacheProvider` and is created with no constructor arguments:

```php
use SocialDept\AtpOrm\Contracts\CacheProvider;

class MyCacheProvider implements CacheProvider
{
    public function get(string $key): mixed {}

    public function put(string $key, mixed $value, int $ttl): void {}

    public function has(string $key): bool {}

    public function forget(string $key): void {}

    public function flush(string $prefix): void {} // remove every key starting with $prefix
}
```

`get()` returns `null` for a missing or expired key.

## In tests

`ArrayCacheProvider` keeps tests fast and isolated. Set it in `phpunit.xml`:

```xml
<env name="ATP_ORM_CACHE_PROVIDER" value="SocialDept\AtpOrm\Providers\ArrayCacheProvider"/>
```
