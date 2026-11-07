# Writing records

Part of the [atp-orm docs](../introduction.md#documentation). Creating, updating and deleting records as an authenticated account.

Writes need an account to make them. `as()` names it, and sets the repo to the same DID:

```php
Post::as($did);
```

Writes go through [atp-client](https://github.com/socialdept/atp-client)'s `Atp::as($did)`, so the account needs a session there, from OAuth or an app password, kept by its credential provider. A write without `as()` throws a [`ReadOnlyException`](../errors.md).

## Creating records

```php
$post = Post::as($did)->create([
    'text' => 'Hello from ORM!',
    'createdAt' => now()->toIso8601String(),
]);

$post->getUri(); // the new record's AT-URI
```

The attributes are built into the class's DTO and written with `com.atproto.repo.createRecord`. The PDS picks the record key unless one is passed as the second argument:

```php
Post::as($did)->create($attributes, 'self');
```

## Updating records

Find the record through `as()`, change it, and save:

```php
$post = Post::as($did)->find($rkey);

$post->text = 'Updated text';
$post->save();

// Or in one call
$post->update(['text' => 'Updated text']);
```

> [!NOTE]
> The cache key for a record does not include the account. A record cached by a read through `for()` comes back without one, and saving it throws a `ReadOnlyException`. Skip the cache when fetching a record to write: `Post::as($did)->fresh()->find($rkey)`.

`save()` writes the whole record with `com.atproto.repo.putRecord`, passing the CID it was fetched with as `swapRecord`, so the PDS rejects the write if the record changed in the meantime. On a record that does not exist yet, `save()` creates it.

## Deleting records

```php
$post = Post::as($did)->find($rkey);
$post->delete();
```

`delete()` calls `com.atproto.repo.deleteRecord`, also with `swapRecord`, and returns `true`. It returns `false` without a request when the record does not exist or has no record key.

## The cache after a write

Each write clears the cached results for that collection in that repo, as [`invalidate()`](../caching/ttls.md#manual-invalidation) does.
