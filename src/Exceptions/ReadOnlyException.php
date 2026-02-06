<?php

namespace SocialDept\AtpOrm\Exceptions;

use RuntimeException;

class ReadOnlyException extends RuntimeException
{
    public function __construct(string $operation = 'write')
    {
        parent::__construct("Cannot {$operation} without an authenticated DID. Use ::as(\$did) for write operations.");
    }
}
