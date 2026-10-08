<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Fixtures;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Ruvelo\Inbox\Notifications\RespectsInboxPreferences;

/**
 * Follows the array convention, and respects preferences.
 */
class InvoicePaid extends Notification
{
    use RespectsInboxPreferences;

    public function __construct(public string $number = '1042') {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->preferredChannels($notifiable, ['mail', 'database']);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Invoice #{$this->number} paid",
            'body' => 'Acme Corp paid €2,400.00.',
            'url' => "/invoices/{$this->number}",
            'icon' => '💸',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->line('Paid');
    }
}
