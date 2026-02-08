<?php

namespace SocialDept\AtpOrm\Backlinks;

use SocialDept\AtpOrm\Cache\CacheKeyGenerator;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\Data\LinkSummary;

class BacklinkQuery
{
    protected string $subject;

    protected ?string $collection = null;

    protected ?string $path = null;

    /** @var array<string>|null */
    protected ?array $dids = null;

    protected int $limit = 16;

    protected bool $reversed = false;

    protected ?string $cursor = null;

    protected bool $bypassCache = false;

    protected ?int $customTtl = null;

    public function __construct(string $subject)
    {
        $this->subject = $subject;
    }

    public static function for(string $subject): static
    {
        return new static($subject);
    }

    /**
     * Set the source collection and path.
     *
     * Accepts either combined "collection:path" format or separate arguments.
     */
    public function source(string $collection, ?string $path = null): static
    {
        if ($path === null && str_contains($collection, ':')) {
            [$collection, $path] = explode(':', $collection, 2);
        }

        $this->collection = $collection;
        $this->path = $path;

        return $this;
    }

    /**
     * Filter results to specific DIDs.
     *
     * @param  string|array<string>  $dids
     */
    public function did(string|array $dids): static
    {
        $this->dids = is_array($dids) ? $dids : [$dids];

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = min($limit, 100);

        return $this;
    }

    public function reverse(): static
    {
        $this->reversed = true;

        return $this;
    }

    public function after(string $cursor): static
    {
        $this->cursor = $cursor;

        return $this;
    }

    public function fresh(): static
    {
        $this->bypassCache = true;

        return $this;
    }

    public function remember(int $ttl): static
    {
        $this->customTtl = $ttl;

        return $this;
    }

    // Convenience methods

    public function likes(): BacklinkCollection
    {
        return $this->source('app.bsky.feed.like', 'subject.uri')->get();
    }

    public function quotes(): BacklinkCollection
    {
        return $this->source('app.bsky.feed.post', 'embed.record.uri')->get();
    }

    public function replies(): BacklinkCollection
    {
        return $this->source('app.bsky.feed.post', 'reply.parent.uri')->get();
    }

    public function reposts(): BacklinkCollection
    {
        return $this->source('app.bsky.feed.repost', 'subject.uri')->get();
    }

    public function mentions(): BacklinkCollection
    {
        return $this->source(
            'app.bsky.feed.post',
            'facets[app.bsky.richtext.facet].features[app.bsky.richtext.facet#mention].did',
        )->get();
    }

    public function followers(): BacklinkCollection
    {
        return $this->source('app.bsky.graph.follow', 'subject')->get();
    }

    // Execution methods

    public function get(): BacklinkCollection
    {
        $sourceString = $this->buildSourceString();

        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();

        $params = [
            'limit' => $this->limit,
            'cursor' => $this->cursor,
            'reverse' => $this->reversed,
            'dids' => $this->dids,
        ];

        $cacheKey = $keyGen->forBacklinks($this->subject, $sourceString, $params);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $client = $this->constellation();
        $response = $client->getBacklinks(
            subject: $this->subject,
            source: $sourceString,
            dids: $this->dids,
            limit: $this->limit,
            reverse: $this->reversed,
            cursor: $this->cursor,
        );

        $result = new BacklinkCollection(
            items: $response->records,
            cursor: $response->cursor,
            total: $response->total,
        );

        $result->setQueryContext($this->buildQueryContext());

        $ttl = $this->resolveTtl();
        if ($ttl > 0) {
            $cache->put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    public function count(): int
    {
        $sourceString = $this->buildSourceString();

        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();
        $cacheKey = $keyGen->forBacklinkCount($this->subject, $sourceString);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $client = $this->constellation();
        $total = $client->getBacklinksCount($this->subject, $sourceString);

        $ttl = $this->resolveTtl();
        if ($ttl > 0) {
            $cache->put($cacheKey, $total, $ttl);
        }

        return $total;
    }

    public function all(): LinkSummary
    {
        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();
        $cacheKey = $keyGen->forAllLinks($this->subject);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $client = $this->constellation();
        $summary = $client->getAllLinks($this->subject);

        $ttl = $this->resolveTtl();
        if ($ttl > 0) {
            $cache->put($cacheKey, $summary, $ttl);
        }

        return $summary;
    }

    /**
     * Execute the query and hydrate results into full records via Slingshot.
     *
     * @param  class-string<\SocialDept\AtpOrm\RemoteRecord>  $remoteRecordClass
     */
    public function hydrate(string $remoteRecordClass): RemoteCollection
    {
        return $this->get()->hydrate($remoteRecordClass);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    protected function buildSourceString(): string
    {
        if ($this->path !== null) {
            return "{$this->collection}:{$this->path}";
        }

        return $this->collection ?? '';
    }

    protected function buildQueryContext(): array
    {
        return [
            'subject' => $this->subject,
            'collection' => $this->collection,
            'path' => $this->path,
            'dids' => $this->dids,
            'limit' => $this->limit,
            'reverse' => $this->reversed,
            'bypassCache' => $this->bypassCache,
            'customTtl' => $this->customTtl,
        ];
    }

    protected function resolveTtl(): int
    {
        if ($this->customTtl !== null) {
            return $this->customTtl;
        }

        return (int) config('atp-orm.cache.default_ttl', 300);
    }

    protected function constellation(): ConstellationClient
    {
        return app(ConstellationClient::class);
    }

    protected function cacheProvider(): CacheProvider
    {
        return app(CacheProvider::class);
    }

    protected function keyGenerator(): CacheKeyGenerator
    {
        return app(CacheKeyGenerator::class);
    }
}
