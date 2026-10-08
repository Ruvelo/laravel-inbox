<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Contracts;

use Ruvelo\Inbox\InboxMessage;

/**
 * A notification that describes how it looks in the inbox.
 *
 * When it's sent through the `database` channel, the message is stored with
 * the notification (merged over anything toDatabase() or toArray() returns),
 * so it doesn't need either of those.
 */
interface InboxNotification
{
    public function toInbox(object $notifiable): InboxMessage;
}
