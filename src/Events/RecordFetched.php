<?php

namespace SocialDept\AtpOrm\Events;

use SocialDept\AtpOrm\RemoteRecord;

class RecordFetched
{
    public function __construct(
        public readonly RemoteRecord $record,
    ) {}
}
