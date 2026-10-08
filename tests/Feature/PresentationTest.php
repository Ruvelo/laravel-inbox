<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Carbon;
use Ruvelo\Inbox\Contracts\InboxNotification;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\InboxMessage;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Tests\Fixtures\ExportReadyNotification;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\Fixtures\TeammateJoined;
use Ruvelo\Inbox\Tests\TestCase;

class PresentationTest extends TestCase
{
    public function test_existing_notifications_follow_the_array_convention(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);

        $message = Notification::query()->firstOrFail()->message();

        $this->assertSame('Invoice #1042 paid', $message->title);
        $this->assertSame('Acme Corp paid €2,400.00.', $message->body);
        $this->assertSame('/invoices/1042', $message->url);
        $this->assertSame('💸', $message->icon);
    }

    public function test_inbox_notifications_store_their_message_without_to_array(): void
    {
        $maya = $this->user();
        $maya->notify(new TeammateJoined('Kenji Mori'));

        $notification = Notification::query()->firstOrFail();

        $this->assertSame(['title' => 'Kenji Mori joined your team', 'url' => '/team', 'actor' => 'Kenji Mori'], $notification->data);
        $this->assertSame('KM', $notification->message()->initials());
    }

    public function test_the_inbox_message_is_merged_over_to_array(): void
    {
        $maya = $this->user();
        $maya->notify(new class extends \Illuminate\Notifications\Notification implements InboxNotification
        {
            /** @return list<string> */
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            /** @return array<string, mixed> */
            public function toArray(object $notifiable): array
            {
                return ['invoice_id' => 7, 'title' => 'Overwritten'];
            }

            public function toInbox(object $notifiable): InboxMessage
            {
                return InboxMessage::make('Payment failed', 'Card declined.');
            }
        });

        $this->assertSame(['invoice_id' => 7, 'title' => 'Payment failed', 'body' => 'Card declined.'], Notification::query()->firstOrFail()->data);
    }

    public function test_unknown_shapes_fall_back_to_the_humanised_class_name(): void
    {
        $maya = $this->user();
        $maya->notify(new ExportReadyNotification);

        $message = Notification::query()->firstOrFail()->message();

        $this->assertSame('Export ready', $message->title);
        $this->assertNull($message->body);
        $this->assertNull($message->url);
    }

    public function test_a_registered_presenter_wins(): void
    {
        $maya = $this->user();
        $maya->notify(new ExportReadyNotification);

        Inbox::present(ExportReadyNotification::class, fn (array $data, Notification $notification) => InboxMessage::make(
            "Your export is ready ({$data['rows']} rows)",
            url: '/exports/'.$data['file'],
            icon: '📦',
        ));

        $message = Notification::query()->firstOrFail()->message();
        $this->assertSame('Your export is ready (1204 rows)', $message->title);
        $this->assertSame('/exports/invoices-2026-09.csv', $message->url);

        Inbox::present(ExportReadyNotification::class, null);
        $this->assertSame('Export ready', Notification::query()->firstOrFail()->message()->title);
    }

    public function test_presenters_can_be_registered_as_a_map(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        $maya->notify(new TeammateJoined);

        Inbox::present([
            InvoicePaid::class => fn (array $data) => InboxMessage::make('Paid!'),
            TeammateJoined::class => fn (array $data) => InboxMessage::make('Joined!'),
        ]);

        $this->assertEqualsCanonicalizing(['Paid!', 'Joined!'], Inbox::latest($maya)->map(fn (Notification $n) => $n->message()->title)->all());
    }

    public function test_humanise(): void
    {
        $this->assertSame('Invoice paid', Inbox::humanise('App\\Notifications\\InvoicePaid'));
        $this->assertSame('Payment failed', Inbox::humanise('App\\Notifications\\PaymentFailedNotification'));
        $this->assertSame('Billing invoice paid', Inbox::humanise('billing.invoice_paid'));
        $this->assertSame('Notification', Inbox::humanise('Notification'));
    }

    public function test_type_labels_use_preference_labels_first(): void
    {
        Inbox::preferences([InvoicePaid::class => ['label' => 'Invoices you’re paid']]);

        $this->assertSame('Invoices you’re paid', Inbox::typeLabel(InvoicePaid::class));
        $this->assertSame('Teammate joined', Inbox::typeLabel(TeammateJoined::class));
    }

    public function test_grouping_by_day_in_the_apps_timezone(): void
    {
        // Like an app booted with this timezone: PHP's default zone matches
        // app.timezone, and timestamps are stored in it. Switching only the
        // config at runtime reads stored dates differently across Laravel
        // 13.x releases, so set both.
        $previous = date_default_timezone_get();
        date_default_timezone_set('Pacific/Auckland');
        config(['app.timezone' => 'Pacific/Auckland']);
        Carbon::setTestNow(Carbon::parse('2026-10-08 12:00', 'Pacific/Auckland'));
        $maya = $this->user();

        foreach (['2026-10-08 09:00', '2026-10-08 00:30', '2026-10-07 23:30', '2026-10-01 10:00', '2025-12-24 10:00'] as $at) {
            Notification::factory()->to($maya)->sentAt(Carbon::parse($at, 'Pacific/Auckland'))->create();
        }

        $groups = Inbox::groupByDay(Inbox::latest($maya, 10));

        $this->assertSame(['Today', 'Yesterday', 'Thursday 1 October', '24 December 2025'], array_keys($groups));
        $this->assertCount(2, $groups['Today']);

        config(['inbox.timezone' => 'UTC']);
        $this->assertSame('Yesterday', Inbox::dayLabel(Carbon::parse('2026-10-07 10:00', 'Pacific/Auckland')));
        Carbon::setTestNow();
        date_default_timezone_set($previous);
    }
}
