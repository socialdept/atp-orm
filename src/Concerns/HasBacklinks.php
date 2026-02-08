<?php

namespace SocialDept\AtpOrm\Concerns;

use SocialDept\AtpOrm\Backlinks\BacklinkQuery;

trait HasBacklinks
{
    public function backlinks(): BacklinkQuery
    {
        return new BacklinkQuery($this->getUri());
    }
}
