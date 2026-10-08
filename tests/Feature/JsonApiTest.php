<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Ruvelo\Inbox\Events\NotificationDeleted;
use Ruvelo\Inbox\Events\PreferencesUpdated;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\Fixtures\TeammateJoined;
use Ruvelo\Inbox\Tests\TestCase;

class JsonApiTest extends TestCase
{
    public function test_list(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        Carbon::setTestNow(Carbon::now()->addMinute());
        $maya->notify(new TeammateJoined);
        Carbon::setTestNow();
        $this->actingAs($maya);

        $this->getJson('/api/inbox/notifications')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'type', 'type_label', 'title', 'body', 'url', 'open_url', 'icon', 'actor', 'initials', 'read', 'read_at', 'created_at', 'time_ago', 'day', 'payload']], 'links', 'meta' => ['unread', 'types', 'current_page', 'total']])
            ->assertJsonPath('data.0.title', 'Tom Reyes joined your team')
            ->assertJsonPath('data.0.actor', 'Tom Reyes')
            ->assertJsonPath('data.0.initials', 'TR')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.0.day', 'Today')
            ->assertJsonPath('data.1.title', 'Invoice #1042 paid')
            ->assertJsonPath('data.1.type', InvoicePaid::class)
            ->assertJsonPath('data.1.type_label', 'Invoice paid')
            ->assertJsonPath('data.1.url', '/invoices/1042')
            ->assertJsonPath('meta.unread', 2)
            ->assertJsonPath('meta.types', [InvoicePaid::class => 'Invoice paid', TeammateJoined::class => 'Teammate joined']);
    }

    public function test_list_filters_and_pages(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        $maya->notify(new InvoicePaid('1043'));
        $maya->notify(new TeammateJoined);
        Inbox::markRead(Notification::query()->where('type', TeammateJoined::class)->firstOrFail());
        $this->actingAs($maya);

        $this->getJson('/api/inbox/notifications?filter=unread')->assertJsonCount(2, 'data');
        $this->getJson('/api/inbox/notifications?type='.urlencode(TeammateJoined::class))->assertJsonCount(1, 'data');
        $this->getJson('/api/inbox/notifications?per_page=1')->assertJsonCount(1, 'data')->assertJsonPath('meta.last_page', 3);
        $this->getJson('/api/inbox/notifications?per_page=500')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/inbox/notifications?filter=everything')->assertUnprocessable()->assertJsonValidationErrors('filter');
    }

    public function test_count_and_show(): void
    {
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->message(['title' => 'Hello'])->create();
        $this->actingAs($maya);

        $this->getJson('/api/inbox/notifications/count')->assertExactJson(['unread' => 1]);
        $this->getJson("/api/inbox/notifications/{$notification->id}")->assertOk()->assertJsonPath('data.title', 'Hello');
        $this->getJson('/api/inbox/notifications/nope')->assertNotFound();
    }

    public function test_mark_read_unread_all_and_delete(): void
    {
        Event::fake();
        $maya = $this->user();
        $notification = Notification::factory()->to($maya)->create();
        Notification::factory()->to($maya)->create();
        $this->actingAs($maya);

        $this->postJson("/api/inbox/notifications/{$notification->id}/read")->assertOk()->assertJsonPath('data.read', true);
        $this->postJson("/api/inbox/notifications/{$notification->id}/unread")->assertOk()->assertJsonPath('data.read', false)->assertJsonPath('data.read_at', null);
        $this->postJson('/api/inbox/notifications/read-all')->assertExactJson(['marked' => 2, 'unread' => 0]);
        $this->deleteJson("/api/inbox/notifications/{$notification->id}")->assertNoContent();

        $this->assertSame(1, Notification::query()->count());
        Event::assertDispatched(NotificationDeleted::class);
    }

    public function test_someone_elses_are_a_404(): void
    {
        $maya = $this->user('Maya');
        $notification = Notification::factory()->to($maya)->message(['title' => 'Maya only'])->create();
        $this->actingAs($this->user('Tom'));

        $this->getJson("/api/inbox/notifications/{$notification->id}")->assertNotFound();
        $this->postJson("/api/inbox/notifications/{$notification->id}/read")->assertNotFound();
        $this->postJson("/api/inbox/notifications/{$notification->id}/unread")->assertNotFound();
        $this->deleteJson("/api/inbox/notifications/{$notification->id}")->assertNotFound();
        $this->getJson('/api/inbox/notifications')->assertJsonCount(0, 'data')->assertJsonPath('meta.unread', 0);
        $this->postJson('/api/inbox/notifications/read-all')->assertJsonPath('marked', 0);

        $this->assertSame(1, Inbox::unreadCount($maya));
    }

    public function test_preferences(): void
    {
        Event::fake();
        Inbox::preferences([
            InvoicePaid::class => ['label' => 'Invoice paid', 'group' => 'Billing', 'defaults' => ['mail' => false]],
            'security' => ['label' => 'Security alerts', 'channels' => ['mail'], 'required' => true],
        ]);
        $maya = $this->user();
        $this->actingAs($maya);

        $this->getJson('/api/inbox/preferences')
            ->assertOk()
            ->assertJsonPath('data.0.type', InvoicePaid::class)
            ->assertJsonPath('data.0.group', 'Billing')
            ->assertJsonPath('data.0.channels', ['mail' => false, 'database' => true])
            ->assertJsonPath('data.1.required', true);

        $this->putJson('/api/inbox/preferences', ['preferences' => [InvoicePaid::class => ['mail' => true]]])
            ->assertOk()
            ->assertJsonPath('data.0.channels', ['mail' => true, 'database' => true]);
        Event::assertDispatched(PreferencesUpdated::class);

        $this->putJson('/api/inbox/preferences', ['preferences' => ['security' => ['mail' => false]]])->assertUnprocessable()->assertJsonValidationErrors('preferences');
        $this->putJson('/api/inbox/preferences', ['preferences' => ['nope' => ['mail' => false]]])->assertUnprocessable();
        $this->putJson('/api/inbox/preferences', ['preferences' => [InvoicePaid::class => ['slack' => true]]])->assertUnprocessable();
        $this->putJson('/api/inbox/preferences', ['preferences' => [InvoicePaid::class => 'off']])->assertUnprocessable();
        $this->putJson('/api/inbox/preferences', ['preferences' => [InvoicePaid::class => ['mail' => 'maybe']]])->assertUnprocessable();
        $this->putJson('/api/inbox/preferences', [])->assertUnprocessable();
    }
}
