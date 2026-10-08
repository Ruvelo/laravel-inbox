<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Fixtures;

use Illuminate\Notifications\Notification;

/**
 * Stores data in a shape the inbox doesn't know.
 */
class ExportReadyNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['file' => 'invoices-2026-09.csv', 'rows' => 1204];
    }
}
