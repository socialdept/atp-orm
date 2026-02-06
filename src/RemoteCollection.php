<?php

namespace SocialDept\AtpOrm;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, RemoteRecord>
 */
class RemoteCollection implements Countable, IteratorAggregate
{
    protected Collection $items;

    protected ?string $cursor;

    protected ?array $queryContext = null;

    /**
     * @param  array<RemoteRecord>  $items
     */
    public function __construct(array $items = [], ?string $cursor = null)
    {
        $this->items = collect($items);
        $this->cursor = $cursor;
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

    public function nextPage(): ?static
    {
        if (! $this->hasMorePages() || ! $this->queryContext) {
            return null;
        }

        $builderClass = $this->queryContext['class'];
        $builder = new \SocialDept\AtpOrm\Query\Builder($builderClass);

        $builder->for($this->queryContext['did']);

        if (isset($this->queryContext['authenticatedDid'])) {
            $builder->setAuthenticatedDid($this->queryContext['authenticatedDid']);
        }

        if (isset($this->queryContext['limit'])) {
            $builder->limit($this->queryContext['limit']);
        }

        if (! empty($this->queryContext['reverse'])) {
            $builder->reverse();
        }

        if (isset($this->queryContext['customTtl'])) {
            $builder->remember($this->queryContext['customTtl']);
        }

        if (! empty($this->queryContext['bypassCache'])) {
            $builder->fresh();
        }

        return $builder->after($this->cursor)->get();
    }

    public function first(): ?RemoteRecord
    {
        return $this->items->first();
    }

    public function last(): ?RemoteRecord
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
        $filtered = new static($this->items->filter($callback)->values()->all(), $this->cursor);
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
     * @return Collection<int, mixed>
     */
    public function pluck(string $key): Collection
    {
        return $this->items->map(fn (RemoteRecord $record) => $record->getAttribute($key));
    }

    public function toArray(): array
    {
        return $this->items->map(fn (RemoteRecord $record) => $record->toArray())->all();
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
