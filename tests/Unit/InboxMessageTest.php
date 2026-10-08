<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruvelo\Inbox\InboxMessage;

class InboxMessageTest extends TestCase
{
    public function test_make_and_withers_return_copies(): void
    {
        $message = InboxMessage::make('Invoice paid');
        $changed = $message->withBody('Acme paid.')->withUrl('/invoices/1')->withIcon('💸')->withActor('Maya Okafor')->withTitle('Invoice #1 paid');

        $this->assertNull($message->body);
        $this->assertSame('Invoice paid', $message->title);
        $this->assertSame([
            'title' => 'Invoice #1 paid',
            'body' => 'Acme paid.',
            'url' => '/invoices/1',
            'icon' => '💸',
            'actor' => 'Maya Okafor',
        ], $changed->toArray());
        $this->assertSame(json_encode($changed->toArray()), json_encode($changed));
    }

    public function test_from_array_follows_the_convention(): void
    {
        $message = InboxMessage::fromArray(['title' => ' Export ready ', 'body' => 'Your CSV is ready.', 'url' => 'https://halyard.test/exports/9', 'icon' => '📦', 'actor' => 'Kenji Mori', 'extra' => 1]);

        $this->assertNotNull($message);
        $this->assertSame('Export ready', $message->title);
        $this->assertSame('Your CSV is ready.', $message->body);
        $this->assertSame('https://halyard.test/exports/9', $message->url);
        $this->assertSame('📦', $message->icon);
        $this->assertSame('Kenji Mori', $message->actor);
    }

    public function test_from_array_accepts_common_aliases(): void
    {
        $message = InboxMessage::fromArray(['subject' => 'Payment failed', 'message' => 'Card declined.', 'action_url' => '/billing']);

        $this->assertNotNull($message);
        $this->assertSame(['Payment failed', 'Card declined.', '/billing'], [$message->title, $message->body, $message->url]);
    }

    public function test_from_array_needs_a_title(): void
    {
        $this->assertNull(InboxMessage::fromArray(['body' => 'No title']));
        $this->assertNull(InboxMessage::fromArray(['title' => '   ']));
        $this->assertNull(InboxMessage::fromArray(['title' => ['nested']]));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function urls(): array
    {
        return [
            'path' => ['/invoices/1', '/invoices/1'],
            'https' => ['https://halyard.test/x', 'https://halyard.test/x'],
            'http' => ['http://halyard.test', 'http://halyard.test'],
            'javascript' => ['javascript:alert(1)', null],
            'javascript, padded' => ['  JavaScript:alert(1)', null],
            'data' => ['data:text/html,<script>alert(1)</script>', null],
            'protocol-relative' => ['//evil.test/x', null],
            'backslash trick' => ['/\\evil.test', null],
            'relative' => ['invoices/1', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('urls')]
    public function test_only_web_links_survive(string $url, ?string $expected): void
    {
        $this->assertSame($expected, InboxMessage::make('T', url: $url)->url);
    }

    public function test_initials(): void
    {
        $this->assertSame('MO', InboxMessage::make('x', actor: 'Maya Okafor')->initials());
        $this->assertSame('IL', InboxMessage::make('x', actor: 'Inès van Laurent')->initials());
        $this->assertSame('K', InboxMessage::make('x', actor: 'Kenji')->initials());
        $this->assertSame('É', InboxMessage::make('été')->initials());
    }

    public function test_excerpt(): void
    {
        $this->assertNull(InboxMessage::make('x')->excerpt());
        $this->assertSame('Short body', InboxMessage::make('x', "Short\n  body")->excerpt());
        $this->assertSame('abcd…', InboxMessage::make('x', 'abcdefghij')->excerpt(5));
    }
}
