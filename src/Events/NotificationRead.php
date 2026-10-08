<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\Inbox\Models\Notification;

/**
 * Fired when a notification is marked as read, one at a time or by opening it.
 */
final class NotificationRead
{
    use Dispatchable;

    public function __construct(
        public readonly Notification $notification,
    ) {}
}
