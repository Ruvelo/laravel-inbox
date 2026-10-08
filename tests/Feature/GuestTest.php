<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Ruvelo\Inbox\Tests\TestCase;

class GuestTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'inbox' => ['get', '/inbox'],
            'preferences' => ['get', '/inbox/preferences'],
            'save preferences' => ['put', '/inbox/preferences'],
            'count' => ['get', '/inbox/count'],
            'bell list' => ['get', '/inbox/bell'],
            'read all' => ['post', '/inbox/read-all'],
            'open' => ['get', '/inbox/abc/open'],
            'read' => ['post', '/inbox/abc/read'],
            'delete' => ['delete', '/inbox/abc'],
        ];
    }

    #[DataProvider('routes')]
    public function test_guests_get_a_403_when_the_app_has_no_login_page(string $method, string $uri): void
    {
        $this->assertFalse(Route::has('login'));

        $this->call(strtoupper($method), $uri)->assertForbidden();
    }

    public function test_guests_go_to_the_login_page_when_there_is_one(): void
    {
        Route::get('/login', fn () => 'login')->name('login');

        $this->get('/inbox')->assertRedirect('/login');
    }

    public function test_json_requests_get_a_401(): void
    {
        $this->getJson('/inbox/count')->assertUnauthorized();
        $this->getJson('/api/inbox/notifications')->assertUnauthorized();
        $this->putJson('/api/inbox/preferences', ['preferences' => []])->assertUnauthorized();
    }

    public function test_the_bell_renders_nothing_for_guests(): void
    {
        $this->assertSame('', trim(Blade::render('<x-inbox::bell />')));
    }
}
