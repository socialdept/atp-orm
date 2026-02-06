<?php

namespace SocialDept\AtpOrm\Query;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpOrm\Cache\CacheKeyGenerator;
use SocialDept\AtpOrm\Contracts\CacheProvider;
use SocialDept\AtpOrm\Events\RecordCreated;
use SocialDept\AtpOrm\Exceptions\ReadOnlyException;
use SocialDept\AtpOrm\Exceptions\RecordNotFoundException;
use SocialDept\AtpOrm\RemoteCollection;
use SocialDept\AtpOrm\RemoteRecord;
use SocialDept\AtpOrm\Support\AtUri;
use SocialDept\AtpOrm\Support\RecordHydrator;
use SocialDept\AtpResolver\Facades\Resolver;
use SocialDept\AtpResolver\Support\Identity;

class Builder
{
    /** @var class-string<RemoteRecord> */
    protected string $remoteRecordClass;

    protected ?string $did = null;

    protected ?string $authenticatedDid = null;

    protected ?int $limit = null;

    protected ?string $cursor = null;

    protected bool $reverse = false;

    protected bool $bypassCache = false;

    protected ?int $customTtl = null;

    protected bool $useRepo = false;

    /** @var array<string, AtpClient> */
    protected static array $clientCache = [];

    /**
     * @param  class-string<RemoteRecord>  $remoteRecordClass
     */
    public function __construct(string $remoteRecordClass)
    {
        $this->remoteRecordClass = $remoteRecordClass;
    }

    public function for(string $didOrHandle): static
    {
        if (Identity::isDid($didOrHandle)) {
            $this->did = $didOrHandle;
        } else {
            $this->did = Resolver::handleToDid($didOrHandle);
        }

        return $this;
    }

    public function as(string $did): static
    {
        $this->authenticatedDid = $did;
        $this->did = $did;

        return $this;
    }

    public function setAuthenticatedDid(?string $did): static
    {
        $this->authenticatedDid = $did;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = min($limit, config('atp-orm.query.max_limit', 100));

        return $this;
    }

    public function after(string $cursor): static
    {
        $this->cursor = $cursor;

        return $this;
    }

