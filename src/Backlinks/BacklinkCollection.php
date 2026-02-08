<?php

namespace SocialDept\AtpOrm\Backlinks;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\Support\RecordHydrator;
use SocialDept\AtpSupport\Microcosm\Data\BacklinkReference;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;
use Traversable;

/**
 * @implements IteratorAggregate<int, BacklinkReference>
 */
class BacklinkCollection implements Countable, IteratorAggregate
{
    protected Collection $items;

    protected ?string $cursor;

    protected int $total;

    protected ?array $queryContext = null;

    /**
     * @param  array<BacklinkReference>  $items
     */
    public function __construct(array $items = [], ?string $cursor = null, int $total = 0)
    {
        $this->items = collect($items);
        $this->cursor = $cursor;
        $this->total = $total;
    }

    public function setQueryContext(array $context): static
    {
        $this->queryContext = $context;

        return $this;
    }

    public function cursor(): ?string
    {
        return $this->cursor;
    }

    public function hasMorePages(): bool
    {
        return $this->cursor !== null;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function nextPage(): ?static
    {
        if (! $this->hasMorePages() || ! $this->queryContext) {
            return null;
        }

        $query = new BacklinkQuery($this->queryContext['subject']);

        if ($this->queryContext['collection'] && $this->queryContext['path']) {
            $query->source($this->queryContext['collection'], $this->queryContext['path']);
        } elseif ($this->queryContext['collection']) {
            $query->source($this->queryContext['collection']);
        }

        if ($this->queryContext['dids'] ?? null) {
            $query->did($this->queryContext['dids']);
        }

        $query->limit($this->queryContext['limit'] ?? 16);

        if (! empty($this->queryContext['reverse'])) {
            $query->reverse();
        }

        if ($this->queryContext['customTtl'] ?? null) {
            $query->remember($this->queryContext['customTtl']);
        }

        if (! empty($this->queryContext['bypassCache'])) {
            $query->fresh();
        }

        return $query->after($this->cursor)->get();
    }

    /**
     * Hydrate backlink references into full RemoteRecord instances via Slingshot.
     *
     * @param  class-string<\SocialDept\AtpOrm\RemoteRecord>  $remoteRecordClass
     */
    public function hydrate(string $remoteRecordClass): RemoteCollection
    {
        if ($this->items->isEmpty()) {
            return new RemoteCollection();
        }

        $slingshot = app(SlingshotClient::class);
        $hydrator = app(RecordHydrator::class);

        $records = [];
        foreach ($this->items as $ref) {
            try {
                $response = $slingshot->getRecord($ref->did, $ref->collection, $ref->rkey);

                $records[] = $hydrator->hydrateOne(
                    $remoteRecordClass,
                    $response->value,
                    $response->uri,
                    $response->cid,
                );
            } catch (\Throwable) {
                continue;
            }
        }

        return new RemoteCollection($records, $this->cursor);
    }

    public function first(): ?BacklinkReference
    {
        return $this->items->first();
    }

    public function last(): ?BacklinkReference
    {
        return $this->items->last();
    }

    /**
     * @return Collection<int, mixed>
     */
    public function map(callable $callback): Collection
    {
        return $this->items->map($callback);
    }

    public function filter(?callable $callback = null): static
    {
        $filtered = new static(
            $this->items->filter($callback)->values()->all(),
            $this->cursor,
            $this->total,
        );
        $filtered->queryContext = $this->queryContext;

        return $filtered;
    }

    public function each(callable $callback): static
    {
        $this->items->each($callback);

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Get all URIs from the backlink references.
     *
     * @return Collection<int, string>
     */
    public function uris(): Collection
    {
        return $this->items->map(fn (BacklinkReference $ref) => $ref->uri());
    }

    /**
     * Get all unique DIDs from the backlink references.
     *
     * @return Collection<int, string>
     */
    public function dids(): Collection
    {
        return $this->items->map(fn (BacklinkReference $ref) => $ref->did)->unique()->values();
    }

    public function toArray(): array
    {
        return $this->items->map(fn (BacklinkReference $ref) => [
            'did' => $ref->did,
            'collection' => $ref->collection,
            'rkey' => $ref->rkey,
            'uri' => $ref->uri(),
        ])->all();
    }

    public function toCollection(): Collection
    {
        return $this->items;
    }

    public function all(): array
    {
        return $this->items->all();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items->all());
    }
}
