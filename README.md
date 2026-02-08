[![ORM Header](./header.png)](https://github.com/socialdept/atp-orm)

<h3 align="center">
    Eloquent-like ORM for AT Protocol remote records in Laravel.
</h3>

<p align="center">
    <br>
    <a href="https://packagist.org/packages/socialdept/atp-orm" title="Latest Version on Packagist"><img src="https://img.shields.io/packagist/v/socialdept/atp-orm.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/socialdept/atp-orm" title="Total Downloads"><img src="https://img.shields.io/packagist/dt/socialdept/atp-orm.svg?style=flat-square"></a>
    <a href="https://github.com/socialdept/atp-orm/actions/workflows/tests.yml" title="GitHub Tests Action Status"><img src="https://img.shields.io/github/actions/workflow/status/socialdept/atp-orm/tests.yml?branch=main&label=tests&style=flat-square"></a>
    <a href="LICENSE" title="Software License"><img src="https://img.shields.io/github/license/socialdept/atp-orm?style=flat-square"></a>
</p>

---

## What is ORM?

**ORM** is a Laravel package that brings an Eloquent-like interface to AT Protocol remote records. Query Bluesky posts, likes, follows, and any other AT Protocol collection as if they were local database models — with built-in caching, pagination, dirty tracking, and write support.

Think of it as Eloquent, but for the AT Protocol.

## Why use ORM?

- **Familiar API** - Query remote records with the same patterns you use for Eloquent models
- **Built-in caching** - Configurable TTLs with automatic cache invalidation via firehose
- **Pagination** - Cursor-based pagination that works out of the box
- **Type-safe** - Backed by [`atp-schema`](https://github.com/socialdept/atp-schema) generated DTOs with full property access
- **Read & write** - Fetch, create, update, and delete records with authentication
- **Dirty tracking** - Track attribute changes just like Eloquent
- **Backlink discovery** - Find all records that link to a given record via [Microcosm](https://microcosm.blue)
- **Slingshot support** - Optionally fetch records from Slingshot cache instead of PDS
- **Events** - Laravel events for record lifecycle hooks
- **Zero config** - Works out of the box with sensible defaults

## Quick Example

```php
use App\Remote\Post;

// List a user's posts
$posts = Post::for('alice.bsky.social')->limit(10)->get();

foreach ($posts as $post) {
    echo $post->text;
    echo $post->createdAt;
}

// Paginate through all posts
while ($posts->hasMorePages()) {
    $posts = $posts->nextPage();
}

// Find a specific post
$post = Post::for('did:plc:ewvi7nxzyoun6zhxrhs64oiz')->find('3mdtrzs7kts2p');
echo $post->text;

// Find by AT-URI
$post = Post::for('alice.bsky.social')
    ->findByUri('at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3mdtrzs7kts2p');
```

## Installation

```bash
composer require socialdept/atp-orm
```

ORM will auto-register with Laravel. Optionally publish the config:

```bash
php artisan vendor:publish --tag=atp-orm-config
```

## Defining Remote Records

Create a model class that extends `RemoteRecord`:

```bash
php artisan make:remote-record Post --collection=app.bsky.feed.post
```

This generates:

```php
namespace App\Remote;

use SocialDept\AtpOrm\RemoteRecord;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Post as PostData;

class Post extends RemoteRecord
{
    protected string $collection = 'app.bsky.feed.post';
    protected string $recordClass = PostData::class;
    protected int $cacheTtl = 300;
}
```

| Property | Description |
|----------|-------------|
| `$collection` | The AT Protocol collection NSID |
| `$recordClass` | The atp-schema DTO class for type-safe hydration |
| `$cacheTtl` | Cache duration in seconds (0 = use config default) |

## Querying Records

### Listing Records

```php
use App\Remote\Post;

// Basic listing
$posts = Post::for('alice.bsky.social')->get();

// With options
$posts = Post::for('did:plc:ewvi7nxzyoun6zhxrhs64oiz')
    ->limit(25)
    ->reverse()
    ->get();
```

### Finding a Single Record

```php
// By record key
$post = Post::for('alice.bsky.social')->find('3mdtrzs7kts2p');

// Throws RecordNotFoundException if not found
$post = Post::for('alice.bsky.social')->findOrFail('3mdtrzs7kts2p');

// By full AT-URI
$post = Post::for('alice.bsky.social')
    ->findByUri('at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3mdtrzs7kts2p');
```

### Pagination

ORM uses cursor-based pagination, matching the AT Protocol's native pattern:

```php
$posts = Post::for($did)->limit(50)->get();

echo $posts->cursor(); // Pagination cursor

while ($posts->hasMorePages()) {
    $posts = $posts->nextPage();

    foreach ($posts as $post) {
        // Process each page...
    }
}
```

You can also paginate manually with `after()`:

```php
$firstPage = Post::for($did)->limit(50)->get();
$secondPage = Post::for($did)->limit(50)->after($firstPage->cursor())->get();
```

### Accessing Attributes

Records support property access, array access, and method access:

```php
$post = Post::for($did)->find($rkey);

// Property access
$post->text;
$post->createdAt;

// Array access
$post['text'];

// Method access
$post->getAttribute('text');

// Record metadata
$post->getUri();    // "at://did:plc:.../app.bsky.feed.post/..."
$post->getRkey();   // "3mdtrzs7kts2p"
$post->getCid();    // "bafyreic3..."
$post->getDid();    // "did:plc:..."

// Convert to atp-schema DTO
$dto = $post->toDto();

// Convert to array
$data = $post->toArray();
```

## Caching

ORM caches query results automatically with configurable TTLs.

### Cache TTL Resolution

TTLs are resolved in order of specificity:

1. **Query-level** - `->remember($ttl)` on the builder
2. **Model-level** - `$cacheTtl` property on the RemoteRecord
3. **Collection-level** - Per-collection overrides in config
4. **Global** - `cache.default_ttl` in config

```php
// Use model's default TTL
$posts = Post::for($did)->get();

// Custom TTL for this query (seconds)
$posts = Post::for($did)->remember(600)->get();

// Bypass cache entirely
$posts = Post::for($did)->fresh()->get();

// Reload a single record from remote
$post = $post->fresh();
```

### Manual Invalidation

```php
// Invalidate all cached data for a scope
Post::for($did)->invalidate();
```

### Automatic Invalidation

When paired with [`atp-signals`](https://github.com/socialdept/atp-signals), ORM can automatically invalidate cache entries when records change on the network:

```php
// config/atp-orm.php
'cache' => [
    'invalidation' => [
        'enabled' => true,
        'collections' => null, // null = all collections
        'dids' => null,        // null = all DIDs
    ],
],
```

### Cache Providers

ORM ships with three cache providers:

| Provider | Use Case |
|----------|----------|
| `LaravelCacheProvider` | Production (default) - uses Laravel's cache system |
| `FileCacheProvider` | Standalone file-based caching |
| `ArrayCacheProvider` | Testing - in-memory, non-persistent |

## Write Operations

Write operations require an authenticated context via `as()`:

### Creating Records

```php
$post = Post::as($authenticatedDid)->create([
    'text' => 'Hello from ORM!',
    'createdAt' => now()->toIso8601String(),
]);

echo $post->getUri(); // "at://did:plc:.../app.bsky.feed.post/..."
```

### Updating Records

```php
$post = Post::as($did)->for($did)->find($rkey);

$post->text = 'Updated text';
$post->save();

// Or in one call
$post->update(['text' => 'Updated text']);
```

### Deleting Records

```php
$post = Post::as($did)->for($did)->find($rkey);
$post->delete();
```

### Dirty Tracking

ORM tracks attribute changes like Eloquent:

```php
$post = Post::for($did)->find($rkey);

$post->isDirty();        // false
$post->text = 'New text';
$post->isDirty();        // true
$post->isDirty('text');  // true
$post->getDirty();       // ['text' => 'New text']
$post->getOriginal('text'); // Original value
```

## Bulk Loading with CAR Export

When you need to load an entire collection efficiently, use `fromRepo()` to fetch via CAR export instead of paginating through `listRecords`:

```php
// Requires socialdept/atp-signals
$allPosts = Post::for($did)->fromRepo()->get();
```

This uses `com.atproto.sync.getRepo` to fetch the repository as a CAR file and extract records locally — significantly faster for large collections.

## Backlink Queries

ORM integrates with [Microcosm's Constellation](https://microcosm.blue) to discover all records that link to a given record across the entire AT Protocol network.

### Basic Usage

```php
$post = Post::for('did:plc:abc')->find('rk1');

// Get all likes on this post
$likes = $post->backlinks()->likes();

echo $likes->total();  // 2852
echo $likes->count();  // Items in this page

foreach ($likes as $ref) {
    echo $ref->did;    // Who liked it
    echo $ref->uri();  // at://did/app.bsky.feed.like/rkey
}
```

### Convenience Methods

Common Bluesky interaction types have built-in shortcuts:

```php
$post->backlinks()->likes();      // app.bsky.feed.like -> subject.uri
$post->backlinks()->quotes();     // app.bsky.feed.post -> embed.record.uri
$post->backlinks()->replies();    // app.bsky.feed.post -> reply.parent.uri
$post->backlinks()->reposts();    // app.bsky.feed.repost -> subject.uri
$post->backlinks()->mentions();   // app.bsky.feed.post -> facets[...].features[...mention].did
$post->backlinks()->followers();  // app.bsky.graph.follow -> subject
```

### Custom Sources

Query any collection and field path using `source()`:

```php
// Find all records in a custom collection that link to this post
$backlinks = $post->backlinks()
    ->source('com.example.bookmark', 'post.uri')
    ->limit(50)
    ->reverse()
    ->get();
```

The source format is `collection:path` where the path is the dot-notation location of the linking field within the record.

### Standalone Queries

You don't need a `RemoteRecord` instance to query backlinks:

```php
use SocialDept\AtpOrm\Backlinks\BacklinkQuery;

// Find followers of a DID
$followers = BacklinkQuery::for('did:plc:abc')
    ->source('app.bsky.graph.follow', 'subject')
    ->get();

// Get a count
$likeCount = BacklinkQuery::for('at://did:plc:abc/app.bsky.feed.post/rk1')
    ->source('app.bsky.feed.like', 'subject.uri')
    ->count();
```

### Link Summary

Get a summary of all link types pointing at a target at once:

```php
$summary = $post->backlinks()->all();

// Returns LinkSummary with nested structure:
// app.bsky.feed.like -> .subject.uri -> { records: 2852, distinct_dids: 2852 }
// app.bsky.feed.post -> .embed.record.uri -> { records: 1143, distinct_dids: 1123 }
// app.bsky.feed.repost -> .subject.uri -> { records: 320, distinct_dids: 320 }

$summary->total();                              // 7205
$summary->forCollection('app.bsky.feed.like');  // Filter to a single collection
```

### Pagination

Backlink queries support cursor-based pagination:

```php
$likes = $post->backlinks()->likes();

while ($likes->hasMorePages()) {
    $likes = $likes->nextPage();
}
```

### Hydration

Hydrate backlink references into full `RemoteRecord` instances via Slingshot:

```php
$hydrated = $post->backlinks()
    ->source('app.bsky.feed.like', 'subject.uri')
    ->hydrate(Like::class);

// Returns RemoteCollection of Like instances
foreach ($hydrated as $like) {
    echo $like->subject; // Full record data
}
```

### BacklinkCollection Helpers

```php
$likes = $post->backlinks()->likes();

$likes->uris();     // Collection of AT-URIs
$likes->dids();     // Collection of unique DIDs
$likes->toArray();  // Array of {did, collection, rkey, uri}
$likes->filter(fn ($ref) => $ref->did === 'did:plc:abc');
```

## Slingshot Record Source

By default, ORM fetches records directly from the user's PDS. You can optionally route through [Slingshot](https://microcosm.blue) for faster cached reads:

```php
// Per-query
$post = Post::for('did:plc:abc')->viaSlingshot()->find('rk1');
```

Or set it globally in config:

```php
// config/atp-orm.php
'record_source' => env('ATP_ORM_RECORD_SOURCE', 'pds'), // 'pds' or 'slingshot'
```

Slingshot returns the same record data as the PDS, but from a globally distributed cache. Records may be slightly stale compared to direct PDS reads.

## Events

ORM fires Laravel events for record lifecycle changes:

| Event | Fired When |
|-------|------------|
| `RecordCreated` | A new record is created |
| `RecordUpdated` | An existing record is updated |
| `RecordDeleted` | A record is deleted |
| `RecordFetched` | A record is fetched from remote |

```php
use SocialDept\AtpOrm\Events\RecordCreated;

Event::listen(RecordCreated::class, function (RecordCreated $event) {
    logger()->info('Record created', [
        'uri' => $event->record->getUri(),
    ]);
});
```

Events can be disabled in config:

```php
'events' => [
    'enabled' => false,
],
```

## AT-URI Helper

ORM includes an `AtUri` helper for parsing and building AT Protocol URIs:

```php
use SocialDept\AtpOrm\Support\AtUri;

$uri = AtUri::parse('at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3mdtrzs7kts2p');

$uri->did;        // "did:plc:ewvi7nxzyoun6zhxrhs64oiz"
$uri->collection; // "app.bsky.feed.post"
$uri->rkey;       // "3mdtrzs7kts2p"

// Build a URI
$uri = AtUri::make($did, 'app.bsky.feed.post', $rkey);
echo (string) $uri; // "at://did/app.bsky.feed.post/rkey"
```

## RemoteCollection

Query results are returned as `RemoteCollection` instances with a familiar collection API:

```php
$posts = Post::for($did)->get();

$posts->count();
$posts->isEmpty();
$posts->isNotEmpty();
$posts->first();
$posts->last();
$posts->pluck('text');
$posts->filter(fn ($post) => str_contains($post->text, 'hello'));
$posts->map(fn ($post) => $post->text);
$posts->each(fn ($post) => logger()->info($post->text));
$posts->toArray();
$posts->toCollection(); // Convert to Laravel Collection
```

## Configuration

Customize behavior in `config/atp-orm.php`:

```php
return [
    // Cache provider class (LaravelCacheProvider, FileCacheProvider, or ArrayCacheProvider)
    'cache_provider' => \SocialDept\AtpOrm\Providers\LaravelCacheProvider::class,

    // Record source: 'pds' (default) or 'slingshot' (Microcosm cache)
    'record_source' => env('ATP_ORM_RECORD_SOURCE', 'pds'),

    'cache' => [
        'default_ttl' => 300,      // 5 minutes (0 = no caching)
        'prefix' => 'atp-orm',
        'store' => null,           // Laravel cache store (null = default)
        'file_path' => storage_path('app/atp-orm-cache'), // FileCacheProvider storage path

        // Per-collection TTL overrides
        'ttls' => [
            'app.bsky.feed.post' => 600,
            'app.bsky.graph.follow' => 3600,
        ],

        // Automatic invalidation via firehose (requires atp-signals)
        'invalidation' => [
            'enabled' => false,
            'collections' => null,  // null = auto from registered models
            'dids' => null,         // null = all
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
        'public_service' => 'https://public.api.bsky.app',
    ],

    'generators' => [
        'path' => 'app/Remote',
    ],
];
```

## Error Handling

ORM throws descriptive exceptions:

```php
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;
use SocialDept\AtpOrm\Exceptions\RecordNotFoundException;

try {
    $post = Post::for($did)->findOrFail('nonexistent');
} catch (RecordNotFoundException $e) {
    // "Record not found: at://did/app.bsky.feed.post/nonexistent"
}

try {
    // Attempting write without ::as()
    Post::for($did)->create(['text' => 'Hello']);
} catch (ReadOnlyException $e) {
    // "Cannot write without an authenticated DID. Use ::as($did) for write operations."
}
```

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

Use the `ArrayCacheProvider` in tests for fast, isolated caching:

```php
// config/atp-orm.php (in testing environment)
'cache_provider' => \SocialDept\AtpOrm\Providers\ArrayCacheProvider::class,
```

## Requirements

- PHP 8.2+
- Laravel 11+
- [socialdept/atp-support](https://github.com/socialdept/atp-support) - Identity resolution, Microcosm clients
- [socialdept/atp-client](https://github.com/socialdept/atp-client) - Authenticated AT Protocol HTTP client
- [socialdept/atp-schema](https://github.com/socialdept/atp-schema) - Lexicon parsing and DTO generation

### Optional

- [socialdept/atp-signals](https://github.com/socialdept/atp-signals) - Automatic cache invalidation and CAR-based bulk loading

## Resources

- [AT Protocol Documentation](https://atproto.com/)
- [Bluesky API Docs](https://docs.bsky.app/)
- [AT-URI Specification](https://atproto.com/specs/at-uri-scheme)
- [Lexicon Specification](https://atproto.com/specs/lexicon)

## Support & Contributing

Found a bug or have a feature request? [Open an issue](https://github.com/socialdept/atp-orm/issues).

Want to contribute? We'd love your help! Check out the [contribution guidelines](CONTRIBUTING.md).

## Credits

- [Miguel Batres](https://batres.co) - founder & lead maintainer
- [All contributors](https://github.com/socialdept/atp-orm/graphs/contributors)

## License

ORM is open-source software licensed under the [MIT license](LICENSE).

---

**Built for the Atmosphere** &bull; By Social Dept.
