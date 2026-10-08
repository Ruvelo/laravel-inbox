<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Ruvelo\Inbox\Contracts\InboxNotification;
use Ruvelo\Inbox\InboxMessage;

/**
 * Describes itself with toInbox() and has no toArray().
 */
class TeammateJoined extends Notification implements InboxNotification
{
    public function __construct(public string $name = 'Tom Reyes') {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toInbox(object $notifiable): InboxMessage
    {
        return InboxMessage::make("{$this->name} joined your team")
            ->withUrl('/team')
            ->withActor($this->name);
    }
}
