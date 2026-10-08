@verbatim
## Laravel Inbox (ruvelo/laravel-inbox)

An in-app notification inbox on top of Laravel's own `database` notification channel and `notifications` table: a header bell, an inbox page at `/inbox`, per-type preferences at `/inbox/preferences`, and a JSON API. Existing database notifications show up unchanged. Every route only ever shows the signed-in user's own notifications.

### Setup

- Needs the `notifications` table (`php artisan make:notifications-table`) and a notifiable model using `Notifiable`. The package adds one table, `inbox_preferences` (prefix: `inbox.table_prefix`).
- Put the bell in the app's layout header, once. It renders nothing for guests and brings its own scoped styles and script:

<code-snippet name="The bell in the layout header" lang="blade">
<header class="flex items-center gap-4">
    <a href="/">Halyard</a>
    <x-inbox::bell class="ms-auto" />
</header>
</code-snippet>

Options: `:limit="8"`, `:poll="60"` (seconds, 0 turns polling off), `label="Updates"`.

### Making a notification look right

The inbox reads `title`, `body`, `url`, `icon` and `actor` from the stored data. Send `database` in `via()` and return those keys from `toArray()` (or `toDatabase()`):

<code-snippet name="Notification data the inbox understands" lang="php">
public function via(object $notifiable): array
{
    return ['mail', 'database'];
}

public function toArray(object $notifiable): array
{
    return [
        'title' => "Invoice {$this->invoice->number} paid",
        'body' => "{$this->invoice->customer} paid {$this->invoice->total}.",
        'url' => route('invoices.show', $this->invoice),
        'icon' => '💸',
        'actor' => $this->invoice->customer,
    ];
}
</code-snippet>

Or implement `InboxNotification`; the message is stored with the notification, so no `toArray()` is needed:

<code-snippet name="Describing a notification with toInbox()" lang="php">
use Ruvelo\Inbox\Contracts\InboxNotification;
use Ruvelo\Inbox\InboxMessage;

class TeammateJoined extends Notification implements InboxNotification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toInbox(object $notifiable): InboxMessage
    {
        return InboxMessage::make("{$this->user->name} joined your team")
            ->withUrl(route('team'))
            ->withActor($this->user->name);
    }
}
</code-snippet>

- Without a `title`, the inbox falls back to the humanised class name ("Invoice paid"). Always give one.
- `url` must be a path or an `http(s)` URL; anything else (`javascript:`) is dropped.
- For notifications you can't edit (other packages, rows already stored), register a presenter in a service provider: `Inbox::present(ExportReady::class, fn (array $data) => InboxMessage::make('Your export is ready', url: route('exports.show', $data['export_id'])));`

### Preferences

Register the types people can switch off, in `config/inbox.php` (`preferences`) or a service provider's `boot()`, then filter `via()` with `RespectsInboxPreferences`:

<code-snippet name="Registering a preference type and respecting it" lang="php">
use Ruvelo\Inbox\Inbox;

Inbox::preferences([
    InvoicePaid::class => [
        'label' => 'Invoice paid',
        'description' => 'When a customer pays one of your invoices.',
        'group' => 'Billing',
        'channels' => ['mail', 'database'],
        'defaults' => ['mail' => false],
    ],
    SecurityAlert::class => ['label' => 'Security alerts', 'channels' => ['mail'], 'required' => true],
]);

// In the notification:
use Ruvelo\Inbox\Notifications\RespectsInboxPreferences;

class InvoicePaid extends Notification
{
    use RespectsInboxPreferences;

    public function via(object $notifiable): array
    {
        return $this->preferredChannels($notifiable, ['mail', 'database']);
    }
}
</code-snippet>

Override `inboxType()` to return one shared key when several notifications share one switch. Check a choice with `Inbox::wants($user, InvoicePaid::class, 'mail')`.

### Reading and changing notifications in PHP

Use the `Ruvelo\Inbox\Inbox` API; it is scoped to one notifiable and fires the events:

<code-snippet name="PHP API" lang="php">
Inbox::unreadCount($user);
Inbox::latest($user, 5);
Inbox::query($user)->whereNull('read_at')->paginate();
Inbox::find($user, $id);          // null if it isn't theirs
Inbox::markRead($notification);   // also markUnread(), delete()
Inbox::markAllRead($user);
Inbox::message($notification);    // InboxMessage: title, body, url, icon, actor
</code-snippet>

### JSON API (Inertia, React, Vue)

Under `/api/inbox`, on the session (`web` middleware), so send the CSRF token on writes like any form. Guests get `401`; someone else's notification is a `404`.

- `GET /api/inbox/notifications` (`?filter=unread`, `?type=`, `?per_page=` up to 100; `meta.unread`), `GET /api/inbox/notifications/count` → `{"unread": 3}`
- `POST /api/inbox/notifications/{id}/read`, `/unread`, `POST /api/inbox/notifications/read-all`, `DELETE /api/inbox/notifications/{id}`
- `GET /api/inbox/preferences`, `PUT /api/inbox/preferences` with `{"preferences": {"App\\Notifications\\InvoicePaid": {"mail": false}}}`

Each notification has `id`, `type`, `type_label`, `title`, `body`, `url`, `open_url` (marks read, then redirects), `icon`, `actor`, `initials`, `read`, `created_at`, `time_ago` and `day`. In a SPA, poll the count endpoint or listen with Echo for the notifiable's broadcast notifications.

### Events

In `Ruvelo\Inbox\Events`, fired only when something changed: `NotificationRead`, `NotificationUnread`, `AllNotificationsRead` (`notifiable`, `count`), `NotificationDeleted`, `PreferencesUpdated` (`notifiable`, `changes`).

### Tests

<code-snippet name="Factory for host-app tests" lang="php">
use Ruvelo\Inbox\InboxMessage;
use Ruvelo\Inbox\Models\Notification;

Notification::factory()->to($user)->count(3)->create();
Notification::factory()->to($user)->read()->ofType(InvoicePaid::class)
    ->message(InboxMessage::make('Invoice paid', url: '/invoices/1'))->create();
</code-snippet>

### Don't

- Don't query the `notifications` table by hand, or `DatabaseNotification::find($id)`, for a user's inbox: use `Inbox::find($user, $id)` / `Inbox::query($user)`, which never return another user's notifications.
- Don't add `auth` to `inbox.middleware` or `inbox.api.middleware`: the routes already require a signed-in user, and send guests to `login` when that route exists (a 403 otherwise). `auth` breaks apps without a `login` route.
- Don't build a second bell, unread counter or "mark as read" endpoint: use `<x-inbox::bell />`, the JSON API or `Inbox::*`.
- Don't mark notifications read with `$notification->markAsRead()` when the app listens for inbox events: `Inbox::markRead()` fires `NotificationRead`.
- To restyle, override the `--inbox-*` CSS variables or publish the views (`php artisan vendor:publish --tag=inbox-views`); to use the app's layout on the inbox pages, set `inbox.layout` (and `inbox.section`).
@endverbatim
