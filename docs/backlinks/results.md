# Backlink results

Part of the [atp-orm docs](../introduction.md#documentation). Paging through results, their helpers, hydrating them into records, and link summaries.

A backlink query returns a `SocialDept\AtpOrm\Backlinks\BacklinkCollection` of atp-support `BacklinkReference` objects. Each reference has `did`, `collection` and `rkey`, and `uri()` builds its AT-URI.

## Pagination

```php
$likes = $post->backlinks()->likes();

while ($likes->hasMorePages()) {
    $likes = $likes->nextPage();
}
```

`nextPage()` runs the same query from the cursor and returns `null` when there are no more pages. `cursor()` returns the cursor itself.

## Helpers

```php
$likes->total();    // every link Constellation knows of, across all pages
$likes->count();    // the links on this page
$likes->uris();     // a Laravel Collection of AT-URIs
$likes->dids();     // a Laravel Collection of unique DIDs
$likes->toArray();  // an array of ['did', 'collection', 'rkey', 'uri']
$likes->filter(fn ($ref) => $ref->did === $did);
```

It also has `first()`, `last()`, `map()`, `each()`, `isEmpty()`, `isNotEmpty()`, `all()` and `toCollection()`, which work as they do on a [`RemoteCollection`](../reading/collections.md).

## Hydration

References only say where the linking records are. `hydrate()` fetches each one from Slingshot and builds it into a remote record class:

```php
$likes = $post->backlinks()
    ->source('app.bsky.feed.like', 'subject.uri')
    ->hydrate(Like::class);

foreach ($likes as $like) {
    $like->subject;
}
```

`Like` here is a remote record class for `app.bsky.feed.like`. The result is a `RemoteCollection`. A record that cannot be fetched is left out of it. Hydration always reads from Slingshot, whatever `record_source` says.

## Link summary

`all()` returns atp-support's `LinkSummary`, with a count for every collection and path that links to the subject:

```php
$summary = $post->backlinks()->all();

$summary->links;                               // collection => path => ['records' => ..., 'distinct_dids' => ...]
$summary->forCollection('app.bsky.feed.like'); // path => counts, for one collection
$summary->total();                             // the records across every collection and path
```
