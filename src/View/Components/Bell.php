<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Ruvelo\Inbox\Inbox;

/**
 * `<x-inbox::bell />`: the bell for your app's header.
 *
 * Two queries: the unread count and the latest few. Renders nothing for
 * guests. Without JavaScript it's a link to the inbox.
 */
class Bell extends Component
{
    public readonly int $limit;

    public readonly int $poll;

    public function __construct(
        ?int $limit = null,
        ?int $poll = null,
        public readonly ?Model $user = null,
        public readonly ?string $label = 'Notifications',
    ) {
        $this->limit = max(1, $limit ?? (int) config('inbox.bell.limit', 6));
        $this->poll = max(0, $poll ?? (int) config('inbox.bell.poll', 30));
    }

    public function shouldRender(): bool
    {
        return $this->notifiable() !== null;
    }

    public function render(): View
    {
        $user = $this->notifiable();
        assert($user !== null);

        return $this->view('inbox::components.bell', [
            'id' => 'inbox-bell-'.Str::lower(Str::random(6)),
            'unread' => Inbox::unreadCount($user),
            'notifications' => Inbox::latest($user, $this->limit),
            'channel' => config('inbox.bell.echo', true) ? Inbox::broadcastChannel($user) : null,
        ]);
    }

    private function notifiable(): ?Model
    {
        $user = $this->user ?? auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
