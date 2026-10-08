<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Ruvelo\Inbox\Database\Factories\NotificationFactory;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\InboxMessage;

/**
 * A row in Laravel's own `notifications` table, as the inbox sees it.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int|string $notifiable_id
 * @property array<array-key, mixed> $data
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Notification extends DatabaseNotification
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    public function getTable(): string
    {
        return config('inbox.notifications_table', 'notifications');
    }

    /**
     * Only this notifiable's notifications. Uses the (notifiable_type,
     * notifiable_id) index that Laravel's migration creates.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, Model $notifiable): void
    {
        $query->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, ?string $type): void
    {
        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }
    }

    /**
     * How this notification looks in the inbox.
     */
    public function message(): InboxMessage
    {
        return Inbox::message($this);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isFor(Model $notifiable): bool
    {
        return $this->notifiable_type === $notifiable->getMorphClass()
            && (string) $this->notifiable_id === (string) $notifiable->getKey();
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
