<?php

namespace SocialDept\AtpOrm\Events;

use SocialDept\AtpOrm\RemoteRecord;

class RecordCreated
{
    public function __construct(
        public readonly RemoteRecord $record,
    ) {}
}
