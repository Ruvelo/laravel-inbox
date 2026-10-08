<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Ruvelo\Inbox\Http\Controllers\Controller;
use Ruvelo\Inbox\Http\Resources\NotificationResource;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Support\Listing;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'filter' => ['nullable', 'in:all,unread'],
            'type' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $this->notifiable($request);
        $listing = Listing::fromRequest($request, $user);
        $perPage = (int) ($request->query('per_page') ?? config('inbox.per_page', 25));

        return NotificationResource::collection($listing->query()->paginate($perPage)->withQueryString())
            ->additional(['meta' => [
                'unread' => Inbox::unreadCount($user),
                'types' => $listing->types,
            ]]);
    }

    public function show(Request $request, string $notification): NotificationResource
    {
        return new NotificationResource($this->owned($request, $notification));
    }

    public function count(Request $request): JsonResponse
    {
        return response()
            ->json(['unread' => Inbox::unreadCount($this->notifiable($request))])
            ->header('Cache-Control', 'no-store, private');
    }

    public function read(Request $request, string $notification): NotificationResource
    {
        $notification = $this->owned($request, $notification);
        Inbox::markRead($notification);

        return new NotificationResource($notification);
    }

    public function unread(Request $request, string $notification): NotificationResource
    {
        $notification = $this->owned($request, $notification);
        Inbox::markUnread($notification);

        return new NotificationResource($notification);
    }

    public function readAll(Request $request): JsonResponse
    {
        return response()->json([
            'marked' => Inbox::markAllRead($this->notifiable($request)),
            'unread' => 0,
        ]);
    }

    public function destroy(Request $request, string $notification): Response
    {
        Inbox::delete($this->owned($request, $notification));

        return response()->noContent();
    }
}
