<?php

namespace SocialDept\AtpOrm\Support;

use SocialDept\AtpClient\Data\Responses\Atproto\Repo\ListRecordsResponse;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\RemoteRecord;
use SocialDept\AtpOrm\Events\RecordFetched;

class RecordHydrator
{
    /**
     * @param  class-string<RemoteRecord>  $class
     */
    public function hydrateOne(
        string $class,
        array $rawValue,
        string $uri,
        ?string $cid = null,
        ?string $authDid = null,
    ): RemoteRecord {
        $instance = new $class;
        $recordClass = $instance->getRecordClass();
        $record = $recordClass::fromArray($rawValue);

        $instance->setUri($uri);
        $instance->setCid($cid);
        $instance->setRecord($record);
        $instance->setExists(true);
        $instance->setAuthenticatedDid($authDid);

        if (config('atp-orm.events.enabled', true)) {
            event(new RecordFetched($instance));
        }

        return $instance;
    }

    /**
     * @param  class-string<RemoteRecord>  $class
     */
    public function hydrateMany(
        string $class,
        ListRecordsResponse $response,
        ?string $authDid = null,
    ): RemoteCollection {
        $items = $response->records->map(function (array $record) use ($class, $authDid) {
            return $this->hydrateOne(
                $class,
                $record['value'],
                $record['uri'],
                $record['cid'] ?? null,
                $authDid,
            );
        });

        return new RemoteCollection($items->all(), $response->cursor);
    }

    /**
     * @param  class-string<RemoteRecord>  $class
     * @param  array<array{rkey: string, cid: string, value: array}>  $records
     */
    public function hydrateFromRepo(
        string $class,
        array $records,
        string $did,
        ?string $authDid = null,
    ): RemoteCollection {
        $instance = new $class;
        $collection = $instance->getCollection();

        $items = [];
        foreach ($records as $record) {
            $uri = "at://{$did}/{$collection}/{$record['rkey']}";

            $items[] = $this->hydrateOne(
                $class,
                $record['value'],
                $uri,
                $record['cid'] ?? null,
                $authDid,
            );
        }

        return new RemoteCollection($items);
    }
}
