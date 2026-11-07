# Backlink queries

Part of the [atp-orm docs](../introduction.md#documentation). Finding and counting the records that link to a record or a DID, with the shortcuts for common Bluesky links.

A backlink is a record anywhere on the network that points at another record or at a DID, such as a like pointing at a post. The package asks Microcosm's [Constellation](https://microcosm.blue) for them, through atp-support's `ConstellationClient`. The Constellation URL comes from atp-support's `ATP_CONSTELLATION_URL`.

## From a record

`backlinks()` on a remote record starts a query for links to that record's AT-URI:

```php
$post = Post::for($did)->find($rkey);

$likes = $post->backlinks()->likes();

$likes->total(); // every like Constellation knows of
$likes->count(); // the likes on this page

foreach ($likes as $ref) {
    $ref->did;   // who liked it
    $ref->uri(); // the like's AT-URI
}
```

## Shortcuts

Common Bluesky links have a method each, which runs the query:

| Method | Collection | Path |
|--------|------------|------|
| `likes()` | `app.bsky.feed.like` | `subject.uri` |
| `quotes()` | `app.bsky.feed.post` | `embed.record.uri` |
| `replies()` | `app.bsky.feed.post` | `reply.parent.uri` |
| `reposts()` | `app.bsky.feed.repost` | `subject.uri` |
| `mentions()` | `app.bsky.feed.post` | `facets[app.bsky.richtext.facet].features[app.bsky.richtext.facet#mention].did` |
| `followers()` | `app.bsky.graph.follow` | `subject` |

`mentions()` and `followers()` look for links to a DID, so use them on a query for one, as below.

## Any source

`source()` names the collection and the path to the linking field in its records, then `get()` runs the query:

```php
$bookmarks = $post->backlinks()
    ->source('com.example.bookmark', 'post.uri')
    ->limit(50)
    ->reverse()
    ->get();
```

The source can also be written as one `collection:path` string, `source('com.example.bookmark:post.uri')`.

## Without a record

`BacklinkQuery::for()` takes an AT-URI or a DID:

```php
use SocialDept\AtpOrm\Backlinks\BacklinkQuery;

$followers = BacklinkQuery::for($did)
    ->source('app.bsky.graph.follow', 'subject')
    ->get();

$likeCount = BacklinkQuery::for($uri)
    ->source('app.bsky.feed.like', 'subject.uri')
    ->count();
```

## Query options

| Method | Effect |
|--------|--------|
| `did($dids)` | Only links from these DIDs, one or an array. |
| `limit($limit)` | Page size, 16 by default, capped at 100. |
| `reverse()` | The opposite order. |
| `after($cursor)` | Start from a cursor. |
| `remember($ttl)` | Cache for this many seconds, instead of `cache.default_ttl`. |
| `fresh()` | Skip the cached result. |

And to run it:

| Method | Returns |
|--------|---------|
| `get()` | A [`BacklinkCollection`](results.md) of references. |
| `count()` | The number of links, as an int. |
| `all()` | A [`LinkSummary`](results.md#link-summary) of every kind of link to the subject. |
| `hydrate($class)` | The linking records, fetched in full. See [Hydration](results.md#hydration). |
