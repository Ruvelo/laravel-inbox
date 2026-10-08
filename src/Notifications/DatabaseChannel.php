<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Notifications;

use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Notifications\Notification;
use Ruvelo\Inbox\Contracts\InboxNotification;

/**
 * Laravel's database channel, plus: notifications that implement
 * InboxNotification store their toInbox() message with their data, so they
 * need neither toDatabase() nor toArray(). Everything else is untouched.
 */
class DatabaseChannel extends LaravelDatabaseChannel
{
    /**
     * @param  mixed  $notifiable
     * @return array<array-key, mixed>
     */
    protected function getData($notifiable, Notification $notification)
    {
        if (! $notification instanceof InboxNotification || ! is_object($notifiable)) {
            return parent::getData($notifiable, $notification);
        }

        $data = method_exists($notification, 'toDatabase') || method_exists($notification, 'toArray')
            ? parent::getData($notifiable, $notification)
            : [];

        $message = array_filter($notification->toInbox($notifiable)->toArray(), fn (?string $value): bool => $value !== null);

        return array_merge($data, $message);
    }
}
