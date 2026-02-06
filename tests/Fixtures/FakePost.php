<?php

namespace SocialDept\AtpOrm\Tests\Fixtures;

use SocialDept\AtpOrm\RemoteRecord;

class FakePost extends RemoteRecord
{
    protected string $collection = 'app.bsky.feed.post';

    protected string $recordClass = FakePostData::class;

    protected int $cacheTtl = 300;
}
