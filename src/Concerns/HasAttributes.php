<?php

namespace SocialDept\AtpOrm\Concerns;

use SocialDept\AtpSchema\Data\Data;

trait HasAttributes
{
    protected array $dirty = [];

    protected array $original = [];

    protected ?Data $record = null;

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->dirty)) {
            return $this->dirty[$key];
        }

        if ($this->record && property_exists($this->record, $key)) {
            return $this->record->{$key};
        }

        return $this->original[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->dirty[$key] = $value;

        return $this;
    }

    public function isDirty(?string $key = null): bool
    {
        if ($key !== null) {
            return array_key_exists($key, $this->dirty);
        }

        return ! empty($this->dirty);
    }

    public function isClean(?string $key = null): bool
    {
        return ! $this->isDirty($key);
    }

    public function getDirty(): array
    {
        return $this->dirty;
    }

    public function getOriginal(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->original[$key] ?? null;
        }

        return $this->original;
    }

    public function getMergedAttributes(): array
    {
        return array_merge($this->original, $this->dirty);
    }

    public function syncOriginal(): static
    {
        $this->original = $this->record ? $this->record->toArray() : [];
        $this->dirty = [];

        return $this;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->getAttribute($name) !== null;
    }
}
