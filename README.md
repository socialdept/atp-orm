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

This Laravel package reads and writes AT Protocol records in a user's repo through classes that work like Eloquent models. A remote record class names a collection and the atp-schema DTO for it, and then lists, finds, creates, updates and deletes records on the user's PDS, with caching, cursor pagination, dirty tracking, events and backlink queries through Constellation. To learn all about it, head over to [the documentation](https://socialde.pt/docs/atp-orm).

Here are a few short examples of what you can do. Generate a remote record class for a collection:

```bash
php artisan make:remote-record Post --collection=app.bsky.feed.post
```

List a user's records, by handle or DID, and page through them:

```php
use App\Remote\Post;

$posts = Post::for('alice.bsky.social')->limit(10)->get();

foreach ($posts as $post) {
    echo $post->text;
}

$next = $posts->nextPage();
```

Find one record by its record key or its AT-URI:

```php
$post = Post::for($did)->find($rkey);
$post = Post::for($did)->findByUri($uri);
```

Write as an authenticated account:

```php
$post = Post::as($did)->create([
    'text' => 'Hello from ORM!',
    'createdAt' => now()->toIso8601String(),
]);

$post->update(['text' => 'Updated text']);
$post->delete();
```

Count and list the records that link to one:

```php
$likes = $post->backlinks()->likes();

$likes->total();
$likes->dids();
```

It builds on [atp-client](https://github.com/socialdept/atp-client) for reads and writes, on [atp-schema](https://github.com/socialdept/atp-schema) for record DTOs, and on [atp-support](https://github.com/socialdept/atp-support) for identity resolution and the Microcosm clients.

## Documentation

You'll find the documentation on [https://socialde.pt/docs/atp-orm](https://socialde.pt/docs/atp-orm).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving it? Feel free to [create an issue on GitHub](https://github.com/socialdept/atp-orm/issues).

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
