<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One explicit choice: this person does (or doesn't) want this notification
 * type on this channel. No row means the type's default applies.
 *
 * @property int $id
 * @property string $notifiable_type
 * @property int|string $notifiable_id
 * @property string $type
 * @property string $channel
 * @property bool $enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Preference extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function getTable(): string
    {
        return self::tableName();
    }

    public static function tableName(): string
    {
        return config('inbox.table_prefix', 'inbox_').'preferences';
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, Model $notifiable): void
    {
        $query->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', (string) $notifiable->getKey());
    }
}
