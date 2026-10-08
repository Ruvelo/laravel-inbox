<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Ruvelo\Inbox\Events\PreferencesUpdated;
use Ruvelo\Inbox\Exceptions\RequiredPreference;
use Ruvelo\Inbox\Exceptions\UnknownPreference;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Models\Preference;
use Ruvelo\Inbox\PreferenceType;
use Ruvelo\Inbox\Tests\Fixtures\InvoicePaid;
use Ruvelo\Inbox\Tests\TestCase;

class PreferencesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('inbox.preferences', [
            InvoicePaid::class => [
                'label' => 'Invoice paid',
                'description' => 'When a customer pays one of your invoices.',
                'group' => 'Billing',
                'channels' => ['mail', 'database'],
                'defaults' => ['mail' => false],
            ],
            'security' => [
                'label' => 'Security alerts',
                'channels' => ['mail'],
                'required' => true,
            ],
        ]);
    }

    public function test_wants_uses_defaults_then_choices(): void
    {
        $maya = $this->user();

        $this->assertFalse(Inbox::wants($maya, InvoicePaid::class, 'mail'));
        $this->assertTrue(Inbox::wants($maya, InvoicePaid::class, 'database'));
        $this->assertTrue(Inbox::wants($maya, InvoicePaid::class, 'slack'), 'channels that can’t be toggled are always wanted');
        $this->assertTrue(Inbox::wants($maya, 'unregistered', 'mail'));
        $this->assertTrue(Inbox::wants($maya, 'security', 'mail'));

        Inbox::updatePreferences($maya, [InvoicePaid::class => ['mail' => true, 'database' => false]]);

        $this->assertTrue(Inbox::wants($maya, InvoicePaid::class, 'mail'));
        $this->assertFalse(Inbox::wants($maya, InvoicePaid::class, 'database'));
        $this->assertTrue(Inbox::wants($this->user('Tom'), InvoicePaid::class, 'database'), 'choices are per person');
    }

    public function test_via_drops_the_channels_you_turned_off(): void
    {
        NotificationFacade::fake();
        $maya = $this->user();

        $maya->notify(new InvoicePaid);
        NotificationFacade::assertSentTo($maya, InvoicePaid::class, fn ($n, array $channels) => $channels === ['database']);

        Inbox::updatePreferences($maya, [InvoicePaid::class => ['mail' => true, 'database' => false]]);
        $maya->notify(new InvoicePaid);
        NotificationFacade::assertSentTo($maya, InvoicePaid::class, fn ($n, array $channels) => $channels === ['mail']);
    }

    public function test_a_turned_off_type_never_reaches_the_inbox(): void
    {
        $maya = $this->user();
        Inbox::updatePreferences($maya, [InvoicePaid::class => ['database' => false]]);

        $maya->notify(new InvoicePaid);

        $this->assertSame(0, Notification::query()->count());
    }

    public function test_anonymous_notifiables_get_every_channel(): void
    {
        $this->assertSame(['mail', 'database'], (new InvoicePaid)->via(new AnonymousNotifiable));
    }

    public function test_update_reports_and_announces_only_real_changes(): void
    {
        Event::fake();
        $maya = $this->user();

        $this->assertSame([], Inbox::updatePreferences($maya, [InvoicePaid::class => ['mail' => false]]));
        Event::assertNotDispatched(PreferencesUpdated::class);

        $changes = Inbox::updatePreferences($maya, [InvoicePaid::class => ['mail' => true, 'database' => true]]);
        $this->assertSame([InvoicePaid::class => ['mail' => true]], $changes);
        Event::assertDispatched(PreferencesUpdated::class, fn (PreferencesUpdated $e) => $e->notifiable->is($maya) && $e->changes === $changes);

        Inbox::updatePreferences($maya, [InvoicePaid::class => ['mail' => true]]);
        $this->assertSame(2, Preference::query()->count(), 'one row per type and channel');
    }

    public function test_preferences_for_fills_in_defaults(): void
    {
        $maya = $this->user();
        Inbox::updatePreferences($maya, [InvoicePaid::class => ['database' => false]]);

        $this->assertSame([
            InvoicePaid::class => ['mail' => false, 'database' => false],
            'security' => ['mail' => true],
        ], Inbox::preferencesFor($maya));
    }

    public function test_unknown_types_and_channels_are_refused(): void
    {
        $maya = $this->user();

        try {
            Inbox::updatePreferences($maya, ['nope' => ['mail' => false]]);
            $this->fail('Expected UnknownPreference');
        } catch (UnknownPreference) {
        }

        $this->expectException(UnknownPreference::class);
        Inbox::updatePreferences($maya, [InvoicePaid::class => ['slack' => false]]);
    }

    public function test_required_types_cant_be_turned_off(): void
    {
        $this->expectException(RequiredPreference::class);

        Inbox::updatePreferences($this->user(), ['security' => ['mail' => false]]);
    }

    public function test_types_registered_in_code_join_the_config_ones(): void
    {
        Inbox::preferences([
            'digest' => ['label' => 'Weekly digest', 'channels' => ['mail']],
            'mentions' => new PreferenceType('mentions', 'Mentions', channels: ['database']),
        ]);

        $this->assertSame([InvoicePaid::class, 'security', 'digest', 'mentions'], array_keys(Inbox::preferenceTypes()));
        $this->assertSame('Weekly digest', Inbox::preferenceType('digest')?->label);
    }

    public function test_the_preferences_page(): void
    {
        $this->actingAs($this->user());

        $this->get('/inbox/preferences')
            ->assertOk()
            ->assertSee('Notification preferences')
            ->assertSee('Billing')
            ->assertSee('Invoice paid')
            ->assertSee('When a customer pays one of your invoices.')
            ->assertSee('Security alerts')
            ->assertSee('Always on')
            ->assertSee('Email')
            ->assertSee('In app')
            ->assertSee('role="switch"', false);
    }

    public function test_saving_the_form(): void
    {
        Event::fake();
        $maya = $this->user();
        $invoice = Inbox::preferenceType(InvoicePaid::class);
        $security = Inbox::preferenceType('security');
        $this->assertNotNull($invoice);
        $this->assertNotNull($security);

        $this->actingAs($maya)
            ->put('/inbox/preferences', ['prefs' => [
                $invoice->id() => ['mail' => '1', 'database' => '0'],
                $security->id() => ['mail' => '0'], // required: ignored
                'made-up' => ['mail' => '1'],
            ]])
            ->assertRedirect('/inbox/preferences')
            ->assertSessionHas('inbox.status', 'Preferences saved.');

        $this->assertSame([
            InvoicePaid::class => ['mail' => true, 'database' => false],
            'security' => ['mail' => true],
        ], Inbox::preferencesFor($maya));
        Event::assertDispatched(PreferencesUpdated::class);

        $this->put('/inbox/preferences', ['prefs' => [$invoice->id() => ['mail' => '1', 'database' => '0']]])
            ->assertSessionHas('inbox.status', 'No changes to save.');
    }

    public function test_an_empty_preferences_page_explains_itself(): void
    {
        config(['inbox.preferences' => []]);

        $this->actingAs($this->user())
            ->get('/inbox/preferences')
            ->assertOk()
            ->assertSee('Nothing to set up yet');
    }
}
