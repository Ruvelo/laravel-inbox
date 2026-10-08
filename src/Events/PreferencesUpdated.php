<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when someone changes their notification preferences, with only the
 * choices that actually changed: [type => [channel => enabled]].
 */
final class PreferencesUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, array<string, bool>>  $changes
     */
    public function __construct(
        public readonly Model $notifiable,
        public readonly array $changes,
    ) {}
}
