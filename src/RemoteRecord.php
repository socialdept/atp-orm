<?php

namespace SocialDept\AtpOrm;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use SocialDept\AtpOrm\Concerns\HasAttributes;
use SocialDept\AtpOrm\Concerns\HasBacklinks;
use SocialDept\AtpOrm\Concerns\HasEvents;
use SocialDept\AtpOrm\Events\RecordCreated;
use SocialDept\AtpOrm\Events\RecordDeleted;
use SocialDept\AtpOrm\Events\RecordUpdated;
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;
use SocialDept\AtpOrm\Query\Builder;
use SocialDept\AtpSupport\AtUri;
use SocialDept\AtpSchema\Data\Data;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 */
abstract class RemoteRecord implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    use HasAttributes;
    use HasBacklinks;
    use HasEvents;

    protected string $collection;

    protected string $recordClass;

    protected int $cacheTtl = 0;

    protected ?string $did = null;

    protected ?string $rkey = null;

    protected ?string $uri = null;

    protected ?string $cid = null;

    protected bool $exists = false;

    protected ?string $authenticatedDid = null;

    public static function for(string $didOrHandle): Builder
    {
        return (new Builder(static::class))->for($didOrHandle);
    }

    public static function as(string $did): Builder
    {
        return (new Builder(static::class))->as($did);
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function getRecordClass(): string
    {
        return $this->recordClass;
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function getDid(): ?string
    {
        return $this->did;
    }

    public function setDid(?string $did): static
    {
        $this->did = $did;

        return $this;
    }

    public function getRkey(): ?string
    {
        return $this->rkey;
    }

    public function setRkey(?string $rkey): static
    {
        $this->rkey = $rkey;

        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(?string $uri): static
    {
        $this->uri = $uri;

        if ($uri) {
            $parsed = AtUri::parse($uri);
            if ($parsed) {
                $this->did = $parsed->did;
                $this->rkey = $parsed->rkey;
            }
        }

        return $this;
    }

    public function getCid(): ?string
    {
        return $this->cid;
    }

    public function setCid(?string $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function setExists(bool $exists): static
    {
        $this->exists = $exists;

        return $this;
    }

    public function getAuthenticatedDid(): ?string
    {
        return $this->authenticatedDid;
    }

    public function setAuthenticatedDid(?string $did): static
    {
        $this->authenticatedDid = $did;

        return $this;
    }

    public function setRecord(Data $record): static
    {
        $this->record = $record;
        $this->syncOriginal();

        return $this;
    }

    public function toDto(): ?Data
    {
        if ($this->isDirty()) {
            $class = $this->recordClass;

            return $class::fromArray($this->getMergedAttributes());
        }

        return $this->record;
    }

    public function save(): static
    {
        $authDid = $this->authenticatedDid;

        if (! $authDid) {
            throw new ReadOnlyException('save');
        }

        $client = \SocialDept\AtpClient\Facades\Atp::as($authDid);
        $class = $this->recordClass;
        $data = $class::fromArray($this->getMergedAttributes());
        $recordArray = $data->toRecord();

        if ($this->exists && $this->rkey) {
            $response = $client->atproto->repo->putRecord(
                collection: $this->collection,
                rkey: $this->rkey,
                record: $recordArray,
                swapRecord: $this->cid,
            );

            $this->uri = $response->uri;
            $this->cid = $response->cid;
            $this->record = $data;
            $this->syncOriginal();

            $this->invalidateCache();
            $this->fireEvent(new RecordUpdated($this));
        } else {
            $response = $client->atproto->repo->createRecord(
                collection: $this->collection,
                record: $recordArray,
                rkey: $this->rkey,
            );

            $this->uri = $response->uri;
            $this->cid = $response->cid;
            $this->exists = true;
            $this->record = $data;

            $parsed = AtUri::parse($response->uri);
            if ($parsed) {
                $this->did = $parsed->did;
                $this->rkey = $parsed->rkey;
            }

            $this->syncOriginal();

            $this->invalidateCache();
            $this->fireEvent(new RecordCreated($this));
        }

        return $this;
    }

    public function update(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this->save();
    }

    public function delete(): bool
    {
        $authDid = $this->authenticatedDid;

        if (! $authDid) {
            throw new ReadOnlyException('delete');
        }

        if (! $this->exists || ! $this->rkey) {
            return false;
        }

        $client = \SocialDept\AtpClient\Facades\Atp::as($authDid);

        $client->atproto->repo->deleteRecord(
            collection: $this->collection,
            rkey: $this->rkey,
            swapRecord: $this->cid,
        );

        $this->exists = false;

        $this->invalidateCache();
        $this->fireEvent(new RecordDeleted($this));

        return true;
    }

    public function fresh(): static
    {
        return static::for($this->did)->fresh()->find($this->rkey);
    }

    protected function invalidateCache(): void
    {
        if (! $this->did) {
            return;
        }

        $cacheProvider = app(\SocialDept\AtpOrm\Contracts\CacheProvider::class);
        $keyGenerator = app(\SocialDept\AtpOrm\Cache\CacheKeyGenerator::class);

        $cacheProvider->flush($keyGenerator->scopePrefix($this->collection, $this->did));
    }

    // Arrayable

    public function toArray(): array
    {
        return $this->getMergedAttributes();
    }

    // Jsonable

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    // JsonSerializable

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    // ArrayAccess

    public function offsetExists(mixed $offset): bool
    {
        return $this->getAttribute($offset) !== null;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->dirty[$offset]);
    }
}
