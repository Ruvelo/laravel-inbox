<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;

/**
 * @property-read Notification $resource
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;
        $message = $notification->message();

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'type_label' => Inbox::typeLabel($notification->type),
            'title' => $message->title,
            'body' => $message->body,
            'url' => $message->url,
            'open_url' => Route::has('inbox.open') ? route('inbox.open', $notification->id) : $message->url,
            'icon' => $message->icon,
            'actor' => $message->actor,
            'initials' => $message->initials(),
            'read' => ! $notification->isUnread(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'time_ago' => $notification->created_at->diffForHumans(),
            'day' => Inbox::dayLabel($notification->created_at),
            'payload' => $notification->data,
        ];
    }
}
