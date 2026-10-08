<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\TestCase;

class BellTest extends TestCase
{
    public function test_it_shows_the_unread_count_and_the_latest(): void
    {
        $maya = $this->user();
        $maya->notify(new InvoicePaid);
        Notification::factory()->to($maya)->read()->message(['title' => 'Already seen', 'actor' => 'Kenji Mori'])->create();
        $this->actingAs($maya);

        $html = Blade::render('<x-inbox::bell />');

        $this->assertStringContainsString('aria-label="Notifications, 1 unread"', $html);
        $this->assertMatchesRegularExpression('/data-inbox-badge aria-hidden="true"\s*>1</', $html);
        $this->assertStringContainsString('Invoice #1042 paid', $html);
        $this->assertStringContainsString('Acme Corp paid €2,400.00.', $html);
        $this->assertStringContainsString('Already seen', $html);
        $this->assertStringContainsString('>KM<', $html);
        $this->assertStringContainsString('data-url="/invoices/1042"', $html);
        $this->assertStringContainsString('href="'.route('inbox.index').'" data-inbox-toggle', $html, 'without JS it is a link to the inbox');
        $this->assertStringContainsString('Mark all as read', $html);
        $this->assertStringContainsString('View all', $html);
        $this->assertStringContainsString('data-poll="30"', $html);
        $this->assertStringContainsString('data-channel="Ruvelo.Inbox.Tests.Fixtures.User.'.$maya->id.'"', $html);
        $this->assertSame(1, substr_count($html, 'class="inbox-bell-dot"'));
    }

    public function test_it_uses_two_queries_however_many_notifications(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->count(40)->create();
        $this->actingAs($maya);

        DB::enableQueryLog();
        $html = Blade::render('<x-inbox::bell :limit="5" />');

        $this->assertCount(2, DB::getQueryLog());
        $this->assertSame(5, preg_match_all('/data-inbox-item\s+data-read-url/', $html));
    }

    public function test_the_badge_caps_at_99_plus_and_hides_at_zero(): void
    {
        $maya = $this->user();
        $this->actingAs($maya);

        $this->assertMatchesRegularExpression('/data-inbox-badge aria-hidden="true"\s+hidden\s*>0</', Blade::render('<x-inbox::bell />'));
        $this->assertStringContainsString('You’re all caught up', Blade::render('<x-inbox::bell />'));

        Notification::factory()->to($maya)->count(120)->create();
        $html = Blade::render('<x-inbox::bell />');
        $this->assertStringContainsString('>99+<', $html);
        $this->assertStringContainsString('120 unread', $html);
    }

    public function test_options_and_an_explicit_user(): void
    {
        $maya = $this->user('Maya');
        Notification::factory()->to($maya)->message(['title' => 'For Maya'])->create();
        config(['inbox.bell.echo' => false]);

        $html = Blade::render('<x-inbox::bell :user="$user" :poll="0" label="Updates" class="ml-auto" />', ['user' => $maya]);

        $this->assertStringContainsString('For Maya', $html);
        $this->assertStringContainsString('data-poll="0"', $html);
        $this->assertStringContainsString('aria-label="Updates, 1 unread"', $html);
        $this->assertStringContainsString('class="inbox-bell ml-auto"', $html);
        $this->assertStringNotContainsString('data-channel', $html);
    }

    public function test_content_is_escaped(): void
    {
        $maya = $this->user();
        Notification::factory()->to($maya)->message(['title' => '<script>x()</script>', 'url' => 'javascript:x()'])->create();
        $this->actingAs($maya);

        $html = Blade::render('<x-inbox::bell />');

        $this->assertStringNotContainsString('<script>x()', $html);
        $this->assertStringNotContainsString('javascript:x()', $html);
        $this->assertStringContainsString('&lt;script&gt;x()&lt;/script&gt;', $html);
    }

    public function test_styles_and_script_are_included_once(): void
    {
        $this->actingAs($this->user());

        $html = Blade::render('<x-inbox::bell /><x-inbox::bell />');

        $this->assertSame(2, substr_count($html, 'data-inbox-bell'."\n"));
        $this->assertSame(1, substr_count($html, '<style>'));
        $this->assertSame(1, substr_count($html, '<script>'));
    }
}
