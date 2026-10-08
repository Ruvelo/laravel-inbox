<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Support\Listing;
use Symfony\Component\HttpFoundation\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->notifiable($request);
        $listing = Listing::fromRequest($request, $user);

        $notifications = $listing->query()
            ->paginate((int) config('inbox.per_page', 25))
            ->withQueryString();

        return response()->view('inbox::index', [
            'notifications' => $notifications,
            'groups' => Inbox::groupByDay($notifications->items()),
            'filter' => $listing->filter,
            'type' => $listing->type,
            'types' => $listing->types,
            'unread' => Inbox::unreadCount($user),
        ]);
    }

    /**
     * Follow a notification: mark it read, then go where it points. A plain
     * link, so it works without JavaScript.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->owned($request, $notification);
        Inbox::markRead($notification);

        $url = $notification->message()->url;

        if ($url === null) {
            return redirect()->route('inbox.index');
        }

        return str_starts_with($url, '/') ? redirect()->to($url) : redirect()->away($url);
    }

    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        Inbox::markRead($this->owned($request, $notification));

        return $this->done($request, 'Marked as read.');
    }

    public function unread(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        Inbox::markUnread($this->owned($request, $notification));

        return $this->done($request, 'Marked as unread.');
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $count = Inbox::markAllRead($this->notifiable($request));

        return $this->done($request, match ($count) {
            0 => 'Nothing to mark: you’re all caught up.',
            1 => 'Marked 1 notification as read.',
            default => "Marked {$count} notifications as read.",
        });
    }

    public function destroy(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        Inbox::delete($this->owned($request, $notification));

        return $this->done($request, 'Notification deleted.');
    }

    /**
     * What the bell polls: one COUNT query.
     */
    public function count(Request $request): JsonResponse
    {
        return response()
            ->json(['unread' => Inbox::unreadCount($this->notifiable($request))])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * The bell's list, re-rendered when the count changes.
     */
    public function bell(Request $request): HttpResponse
    {
        $user = $this->notifiable($request);
        $limit = min(50, max(1, (int) $request->query('limit', (string) config('inbox.bell.limit', 6))));

        return response()
            ->view('inbox::partials.bell-list', ['notifications' => Inbox::latest($user, $limit)])
            ->header('X-Inbox-Unread', (string) Inbox::unreadCount($user))
            ->header('Cache-Control', 'no-store, private');
    }

    private function done(Request $request, string $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['unread' => Inbox::unreadCount($this->notifiable($request))]);
        }

        return redirect()->back(fallback: route('inbox.index'))->with('inbox.status', $status);
    }
}
