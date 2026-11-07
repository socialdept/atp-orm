# Querying

Part of the [atp-orm docs](../introduction.md#documentation). Listing a repo's records, finding one, paging, and reading attributes.

Every read starts with `for()`, which names the repo. It takes a DID or a handle, and a handle is resolved to its DID through [atp-support](https://github.com/socialdept/atp-support)'s resolver.

## Listing records

```php
use App\Remote\Post;

$posts = Post::for('alice.bsky.social')->get();

$posts = Post::for($did)
    ->limit(25)
    ->reverse()
    ->get();
```

`get()` calls `com.atproto.repo.listRecords` on the repo's PDS and returns a [`RemoteCollection`](collections.md). Without `limit()`, a page holds `query.default_limit` records (50). `limit()` is capped at `query.max_limit` (100). `reverse()` asks the PDS for the opposite order.

## Finding a record

```php
// By record key
$post = Post::for($did)->find('3mdtrzs7kts2p');

// Throws RecordNotFoundException when there is none
$post = Post::for($did)->findOrFail('3mdtrzs7kts2p');

// By AT-URI
$post = Post::for($did)->findByUri($uri);
```

`find()` returns `null` when the record does not exist, and also when the fetch fails for any other reason. `findByUri()` reads the DID and record key from the URI, so the repo in the URI wins over the one given to `for()`. To parse or build URIs yourself, use atp-support's `SocialDept\AtpSupport\AtUri`.

## Pagination

Listings use the cursor the PDS returns:

```php
$posts = Post::for($did)->limit(50)->get();

$posts->cursor(); // null on the last page

while ($posts->hasMorePages()) {
    $posts = $posts->nextPage();

    foreach ($posts as $post) {
        // ...
    }
}
```

`nextPage()` runs the same query again from the cursor, with the same limit, order and cache settings, and returns `null` when there are no more pages. A cursor can also be passed by hand with `after()`:

```php
$firstPage = Post::for($did)->limit(50)->get();
$secondPage = Post::for($did)->limit(50)->after($firstPage->cursor())->get();
```

## Reading attributes

A record's fields are read as properties, through array access, or with `getAttribute()`:

```php
$post = Post::for($did)->find($rkey);

$post->text;
$post['text'];
$post->getAttribute('text');
```

A field changed since the record was fetched returns the new value. See [Dirty tracking](../writing/dirty-tracking.md).

The record's place in the network comes from its own methods:

```php
$post->getUri();  // the record's AT-URI
$post->getRkey(); // the record key
$post->getCid();  // the record's CID
$post->getDid();  // the repo's DID
$post->exists();  // true once fetched or saved
```

And it converts to other shapes:

```php
$post->toDto();   // the atp-schema DTO, rebuilt from the changes when there are any
$post->toArray(); // the record's fields as an array
$post->toJson();
```
