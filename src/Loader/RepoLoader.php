<?php

namespace SocialDept\AtpOrm\Loader;

use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpResolver\Facades\Resolver;
use SocialDept\AtpSignals\CAR\BlockReader;
use SocialDept\AtpSignals\CAR\RecordExtractor;
use SocialDept\AtpSignals\Core\CBOR;
use SocialDept\AtpSignals\Core\CID;

class RepoLoader
{
    /**
     * Load all records for a collection from a repo CAR export.
     *
     * @return array<int, array{rkey: string, cid: string, value: array}>
     */
    public function load(string $did, string $collection): array
    {
        $pds = Resolver::resolvePds($did);

        if (! $pds) {
            $pds = config('atp-orm.pds.public_service', 'https://public.api.bsky.app');
        }

        $client = Atp::public($pds);
        $response = $client->atproto->sync->getRepo($did);

        $carData = $response->body();

        return $this->parseCarForCollection($carData, $did, $collection);
    }

    /**
     * @return array<int, array{rkey: string, cid: string, value: array}>
     */
    protected function parseCarForCollection(string $carData, string $did, string $collection): array
    {
        $blockReader = new BlockReader($carData);
        $blocks = $blockReader->getBlockMap();

        $rootCid = $this->findMstRoot($blocks);

        if (! $rootCid) {
            return [];
        }

        $extractor = new RecordExtractor($blocks, $did);
        $records = [];

        foreach ($extractor->extractRecords($rootCid) as $path => $record) {
            $parts = explode('/', $path, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$recordCollection, $rkey] = $parts;

            if ($recordCollection !== $collection) {
                continue;
            }

            $records[] = [
                'rkey' => $rkey,
                'cid' => $record['cid'],
                'value' => $record['value'],
            ];
        }

        return $records;
    }

    protected function findMstRoot(array $blocks): ?CID
    {
        $firstBlock = reset($blocks);

        if ($firstBlock === false) {
            return null;
        }

        $commit = CBOR::decode($firstBlock);

        if (! is_array($commit)) {
            return null;
        }

        if (isset($commit['data']) && $commit['data'] instanceof CID) {
            return $commit['data'];
        }

        return null;
    }
}
