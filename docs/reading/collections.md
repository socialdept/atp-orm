# Collections

Part of the [atp-orm docs](../introduction.md#documentation). The `RemoteCollection` a listing returns, and its methods.

`get()` returns a `SocialDept\AtpOrm\RemoteCollection`. It is countable and iterable, and carries the cursor for the next page:

```php
$posts = Post::for($did)->get();

foreach ($posts as $post) {
    // ...
}

count($posts);
```

| Method | Returns |
|--------|---------|
| `count()` | The number of records on this page. |
| `isEmpty()`, `isNotEmpty()` | Whether the page has records. |
| `first()`, `last()` | A record, or `null` on an empty page. |
| `all()` | The records as a plain array. |
| `pluck($key)` | A Laravel `Collection` of one attribute from each record. |
| `map($callback)` | A Laravel `Collection` of the callback's results. |
| `filter($callback)` | A new `RemoteCollection` with the records that pass, keeping the cursor. |
| `each($callback)` | The same collection, after calling the callback on each record. |
| `toArray()` | Each record's `toArray()`. |
| `toCollection()` | The records as a Laravel `Collection`. |
| `cursor()` | The cursor for the next page, or `null`. |
| `hasMorePages()` | Whether there is a cursor. |
| `nextPage()` | The next page, or `null`. See [Pagination](querying.md#pagination). |

```php
$posts->pluck('text');
$posts->filter(fn ($post) => str_contains($post->text, 'hello'));
$posts->map(fn ($post) => $post->text);
```
