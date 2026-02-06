<?php

namespace SocialDept\AtpOrm\Concerns;

trait HasEvents
{
    protected function fireEvent(object $event): void
    {
        if (! config('atp-orm.events.enabled', true)) {
            return;
        }

        event($event);
    }
}
