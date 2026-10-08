<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when someone marks everything as read. `$count` is how many were
 * unread; it isn't fired when there was nothing to mark.
 */
final class AllNotificationsRead
{
    use Dispatchable;

    public function __construct(
        public readonly Model $notifiable,
        public readonly int $count,
    ) {}
}
