# Introduction

**ATP ORM** brings an Eloquent-like interface to AT Protocol records, which live in each account's repo on its PDS. A remote record class names a collection and its atp-schema DTO, and then reads and writes records on the owner's PDS:

- **Querying:** list a repo's records with cursor pagination, or find one by record key or AT-URI, addressing the repo by handle or DID.
- **Record sources:** read from the PDS by default, from Slingshot's cache when asked, or load a whole collection at once from a CAR export of the repo.
- **Writing:** create, update and delete records as an authenticated account, with dirty tracking like Eloquent's.
- **Caching:** query results are cached with TTLs set per query, per class, per collection or globally, and can be invalidated by hand or from the firehose through atp-signals.
- **Events:** Laravel events when a record is fetched, created, updated or deleted.
- **Backlinks:** find, count and summarize the records across the network that link to a record or a DID, through Microcosm's Constellation.

It builds on [atp-client](https://github.com/socialdept/atp-client) for reads and writes, on [atp-schema](https://github.com/socialdept/atp-schema) for record DTOs, and on [atp-support](https://github.com/socialdept/atp-support) for identity resolution and the Microcosm clients.

## A first look

A remote record class names its collection and the DTO its records hydrate into:

```php
namespace App\Remote;

use SocialDept\AtpOrm\RemoteRecord;

class Post extends RemoteRecord
{
    protected string $collection = 'app.bsky.feed.post';

    protected string $recordClass = \SocialDept\AtpSchema\Generated\App\Bsky\Feed\Post::class;

    protected int $cacheTtl = 300;
}
```

Reads start from the repo that holds the records:

```php
use App\Remote\Post;

$posts = Post::for('alice.bsky.social')->limit(10)->get();

foreach ($posts as $post) {
    echo $post->text;
}

$post = Post::for($did)->find($rkey);
```

Writes start from the account that makes them:

```php
$post = Post::as($did)->create([
    'text' => 'Hello from ORM!',
    'createdAt' => now()->toIso8601String(),
]);

$post->text = 'Updated text';
$post->save();
```

## Documentation

Start with [Installation](getting-started/installation.md), then [Remote records](getting-started/remote-records.md) for defining a class per collection. The docs are grouped by area, and each group has an index of its own:

- **[Getting started](getting-started/README.md)**: [Installation](getting-started/installation.md), [Remote records](getting-started/remote-records.md)
- **[Reading](reading/README.md)**: [Querying](reading/querying.md), [Collections](reading/collections.md), [Record sources](reading/sources.md)
- **[Writing](writing/README.md)**: [Writing records](writing/records.md), [Dirty tracking](writing/dirty-tracking.md), [Events](writing/events.md)
- **[Caching](caching/README.md)**: [Cache TTLs](caching/ttls.md), [Invalidation](caching/invalidation.md), [Cache providers](caching/providers.md)
- **[Backlinks](backlinks/README.md)**: [Backlink queries](backlinks/queries.md), [Backlink results](backlinks/results.md)
- **[Errors](errors.md)**: The exceptions the package throws, and the failures it turns into `null`.
