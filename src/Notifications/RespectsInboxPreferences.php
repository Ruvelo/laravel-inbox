<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Notifications;

use Illuminate\Database\Eloquent\Model;
use Ruvelo\Inbox\Inbox;

/**
 * For notifications people can switch off on the preferences page.
 *
 *     use RespectsInboxPreferences;
 *
 *     public function via(object $notifiable): array
 *     {
 *         return $this->preferredChannels($notifiable, ['mail', 'database']);
 *     }
 *
 * The type is the notification's class; override inboxType() to share
 * one preference between several notifications.
 */
trait RespectsInboxPreferences
{
    /**
     * The key this notification's preferences are stored under.
     */
    public function inboxType(): string
    {
        return static::class;
    }

    /**
     * The channels from `$channels` this notifiable hasn't turned off.
     * Anonymous notifiables (Notification::route(...)) get them all.
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    protected function preferredChannels(object $notifiable, array $channels): array
    {
        if (! $notifiable instanceof Model) {
            return array_values($channels);
        }

        return Inbox::filterChannels($notifiable, $this->inboxType(), $channels);
    }
}
