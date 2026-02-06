<?php

namespace SocialDept\AtpOrm\Events;

use SocialDept\AtpOrm\RemoteRecord;

class RecordUpdated
{
    public function __construct(
        public readonly RemoteRecord $record,
    ) {
    }
}
