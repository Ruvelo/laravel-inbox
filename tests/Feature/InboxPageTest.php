<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Ruvelo\Inbox\Events\AllNotificationsRead;
use Ruvelo\Inbox\Events\NotificationDeleted;
use Ruvelo\Inbox\Events\NotificationRead;
use Ruvelo\Inbox\Events\NotificationUnread;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\Fixtures\TeammateJoined;
use Ruvelo\Inbox\Tests\TestCase;

class InboxPageTest extends TestCase
{
    public function test_the_inbox_groups_notifications_by_day(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->message(['title' => 'Fresh one', 'body' => 'Just now.', 'actor' => 'Tom Reyes'])->create();
        Notification::factory()->to($maya)->read()->message(['title' => 'Older one'])->sentAt(Carbon::now()->subDay())->create();
        Notification::factory()->to($maya)->message(['title' => 'Ancient one'])->sentAt(Carbon::parse('2025-03-14 10:00'))->create();

        $this->actingAs($maya)
            ->get('/inbox')
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSeeInOrder(['Today', 'Fresh one', 'Just now.', 'Tom Reyes', 'Yesterday', 'Older one', '14 March 2025', 'Ancient one'])
            ->assertSee('<strong>2 unread</strong>', false)
            ->assertSee('Mark as unread')
            ->assertSee('Mark all as read');
    }

    public function test_the_unread_tab(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->message(['title' => 'Unread one'])->create();
        Notification::factory()->to($maya)->read()->message(['title' => 'Read one'])->create();

        $this->actingAs($maya)
            ->get('/inbox?filter=unread')
            ->assertOk()
            ->assertSee('Unread one')
            ->assertDontSee('Read one');
    }

    public function test_filter_by_type(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        $maya->notify(new TeammateJoined);

        $this->actingAs($maya)
            ->get('/inbox?type='.urlencode(InvoicePaid::class))
            ->assertOk()
            ->assertSee('Invoice #1042 paid')
            ->assertDontSee('Tom Reyes joined your team')
            ->assertSee('<option value="'.e(InvoicePaid::class).'" selected>Invoice paid</option>', false);

        // A type you don't have is ignored rather than trusted.
        $this->get('/inbox?type=App%5CSomething%5CElse')
            ->assertOk()
            ->assertSee('Invoice #1042 paid')
            ->assertSee('Tom Reyes joined your team');
    }

    public function test_pagination(): void
    {
        config(['inbox.per_page' => 2]);
        $maya = $this->user();
        foreach (range(1, 5) as $i) {
            Notification::factory()->to($maya)->message(['title' => "Item {$i}"])->sentAt(Carbon::now()->subMinutes($i))->create();
        }

        $this->actingAs($maya)->get('/inbox')
            ->assertSee('Item 1')->assertSee('Item 2')->assertDontSee('Item 3')
            ->assertSee('Page 1 of 3')->assertSee('Older');

        $this->get('/inbox?page=3')->assertSee('Item 5')->assertDontSee('Item 4')->assertSee('Newer');
    }

    public function test_empty_states(): void
    {
        $this->actingAs($this->user());

        $this->get('/inbox')->assertOk()->assertSee('No notifications yet');
        $this->get('/inbox?filter=unread')->assertOk()->assertSee('You’re all caught up');
    }

