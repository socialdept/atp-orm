<?php

namespace SocialDept\AtpOrm\Support;

use Stringable;

class AtUri implements Stringable
{
    public function __construct(
        public readonly string $did,
        public readonly string $collection,
        public readonly string $rkey,
    ) {}

    public static function parse(string $uri): ?static
    {
        if (! preg_match('#^at://([^/]+)/([^/]+)/([^/]+)$#', $uri, $matches)) {
            return null;
        }

        return new static($matches[1], $matches[2], $matches[3]);
    }

    public static function make(string $did, string $collection, string $rkey): static
    {
        return new static($did, $collection, $rkey);
    }

    public function __toString(): string
    {
        return "at://{$this->did}/{$this->collection}/{$this->rkey}";
    }
}
