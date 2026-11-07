# Dirty tracking

Part of the [atp-orm docs](../introduction.md#documentation). Seeing which fields changed since a record was fetched.

Setting a field on a record does not write it. The change is kept until `save()`, and the record tracks it the way Eloquent does:

```php
$post = Post::for($did)->find($rkey);

$post->isDirty();           // false
$post->text = 'New text';
$post->isDirty();           // true
$post->isDirty('text');     // true
$post->isClean('createdAt'); // true
$post->getDirty();          // ['text' => 'New text']
$post->getOriginal('text'); // the value as fetched
$post->getOriginal();       // every field as fetched
```

Fields can also be set with array access or `setAttribute()`. Unsetting one with `unset($post['text'])` drops the pending change.

A successful `save()` makes the saved values the new originals and clears the changes.
