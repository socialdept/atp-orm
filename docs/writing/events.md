# Events

Part of the [atp-orm docs](../introduction.md#documentation). The Laravel events fired when records are fetched and written.

| Event | Fired when |
|-------|------------|
| `RecordFetched` | A record is built from a response. |
| `RecordCreated` | A record is created, by `create()` or by `save()` on a new record. |
| `RecordUpdated` | An existing record is saved. |
| `RecordDeleted` | A record is deleted. |

Each event lives in `SocialDept\AtpOrm\Events` and carries the record as `$event->record`:

```php
use Illuminate\Support\Facades\Event;
use SocialDept\AtpOrm\Events\RecordCreated;

Event::listen(RecordCreated::class, function (RecordCreated $event) {
    logger()->info('Record created', [
        'uri' => $event->record->getUri(),
    ]);
});
```

`RecordFetched` fires for each record that `find()`, a listing, a CAR export or a hydrated backlink result builds, and for the record `create()` returns. Results served from the cache fire nothing.

Turn the events off in the config:

```php
// config/atp-orm.php
'events' => [
    'enabled' => false,
],
```
