<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\Inbox\Models\Notification;

/**
 * Fired when a read notification is marked as unread again.
 */
final class NotificationUnread
{
    use Dispatchable;

    public function __construct(
        public readonly Notification $notification,
    ) {}
}
