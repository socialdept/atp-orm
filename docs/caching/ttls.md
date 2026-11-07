# Cache TTLs

Part of the [atp-orm docs](../introduction.md#documentation). How long results are cached, skipping the cache, and clearing it by hand.

Listings, single records and CAR exports are cached as they are fetched. Each is cached for the first TTL found in this order:

1. **Query:** `remember($ttl)` on the query.
2. **Class:** the `$cacheTtl` property on the remote record class, when it is above `0`.
3. **Collection:** the collection's entry in `cache.ttls`.
4. **Global:** `cache.default_ttl` (`ATP_ORM_CACHE_TTL`, 300 seconds by default).

A TTL of `0` stores nothing.

```php
// config/atp-orm.php
'cache' => [
    'default_ttl' => env('ATP_ORM_CACHE_TTL', 300),
    'ttls' => [
        'app.bsky.actor.profile' => 3600,
    ],
],
```

Classes made by `make:remote-record` set `$cacheTtl = 300`, which wins over `cache.ttls`. Set it to `0` in a class whose collection has an entry there.

[Backlink queries](../backlinks/queries.md) use `remember()` or `cache.default_ttl` only.

## Per query

```php
// The class's TTL, or the config's
$posts = Post::for($did)->get();

// Ten minutes for this query
$posts = Post::for($did)->remember(600)->get();

// Skip the cached result and fetch again
$posts = Post::for($did)->fresh()->get();

// Fetch a record you already have again
$post = $post->fresh();
```

`fresh()` skips reading the cache. The result it fetches is still cached for the next query.

## Manual invalidation

```php
Post::for($did)->invalidate();
```

`invalidate()` clears every cached listing, record and CAR export for that collection in that repo. Cached backlink results are not cleared. Writes made through the package clear the same entries by themselves.