    public function test_everything_is_escaped(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->message([
            'title' => '<script>alert("t")</script>',
            'body' => '<img src=x onerror=alert(1)>',
            'actor' => '<b>Eve</b>',
            'url' => 'javascript:alert(1)',
        ])->create();

        $this->actingAs($maya)->get('/inbox')
            ->assertOk()
            ->assertDontSee('<script>alert("t")</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('<b>Eve</b>', false)
            ->assertDontSee('javascript:', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_opening_marks_read_and_follows_the_link(): void
    {
        Event::fake();
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        $notification = Notification::query()->firstOrFail();

        $this->actingAs($maya)->get("/inbox/{$notification->id}/open")->assertRedirect('/invoices/1042');

        $this->assertSame(0, Inbox::unreadCount($maya));
        Event::assertDispatched(NotificationRead::class);
    }

    public function test_opening_follows_absolute_links_and_falls_back_to_the_inbox(): void
    {
        $maya = $this->user();
        $external = Notification::factory()->to($maya)->message(['title' => 'Receipt', 'url' => 'https://pay.example.com/r/1'])->create();
        $unsafe = Notification::factory()->to($maya)->message(['title' => 'Bad', 'url' => 'javascript:alert(1)'])->create();
        $none = Notification::factory()->to($maya)->message(['title' => 'No link'])->create();

        $this->actingAs($maya);
        $this->get("/inbox/{$external->id}/open")->assertRedirect('https://pay.example.com/r/1');
        $this->get("/inbox/{$unsafe->id}/open")->assertRedirect(route('inbox.index'));
        $this->get("/inbox/{$none->id}/open")->assertRedirect(route('inbox.index'));
    }

    public function test_mark_read_unread_and_delete_from_the_page(): void
    {
        Event::fake();
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->create();
        $this->actingAs($maya)->from('/inbox');

        $this->post("/inbox/{$notification->id}/read")->assertRedirect('/inbox')->assertSessionHas('inbox.status', 'Marked as read.');
        $this->assertFalse($notification->fresh()?->isUnread());

        $this->post("/inbox/{$notification->id}/unread")->assertRedirect('/inbox')->assertSessionHas('inbox.status', 'Marked as unread.');
        $this->assertTrue($notification->fresh()?->isUnread());

        $this->delete("/inbox/{$notification->id}")->assertRedirect('/inbox')->assertSessionHas('inbox.status', 'Notification deleted.');
        $this->assertNull($notification->fresh());

        Event::assertDispatched(NotificationRead::class);
        Event::assertDispatched(NotificationUnread::class);
        Event::assertDispatched(NotificationDeleted::class);
    }

    public function test_mark_all_as_read(): void
    {
        Event::fake();
        $maya = $this->user();
        Notification::factory()->to($maya)->count(2)->create();

        $this->actingAs($maya)->post('/inbox/read-all')
            ->assertRedirect(route('inbox.index'))
            ->assertSessionHas('inbox.status', 'Marked 2 notifications as read.');
        $this->post('/inbox/read-all')->assertSessionHas('inbox.status', 'Nothing to mark: you’re all caught up.');

        Event::assertDispatchedTimes(AllNotificationsRead::class, 1);
    }

    public function test_the_bell_uses_json_for_background_updates(): void
    {
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->create();
        Notification::factory()->to($maya)->create();
        $this->actingAs($maya);

        $this->getJson('/inbox/count')->assertExactJson(['unread' => 2])->assertHeader('Cache-Control');
        $this->postJson("/inbox/{$notification->id}/read")->assertExactJson(['unread' => 1]);
        $this->postJson('/inbox/read-all')->assertExactJson(['unread' => 0]);
    }

    public function test_the_bell_list_fragment(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->count(3)->create();
        $maya->notify(new InvoicePaid);

        $this->actingAs($maya)->get('/inbox/bell?limit=2')
            ->assertOk()
            ->assertHeader('X-Inbox-Unread', '4')
            ->assertSee('data-inbox-item', false)
            ->assertDontSee('<html', false);

        $this->assertSame(2, preg_match_all('/data-inbox-item\s+data-read-url/', (string) $this->get('/inbox/bell?limit=2')->getContent()));
    }

    public function test_someone_elses_notifications_are_a_404_everywhere(): void
    {
        $maya = $this->user('Maya');
        $tom = $this->user('Tom');
        $notification = Notification::factory()->to($maya)->message(['title' => 'Maya only'])->create();
        $this->actingAs($tom);

        $this->get("/inbox/{$notification->id}/open")->assertNotFound();
        $this->post("/inbox/{$notification->id}/read")->assertNotFound();
        $this->post("/inbox/{$notification->id}/unread")->assertNotFound();
        $this->delete("/inbox/{$notification->id}")->assertNotFound();
        $this->get('/inbox')->assertDontSee('Maya only');
        $this->post('/inbox/read-all');

        $fresh = $notification->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->isUnread());
        $this->assertSame(1, Inbox::unreadCount($maya));
    }

    public function test_the_page_can_use_the_apps_layout(): void
    {
        View::addNamespace('host', __DIR__.'/../Fixtures/views');
        config(['inbox.layout' => 'host::app', 'inbox.section' => 'main']);

        $this->actingAs($this->user())->get('/inbox')
            ->assertOk()
            ->assertSee('<div id="host-app">', false)
            ->assertSee('<title>Notifications</title>', false)
            ->assertSee('No notifications yet')
            ->assertDontSee('Powered by');
    }
}
