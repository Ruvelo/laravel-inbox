<?php

declare(strict_types=1);

namespace Ruvelo\Inbox;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * How one notification looks in the inbox: what it says, where it leads,
 * and who or what it's about. Immutable; every `with…` returns a copy.
 *
 *     InboxMessage::make('Invoice #1042 paid')
 *         ->withBody('Acme Corp paid €2,400.00.')
 *         ->withUrl(route('invoices.show', $invoice))
 *         ->withIcon('💸');
 *
 * @implements Arrayable<string, string|null>
 */
final readonly class InboxMessage implements Arrayable, JsonSerializable
{
    public ?string $url;

    public function __construct(
        public string $title,
        public ?string $body = null,
        ?string $url = null,
        public ?string $icon = null,
        public ?string $actor = null,
    ) {
        $this->url = self::safeUrl($url);
    }

    public static function make(string $title, ?string $body = null, ?string $url = null, ?string $icon = null, ?string $actor = null): self
    {
        return new self($title, $body, $url, $icon, $actor);
    }

    /**
     * Build a message from a notification's stored data, by convention:
     * `title`, `body`, `url`, `icon` and `actor`. Returns null without a title.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $title = self::pick($data, ['title', 'subject', 'heading']);

        if ($title === null) {
            return null;
        }

        return new self(
            $title,
            self::pick($data, ['body', 'message', 'text', 'description']),
            self::pick($data, ['url', 'action_url', 'link']),
            self::pick($data, ['icon', 'emoji']),
            self::pick($data, ['actor', 'actor_name', 'causer']),
        );
    }

    public function withTitle(string $title): self
    {
        return new self($title, $this->body, $this->url, $this->icon, $this->actor);
    }

    public function withBody(?string $body): self
    {
        return new self($this->title, $body, $this->url, $this->icon, $this->actor);
    }

    public function withUrl(?string $url): self
    {
        return new self($this->title, $this->body, $url, $this->icon, $this->actor);
    }

    public function withIcon(?string $icon): self
    {
        return new self($this->title, $this->body, $this->url, $icon, $this->actor);
    }

    public function withActor(?string $actor): self
    {
        return new self($this->title, $this->body, $this->url, $this->icon, $actor);
    }

    /**
     * Up to two initials for the avatar: the actor's, or the title's first letter.
     */
    public function initials(): string
    {
        $source = $this->actor ?? $this->title;
        $words = preg_split('/[\s\-_.]+/u', trim($source), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($this->actor === null || count($words) < 2) {
            return mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[count($words) - 1], 0, 1));
    }

    /**
     * The body cut to a length that fits a dropdown.
     */
    public function excerpt(int $length = 110): ?string
    {
        if ($this->body === null) {
            return null;
        }

        $body = trim((string) preg_replace('/\s+/u', ' ', $this->body));

        return mb_strlen($body) > $length ? rtrim(mb_substr($body, 0, $length - 1)).'…' : $body;
    }

    /**
     * @return array{title: string, body: string|null, url: string|null, icon: string|null, actor: string|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'actor' => $this->actor,
        ];
    }

    /**
     * @return array{title: string, body: string|null, url: string|null, icon: string|null, actor: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Only web links and paths: a `javascript:` or `data:` URL in stored
     * data never becomes a link.
     */
    private static function safeUrl(?string $url): ?string
    {
        $url = $url === null ? '' : trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')) {
            return $url;
        }

        return preg_match('~^https?://[^\s/]+~i', $url) === 1 ? $url : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function pick(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
