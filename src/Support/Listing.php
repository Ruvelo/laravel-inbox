<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;

/**
 * The inbox's filters (all or unread, one type), read from a request.
 * The type must be one this person actually has, so the filter can only
 * ever narrow their own list.
 *
 * @internal
 */
final readonly class Listing
{
    /**
     * @param  'all'|'unread'  $filter
     * @param  array<string, string>  $types
     */
    private function __construct(
        public Model $notifiable,
        public string $filter,
        public ?string $type,
        public array $types,
    ) {}

    public static function fromRequest(Request $request, Model $notifiable): self
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $types = Inbox::types($notifiable);
        $type = $request->query('type');

        return new self($notifiable, $filter, is_string($type) && isset($types[$type]) ? $type : null, $types);
    }

    /**
     * @return Builder<Notification>
     */
    public function query(): Builder
    {
        return Inbox::query($this->notifiable)
            ->ofType($this->type)
            ->when($this->filter === 'unread', fn (Builder $query) => $query->whereNull('read_at'));
    }
}
