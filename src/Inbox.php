<?php

declare(strict_types=1);

namespace Ruvelo\Inbox;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Ruvelo\Inbox\Events\AllNotificationsRead;
use Ruvelo\Inbox\Events\NotificationDeleted;
use Ruvelo\Inbox\Events\NotificationRead;
use Ruvelo\Inbox\Events\NotificationUnread;
use Ruvelo\Inbox\Events\PreferencesUpdated;
use Ruvelo\Inbox\Exceptions\InvalidPreferenceType;
use Ruvelo\Inbox\Exceptions\RequiredPreference;
use Ruvelo\Inbox\Exceptions\UnknownPreference;
use Ruvelo\Inbox\Models\Notification;
use Ruvelo\Inbox\Models\Preference;

/**
 * The inbox's public PHP API.
 *
 *     Inbox::unreadCount($user);
 *     Inbox::latest($user, 5)->map->message();
 *     Inbox::markAllRead($user);
 *     Inbox::present(InvoicePaid::class, fn (array $data) => InboxMessage::make(...));
 *     Inbox::wants($user, InvoicePaid::class, 'mail');
 */
final class Inbox
{
    /** @var array<string, Closure(array<array-key, mixed>, Notification): InboxMessage> */
    private static array $presenters = [];

    /** @var array<string, PreferenceType> */
    private static array $preferenceTypes = [];

    // --- Reading ---------------------------------------------------------

    /**
     * Everything in this person's inbox, newest first.
     *
     * @return Builder<Notification>
     */
    public static function query(Model $notifiable): Builder
    {
        return Notification::query()
            ->ownedBy($notifiable)
            ->latest()
            ->orderByDesc('id');
    }

    /**
     * One indexed COUNT query: cheap enough to poll.
     */
    public static function unreadCount(Model $notifiable): int
    {
        return Notification::query()->ownedBy($notifiable)->whereNull('read_at')->count();
    }

    /**
     * The newest notifications, read or not.
     *
     * @return Collection<int, Notification>
     */
    public static function latest(Model $notifiable, int $limit = 10): Collection
    {
        return self::query($notifiable)->limit(max(1, $limit))->get();
    }

    /**
     * One of this person's notifications; null if it isn't theirs.
     */
    public static function find(Model $notifiable, string $id): ?Notification
    {
        return Notification::query()->ownedBy($notifiable)->whereKey($id)->first();
    }

    /**
     * The notification types in this person's inbox, as [type => label].
     *
     * @return array<string, string>
     */
    public static function types(Model $notifiable): array
    {
        return Notification::query()
            ->ownedBy($notifiable)
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->mapWithKeys(fn (string $type): array => [$type => self::typeLabel($type)])
            ->sort()
            ->all();
    }

    // --- Writing ---------------------------------------------------------

    /**
     * Mark one notification as read. Returns false if it already was.
     */
    public static function markRead(DatabaseNotification $notification): bool
    {
        $notification = self::wrap($notification);
        $now = $notification->freshTimestamp();

        $changed = Notification::query()->whereKey($notification->getKey())->whereNull('read_at')->update(['read_at' => $now]) > 0;

        if ($changed) {
            $notification->setAttribute('read_at', $now)->syncOriginalAttribute('read_at');
            NotificationRead::dispatch($notification);
        }

        return $changed;
    }

    /**
     * Mark one notification as unread. Returns false if it already was.
     */
    public static function markUnread(DatabaseNotification $notification): bool
    {
        $notification = self::wrap($notification);

        $changed = Notification::query()->whereKey($notification->getKey())->whereNotNull('read_at')->update(['read_at' => null]) > 0;

        if ($changed) {
            $notification->setAttribute('read_at', null)->syncOriginalAttribute('read_at');
            NotificationUnread::dispatch($notification);
        }

        return $changed;
    }

    /**
     * Mark everything in this person's inbox as read, in one query.
     * Returns how many were unread.
     */
    public static function markAllRead(Model $notifiable): int
    {
        $count = Notification::query()->ownedBy($notifiable)->whereNull('read_at')->update(['read_at' => Carbon::now()]);

        if ($count > 0) {
            AllNotificationsRead::dispatch($notifiable, $count);
        }

        return $count;
    }

    public static function delete(DatabaseNotification $notification): void
    {
        $notification = self::wrap($notification);

        if ($notification->delete()) {
            NotificationDeleted::dispatch($notification);
        }
    }

    // --- Presentation ----------------------------------------------------

