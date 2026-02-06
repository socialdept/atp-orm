<?php

namespace SocialDept\AtpOrm\Exceptions;

use RuntimeException;

class RecordNotFoundException extends RuntimeException
{
    public function __construct(string $collection, string $did, string $rkey)
    {
        parent::__construct("Record not found: at://{$did}/{$collection}/{$rkey}");
    }
}
