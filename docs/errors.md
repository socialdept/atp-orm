# Errors

Part of the [atp-orm docs](introduction.md#documentation). The exceptions the package throws, and the failures it turns into `null`.

Both exceptions live in `SocialDept\AtpOrm\Exceptions` and extend `RuntimeException`.

## RecordNotFoundException

`findOrFail()` throws it when there is no record:

```php
use SocialDept\AtpOrm\Exceptions\RecordNotFoundException;

try {
    $post = Post::for($did)->findOrFail('nonexistent');
} catch (RecordNotFoundException $e) {
    // "Record not found: at://{did}/app.bsky.feed.post/nonexistent"
}
```

## ReadOnlyException

`create()`, `save()`, `update()` and `delete()` throw it when the query or the record has no account to write as. See [Writing records](writing/records.md).

```php
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;

try {
    Post::for($did)->create(['text' => 'Hello']);
} catch (ReadOnlyException $e) {
    // "Cannot create without an authenticated DID. Use ::as($did) for write operations."
}
```

## Failures that return null

`find()` and `findByUri()` catch every error from the fetch and return `null`, so a PDS that is down looks the same as a missing record. `findByUri()` also returns `null` for a URI it cannot parse. A record that fails to hydrate is left out of a [hydrated backlink result](backlinks/results.md#hydration).

Errors from listings, CAR exports, writes and backlink queries are not caught, and reach your code unchanged.