    /**
     * Decide how a notification type looks in the inbox. The callback gets
     * the stored data and the notification, and returns an InboxMessage.
     *
     *     Inbox::present(InvoicePaid::class, fn (array $data) => InboxMessage::make(
     *         "Invoice {$data['number']} paid", url: route('invoices.show', $data['invoice_id']),
     *     ));
     *
     * @param  string|array<string, Closure(array<array-key, mixed>, Notification): InboxMessage>  $type
     * @param  (Closure(array<array-key, mixed>, Notification): InboxMessage)|null  $presenter
     */
    public static function present(string|array $type, ?Closure $presenter = null): void
    {
        if (is_string($type)) {
            if ($presenter === null) {
                unset(self::$presenters[$type]);

                return;
            }

            $type = [$type => $presenter];
        }

        foreach ($type as $key => $callback) {
            self::$presenters[$key] = $callback;
        }
    }

    /**
     * How a stored notification looks: its registered presenter, else the
     * `title`/`body`/`url`/`icon`/`actor` convention, else the type's name.
     */
    public static function message(DatabaseNotification $notification): InboxMessage
    {
        $notification = self::wrap($notification);
        $data = is_array($notification->data) ? $notification->data : [];

        if (isset(self::$presenters[$notification->type])) {
            return (self::$presenters[$notification->type])($data, $notification);
        }

        return InboxMessage::fromArray($data)
            ?? InboxMessage::fromArray(['title' => self::typeLabel($notification->type)] + $data)
            ?? InboxMessage::make(self::typeLabel($notification->type));
    }

    /**
     * A readable name for a notification type: its preference label if it
     * has one, else its humanised class name.
     */
    public static function typeLabel(string $type): string
    {
        return self::preferenceType($type)->label ?? self::humanise($type);
    }

    /**
     * `App\Notifications\InvoicePaidNotification` → "Invoice paid".
     */
    public static function humanise(string $type): string
    {
        $name = Str::afterLast($type, '\\');
        $name = (string) preg_replace('/Notification$/', '', $name) ?: $name;
        $words = Str::lower(Str::headline(str_replace(['.', ':'], ' ', $name)));

        return Str::ucfirst(trim($words)) ?: 'Notification';
    }

    /**
     * Group notifications under "Today", "Yesterday" and dates, in the
     * inbox's timezone. Keeps their order.
     *
     * @param  iterable<Notification>  $notifications
     * @return array<string, list<Notification>>
     */
    public static function groupByDay(iterable $notifications): array
    {
        $groups = [];

        foreach ($notifications as $notification) {
            $groups[self::dayLabel($notification->created_at)][] = $notification;
        }

        return $groups;
    }

    public static function dayLabel(\DateTimeInterface $date): string
    {
        $timezone = self::timezone();
        $day = Carbon::instance($date)->setTimezone($timezone)->startOfDay();
        $today = Carbon::now($timezone)->startOfDay();

        return match (true) {
            $day->equalTo($today) => 'Today',
            $day->equalTo($today->copy()->subDay()) => 'Yesterday',
            $day->year === $today->year => $day->translatedFormat('l j F'),
            default => $day->translatedFormat('j F Y'),
        };
    }

