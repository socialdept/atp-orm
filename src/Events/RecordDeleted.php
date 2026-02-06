<?php

namespace SocialDept\AtpOrm\Events;

use SocialDept\AtpOrm\RemoteRecord;

class RecordDeleted
{
    public function __construct(
        public readonly RemoteRecord $record,
    ) {
    }
}
