# Remote records

Part of the [atp-orm docs](../introduction.md#documentation). Defining a class per collection, by hand or with `make:remote-record`.

A remote record class extends `RemoteRecord` and stands for one collection, the way an Eloquent model stands for one table:

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

| Property | Description |
|----------|-------------|
| `$collection` | The collection NSID. |
| `$recordClass` | The [atp-schema](https://github.com/socialdept/atp-schema) DTO class each record hydrates into, and is built from on writes. |
| `$cacheTtl` | Cache duration in seconds. `0`, the default on `RemoteRecord`, defers to the config. See [Cache TTLs](../caching/ttls.md). |

## Generating a class

```bash
php artisan make:remote-record Post --collection=app.bsky.feed.post
```

The class lands in the `generators.path` directory, `app/Remote` by default, with the namespace that follows from it. Without `--collection`, the command asks for the NSID and suggests one from the class name. The command stops if the file already exists.

It fills in `$recordClass` by looking for a DTO in this order:

1. The app's own generated lexicons, in the namespace of atp-schema's `generators.lexicon_path` (`app/Lexicons` by default).
2. atp-schema's pre-generated classes, in its `generated.namespace` (`SocialDept\AtpSchema\Generated` by default).
3. If neither has one, it runs `schema:generate` for the NSID and looks again. Pass `--no-dto` to skip this step.

When no class turns up, it writes the class name the pre-generated namespace would use and prints a warning.

The generated class sets `$cacheTtl = 300`. Set it to `0` to let the per-collection and global TTLs in the config apply instead.

To change what the command writes, publish the stub with `php artisan vendor:publish --tag=atp-orm-stubs` and edit `stubs/remote-record.stub`. The command uses that file when it exists.