    public static function timezone(): string
    {
        $timezone = config('inbox.timezone') ?: config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    // --- Preferences -----------------------------------------------------

    /**
     * Register notification types people can switch on and off, on top of
     * the `inbox.preferences` config. Keyed by class name or alias.
     *
     *     Inbox::preferences([
     *         InvoicePaid::class => ['label' => 'Invoice paid', 'channels' => ['mail', 'database']],
     *         SecurityAlert::class => ['label' => 'Security alerts', 'required' => true],
     *     ]);
     *
     * @param  array<string, array<array-key, mixed>|string|PreferenceType>  $types
     *
     * @throws InvalidPreferenceType
     */
    public static function preferences(array $types): void
    {
        foreach ($types as $key => $definition) {
            self::$preferenceTypes[$key] = $definition instanceof PreferenceType
                ? $definition
                : PreferenceType::fromConfig($key, $definition);
        }
    }

    /**
     * Every registered type, config first, in the order they were given.
     *
     * @return array<string, PreferenceType>
     *
     * @throws InvalidPreferenceType
     */
    public static function preferenceTypes(): array
    {
        $types = [];

        foreach ((array) config('inbox.preferences', []) as $key => $definition) {
            if (is_string($key) && (is_array($definition) || is_string($definition))) {
                $types[$key] = PreferenceType::fromConfig($key, $definition);
            }
        }

        return array_merge($types, self::$preferenceTypes);
    }

    public static function preferenceType(string $key): ?PreferenceType
    {
        return self::$preferenceTypes[$key] ?? self::preferenceTypes()[$key] ?? null;
    }

    /**
     * Whether this person wants this type of notification on this channel.
     * Unregistered types, channels that can't be toggled and required types
     * are always wanted.
     */
    public static function wants(Model $notifiable, string $type, string $channel): bool
    {
        return in_array($channel, self::filterChannels($notifiable, $type, [$channel]), true);
    }

    /**
     * Keep the channels this person wants for this type: what a
     * notification's via() returns. One query, and none for types that
     * aren't registered.
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    public static function filterChannels(Model $notifiable, string $type, array $channels): array
    {
        $definition = self::preferenceType($type);

        if ($definition === null || $definition->required) {
            return array_values($channels);
        }

        $choices = Preference::query()->ownedBy($notifiable)->where('type', $type)->pluck('enabled', 'channel');

        return array_values(array_filter($channels, function (string $channel) use ($definition, $choices): bool {
            if (! $definition->allows($channel)) {
                return true;
            }

            return $choices->has($channel) ? (bool) $choices->get($channel) : $definition->defaultFor($channel);
        }));
    }

    /**
     * This person's effective choices for every registered type, defaults
     * filled in: [type => [channel => on]].
     *
     * @return array<string, array<string, bool>>
     */
    public static function preferencesFor(Model $notifiable): array
    {
        $stored = [];
        foreach (Preference::query()->ownedBy($notifiable)->get(['type', 'channel', 'enabled']) as $row) {
            $stored[$row->type][$row->channel] = $row->enabled;
        }

        $effective = [];
        foreach (self::preferenceTypes() as $key => $type) {
            foreach ($type->channels as $channel) {
                $effective[$key][$channel] = $type->required || ($stored[$key][$channel] ?? $type->defaultFor($channel));
            }
        }

        return $effective;
    }

    /**
     * Save this person's choices: [type => [channel => on]]. Only the pairs
     * given are touched. Returns what changed, and fires PreferencesUpdated
     * if anything did.
     *
     * @param  array<string, array<string, bool>>  $choices
     * @return array<string, array<string, bool>>
     *
     * @throws UnknownPreference
     * @throws RequiredPreference
     */
    public static function updatePreferences(Model $notifiable, array $choices): array
    {
        $types = self::preferenceTypes();

        foreach ($choices as $key => $channels) {
            $type = $types[$key] ?? throw UnknownPreference::type($key);

            foreach ($channels as $channel => $on) {
                if (! $type->allows($channel)) {
                    throw UnknownPreference::channel($key, $channel);
                }

                if ($type->required && ! $on) {
                    throw RequiredPreference::type($key);
                }
            }
        }

        $before = self::preferencesFor($notifiable);
        $changes = [];

        DB::transaction(function () use ($notifiable, $choices, $types, $before, &$changes): void {
            foreach ($choices as $key => $channels) {
                if ($types[$key]->required) {
                    continue;
                }

                foreach ($channels as $channel => $on) {
                    Preference::query()->updateOrCreate([
                        'notifiable_type' => $notifiable->getMorphClass(),
                        'notifiable_id' => (string) $notifiable->getKey(),
                        'type' => $key,
                        'channel' => $channel,
                    ], ['enabled' => (bool) $on]);

                    if (($before[$key][$channel] ?? null) !== (bool) $on) {
                        $changes[$key][$channel] = (bool) $on;
                    }
                }
            }
        });

        if ($changes !== []) {
            PreferencesUpdated::dispatch($notifiable, $changes);
        }

        return $changes;
    }

    /**
     * How a channel is labelled on the preferences page.
     */
    public static function channelLabel(string $channel): string
    {
        $label = config("inbox.channels.{$channel}");

        return is_string($label) ? $label : Str::ucfirst(str_replace(['_', '-'], ' ', $channel));
    }

    // --- Plumbing --------------------------------------------------------

    /**
     * The private channel Laravel broadcasts this person's notifications on.
     */
    public static function broadcastChannel(Model $notifiable): string
    {
        if (method_exists($notifiable, 'receivesBroadcastNotificationsOn')
            && (new ReflectionMethod($notifiable, 'receivesBroadcastNotificationsOn'))->getNumberOfRequiredParameters() === 0) {
            $channel = $notifiable->receivesBroadcastNotificationsOn();
            $channel = is_array($channel) ? reset($channel) : $channel;

            if (is_string($channel) && $channel !== '') {
                return $channel;
            }
        }

        return str_replace('\\', '.', $notifiable::class).'.'.$notifiable->getKey();
    }

    /**
     * Forget registered presenters and preference types. For tests and
     * long-running workers.
     */
    public static function flush(): void
    {
        self::$presenters = [];
        self::$preferenceTypes = [];
    }

    private static function wrap(DatabaseNotification $notification): Notification
    {
        if ($notification instanceof Notification) {
            return $notification;
        }

        $model = (new Notification)->newFromBuilder($notification->getAttributes(), $notification->getConnectionName());
        $model->exists = $notification->exists;

        return $model;
    }
}
