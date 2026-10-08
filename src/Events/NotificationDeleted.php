<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\Inbox\Models\Notification;

/**
 * Fired after a notification is deleted from the inbox. The model is no longer in the database.
 */
final class NotificationDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly Notification $notification,
    ) {}
}
