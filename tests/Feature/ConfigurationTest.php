<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Ruvelo\Inbox\Models\Preference;
use Ruvelo\Inbox\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('inbox.api.enabled', false);
        $app['config']->set('inbox.path', 'account/notifications');
        $app['config']->set('inbox.table_prefix', 'app_inbox_');
    }

    public function test_the_api_can_be_turned_off(): void
    {
        $this->assertFalse(Route::has('inbox.api.index'));
        $this->assertTrue(Route::has('inbox.index'));
    }

    public function test_the_path_and_table_prefix_are_configurable(): void
    {
        $this->assertSame(url('account/notifications'), route('inbox.index'));
        $this->assertSame('app_inbox_preferences', Preference::tableName());
        $this->assertTrue(Schema::hasTable('app_inbox_preferences'));

        $this->actingAs($this->user())->get('/account/notifications')->assertOk();
    }
}
