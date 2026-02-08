<?php

namespace SocialDept\AtpOrm\Loader;

use SocialDept\AtpSupport\Microcosm\SlingshotClient;

class SlingshotLoader
{
    protected SlingshotClient $client;

    public function __construct(SlingshotClient $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch a record via Slingshot cache.
     *
     * @return array{uri: string, cid: string, value: array}
     */
    public function getRecord(string $did, string $collection, string $rkey): array
    {
        $response = $this->client->getRecord($did, $collection, $rkey);

        return [
            'uri' => $response->uri,
            'cid' => $response->cid,
            'value' => $response->value,
        ];
    }

    /**
     * Fetch a record by AT-URI via Slingshot cache.
     *
     * @return array{uri: string, cid: string, value: array}
     */
    public function getRecordByUri(string $uri): array
    {
        $response = $this->client->getRecordByUri($uri);

        return [
            'uri' => $response->uri,
            'cid' => $response->cid,
            'value' => $response->value,
        ];
    }
}