    public function reverse(): static
    {
        $this->reverse = true;

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

    public function fromRepo(): static
    {
        $this->useRepo = true;

        return $this;
    }

    public function get(): RemoteCollection
    {
        $instance = new $this->remoteRecordClass;
        $collection = $instance->getCollection();

        if ($this->useRepo) {
            return $this->getViaRepo($collection);
        }

        return $this->getViaList($collection);
    }

    public function find(string $rkey): ?RemoteRecord
    {
        $instance = new $this->remoteRecordClass;
        $collection = $instance->getCollection();

        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();
        $cacheKey = $keyGen->forRecord($collection, $this->did, $rkey);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $pds = $this->resolvePds();
        $client = $this->publicClient($pds);

        try {
            $response = $client->atproto->repo->getRecord(
                $this->did,
                $collection,
                $rkey,
            );
        } catch (\Throwable) {
            return null;
        }

        $hydrator = $this->hydrator();
        $record = $hydrator->hydrateOne(
            $this->remoteRecordClass,
            $response->value,
            $response->uri,
            $response->cid,
            $this->authenticatedDid,
        );

        $ttl = $this->resolveTtl($collection);
        if ($ttl > 0) {
            $cache->put($cacheKey, $record, $ttl);
        }

        return $record;
    }

    public function findOrFail(string $rkey): RemoteRecord
    {
        $record = $this->find($rkey);

        if (! $record) {
            $instance = new $this->remoteRecordClass;
            throw new RecordNotFoundException($instance->getCollection(), $this->did, $rkey);
        }

        return $record;
    }

    public function findByUri(string $uri): ?RemoteRecord
    {
        $parsed = AtUri::parse($uri);

        if (! $parsed) {
            return null;
        }

        return $this->for($parsed->did)->find($parsed->rkey);
    }

    public function create(array $attributes, ?string $rkey = null): RemoteRecord
    {
        if (! $this->authenticatedDid) {
            throw new ReadOnlyException('create');
        }

        $instance = new $this->remoteRecordClass;
        $collection = $instance->getCollection();
        $recordClass = $instance->getRecordClass();

        $data = $recordClass::fromArray($attributes);
        $recordArray = $data->toRecord();

        $client = Atp::as($this->authenticatedDid);

        $response = $client->atproto->repo->createRecord(
            collection: $collection,
            record: $recordArray,
            rkey: $rkey,
        );

        $hydrator = $this->hydrator();
        $record = $hydrator->hydrateOne(
            $this->remoteRecordClass,
            $attributes,
            $response->uri,
            $response->cid,
            $this->authenticatedDid,
        );

        $this->invalidateScope($collection);

        if (config('atp-orm.events.enabled', true)) {
            event(new RecordCreated($record));
        }

        return $record;
    }

    public function invalidate(): void
    {
        $instance = new $this->remoteRecordClass;
        $collection = $instance->getCollection();

        $this->invalidateScope($collection);
    }

    protected function getViaList(string $collection): RemoteCollection
    {
        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();

        $params = [
            'limit' => $this->limit ?? config('atp-orm.query.default_limit', 50),
            'cursor' => $this->cursor,
            'reverse' => $this->reverse,
        ];

        $cacheKey = $keyGen->forList($collection, $this->did, $params);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $pds = $this->resolvePds();
        $client = $this->publicClient($pds);

        $response = $client->atproto->repo->listRecords(
            repo: $this->did,
            collection: $collection,
            limit: $params['limit'],
            cursor: $params['cursor'],
            reverse: $params['reverse'],
        );

        $hydrator = $this->hydrator();
        $result = $hydrator->hydrateMany(
            $this->remoteRecordClass,
            $response,
            $this->authenticatedDid,
        );

        $result->setQueryContext($this->buildQueryContext());

        $ttl = $this->resolveTtl($collection);
        if ($ttl > 0) {
            $cache->put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    protected function getViaRepo(string $collection): RemoteCollection
    {
        if (! class_exists(\SocialDept\AtpOrm\Loader\RepoLoader::class)) {
            return $this->getViaList($collection);
        }

        $loader = app(\SocialDept\AtpOrm\Loader\RepoLoader::class);

        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();
        $cacheKey = $keyGen->forRepo($collection, $this->did);

        if (! $this->bypassCache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $records = $loader->load($this->did, $collection);

        $hydrator = $this->hydrator();
        $result = $hydrator->hydrateFromRepo(
            $this->remoteRecordClass,
            $records,
            $this->did,
            $this->authenticatedDid,
        );

        $result->setQueryContext($this->buildQueryContext());

        $ttl = $this->resolveTtl($collection);
        if ($ttl > 0) {
            $cache->put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    protected function resolvePds(): string
    {
        $pds = Resolver::resolvePds($this->did);

        return $pds ?? config('atp-orm.pds.public_service', 'https://public.api.bsky.app');
    }

    protected function publicClient(string $pds): AtpClient
    {
        return static::$clientCache[$pds] ??= Atp::public($pds);
    }

    protected function resolveTtl(string $collection): int
    {
        if ($this->customTtl !== null) {
            return $this->customTtl;
        }

        $instance = new $this->remoteRecordClass;
        $modelTtl = $instance->getCacheTtl();

        if ($modelTtl > 0) {
            return $modelTtl;
        }

        $perCollection = config("atp-orm.cache.ttls.{$collection}");

        if ($perCollection !== null) {
            return (int) $perCollection;
        }

        return (int) config('atp-orm.cache.default_ttl', 300);
    }

    protected function invalidateScope(string $collection): void
    {
        $cache = $this->cacheProvider();
        $keyGen = $this->keyGenerator();
        $cache->flush($keyGen->scopePrefix($collection, $this->did));
    }

    protected function buildQueryContext(): array
    {
        return [
            'class' => $this->remoteRecordClass,
            'did' => $this->did,
            'authenticatedDid' => $this->authenticatedDid,
            'limit' => $this->limit,
            'reverse' => $this->reverse,
            'bypassCache' => $this->bypassCache,
            'customTtl' => $this->customTtl,
        ];
    }

    protected function cacheProvider(): CacheProvider
    {
        return app(CacheProvider::class);
    }

    protected function keyGenerator(): CacheKeyGenerator
    {
        return app(CacheKeyGenerator::class);
    }

    protected function hydrator(): RecordHydrator
    {
        return app(RecordHydrator::class);
    }
}
