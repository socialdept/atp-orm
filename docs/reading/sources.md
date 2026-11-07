# Record sources

Part of the [atp-orm docs](../introduction.md#documentation). Reading from the PDS, from Slingshot, or from a CAR export of the whole repo.

## The PDS

By default every read goes to the repo's own PDS, found through atp-support's resolver. When the PDS cannot be resolved, reads go to atp-support's `public_api` (`ATP_PUBLIC_API`, `https://public.api.bsky.app` by default).

## Slingshot

[Slingshot](https://microcosm.blue) is a cache of records run by Microcosm. `find()` can read from it instead of the PDS:

```php
$post = Post::for($did)->viaSlingshot()->find($rkey);
```

Or for every `find()`, in the config:

```php
// config/atp-orm.php
'record_source' => env('ATP_ORM_RECORD_SOURCE', 'pds'), // 'pds' or 'slingshot'
```

Slingshot returns the same record data as the PDS, but it may be slightly behind it. The setting applies to `find()`, `findOrFail()` and `findByUri()`. Listings always read from the PDS. The Slingshot URL comes from atp-support's `ATP_SLINGSHOT_URL`.

## CAR export

To load a whole collection at once, `fromRepo()` downloads the repo as a CAR file through `com.atproto.sync.getRepo` and reads the collection's records out of it, instead of paging through `listRecords`:

```php
$allPosts = Post::for($did)->fromRepo()->get();
```

The CAR file is parsed with [atp-cbor](https://github.com/socialdept/atp-cbor), which the package requires, so nothing else needs installing. The export holds the whole repo, every collection in it, so this suits large collections better than small ones. The result has every record in one `RemoteCollection` with no cursor. `limit()`, `after()` and `reverse()` do not apply to it.
