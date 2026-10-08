<?php

declare(strict_types=1);

// The demo app's notifications: ordinary Laravel notifications, written the
// way an app already would. Most follow the toArray() convention; one
// describes itself with toInbox().

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Ruvelo\Inbox\Contracts\InboxNotification;
use Ruvelo\Inbox\InboxMessage;
use Ruvelo\Inbox\Notifications\RespectsInboxPreferences;

abstract class DemoNotification extends Notification
{
    use RespectsInboxPreferences;

    /** @param array<string, string> $data */
    public function __construct(public array $data) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->preferredChannels($notifiable, ['database']);
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}

class InvoicePaid extends DemoNotification {}
class PaymentFailed extends DemoNotification {}
class PayoutSent extends DemoNotification {}
class SubscriptionUpgraded extends DemoNotification {}
class TrialsEnding extends DemoNotification {}
class ExportReady extends DemoNotification {}
class CommentMention extends DemoNotification {}
class WebhookFailing extends DemoNotification {}
class NewSignIn extends DemoNotification {}

class TeammateJoined extends Notification implements InboxNotification
{
    public function __construct(public string $name, public string $role) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toInbox(object $notifiable): InboxMessage
    {
        return InboxMessage::make("{$this->name} joined Halyard")
            ->withBody("They accepted your invite and have the {$this->role} role.")
            ->withUrl('/settings/team')
            ->withActor($this->name);
    }
}
