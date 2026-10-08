<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Ruvelo\Inbox\Events\AllNotificationsRead;
use Ruvelo\Inbox\Events\NotificationDeleted;
use Ruvelo\Inbox\Events\NotificationRead;
use Ruvelo\Inbox\Events\NotificationUnread;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\Fixtures\TeammateJoined;
use Ruvelo\Inbox\Tests\TestCase;

class PhpApiTest extends TestCase
{
    public function test_unread_count_is_one_query_and_only_counts_your_own(): void
    {
        $maya = $this->user('Maya');
        $tom = $this->user('Tom');
        Notification::factory()->to($maya)->count(3)->create();
        Notification::factory()->to($maya)->read()->create();
        Notification::factory()->to($tom)->count(2)->create();

        DB::enableQueryLog();
        $count = Inbox::unreadCount($maya);

        $this->assertSame(3, $count);
        $this->assertCount(1, DB::getQueryLog());
        $this->assertStringContainsString('count(*)', strtolower(DB::getQueryLog()[0]['query']));
        $this->assertSame(2, Inbox::unreadCount($tom));
    }

    public function test_latest_is_newest_first_and_limited(): void
    {
        $maya = $this->user();
        foreach (['3 days ago', '1 hour ago', '2 days ago'] as $i => $ago) {
            Notification::factory()->to($maya)->message(['title' => "N{$i}"])->sentAt(Carbon::parse($ago))->create();
        }

        $this->assertSame(['N1', 'N2'], Inbox::latest($maya, 2)->map(fn (Notification $n) => $n->message()->title)->all());
        $this->assertCount(3, Inbox::latest($maya));
    }

    public function test_mark_read_and_unread_fire_events_only_on_change(): void
    {
        Event::fake();
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->create();

        $this->assertTrue(Inbox::markRead($notification));
        $this->assertFalse(Inbox::markRead($notification));
        $this->assertNotNull($notification->fresh()?->read_at);
        $this->assertFalse($notification->isUnread());
        Event::assertDispatchedTimes(NotificationRead::class, 1);

        $this->assertTrue(Inbox::markUnread($notification));
        $this->assertFalse(Inbox::markUnread($notification));
        $this->assertNull($notification->fresh()?->read_at);
        Event::assertDispatchedTimes(NotificationUnread::class, 1);
        Event::assertDispatched(NotificationUnread::class, fn (NotificationUnread $e) => $e->notification->is($notification));
    }

    public function test_it_accepts_laravels_own_notification_model(): void
    {
        Event::fake();
        $maya = $this->user();
        $maya->notify(new InvoicePaid);

        $notification = $maya->notifications()->firstOrFail();

        $this->assertSame('Invoice #1042 paid', Inbox::message($notification)->title);
        $this->assertTrue(Inbox::markRead($notification));
        $this->assertSame(0, Inbox::unreadCount($maya));
        Event::assertDispatched(NotificationRead::class, fn (NotificationRead $e) => $e->notification->id === $notification->id);
    }

    public function test_mark_all_read_touches_only_your_own(): void
    {
        Event::fake();
        $maya = $this->user('Maya');
        $tom = $this->user('Tom');
        Notification::factory()->to($maya)->count(3)->create();
        Notification::factory()->to($tom)->count(2)->create();

        $this->assertSame(3, Inbox::markAllRead($maya));
        $this->assertSame(0, Inbox::markAllRead($maya));

        $this->assertSame(0, Inbox::unreadCount($maya));
        $this->assertSame(2, Inbox::unreadCount($tom));
        Event::assertDispatchedTimes(AllNotificationsRead::class, 1);
        Event::assertDispatched(AllNotificationsRead::class, fn (AllNotificationsRead $e) => $e->count === 3 && $e->notifiable->is($maya));
    }

    public function test_delete(): void
    {
        Event::fake();
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->create();

        Inbox::delete($notification);

        $this->assertSame(0, Notification::query()->count());
        Event::assertDispatched(NotificationDeleted::class, fn (NotificationDeleted $e) => $e->notification->id === $notification->id);
    }

    public function test_find_never_returns_someone_elses(): void
    {
        $maya = $this->user('Maya');
        $tom = $this->user('Tom');
        $notification = Notification::factory()->to($maya)->create();

        $this->assertNotNull(Inbox::find($maya, $notification->id));
        $this->assertNull(Inbox::find($tom, $notification->id));
        $this->assertTrue($notification->isFor($maya));
        $this->assertFalse($notification->isFor($tom));
    }

    public function test_types_lists_what_you_have_with_labels(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        $maya->notify(new InvoicePaid('1043'));
        $maya->notify(new TeammateJoined);

        Inbox::preferences([InvoicePaid::class => 'Invoices paid']);

        $this->assertSame([
            InvoicePaid::class => 'Invoices paid',
            TeammateJoined::class => 'Teammate joined',
        ], Inbox::types($maya));
    }

    public function test_broadcast_channel_matches_laravels(): void
    {
        $maya = $this->user();

        $this->assertSame('Ruvelo.Inbox.Tests.Fixtures.User.'.$maya->id, Inbox::broadcastChannel($maya));
    }
}
