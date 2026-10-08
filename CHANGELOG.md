# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-10-08

### Added

- Laravel Boost guidelines in `resources/boost/guidelines/core.blade.php`: `php artisan boost:install` offers them to apps that use Laravel Boost, so AI coding agents use the package the intended way.

## [1.0.0] - 2026-10-08

First release.

### Added

- Works on Laravel's own `database` notification channel and `notifications` table: existing notifications show up unchanged.
- `<x-inbox::bell />`: unread badge (99+ cap), a dropdown with the latest notifications, mark all as read, view all. A plain link without JavaScript; with it, an accessible dropdown, background read marks, a polled unread count that pauses in hidden tabs, and live updates over Laravel Echo when it's on the page.
- The inbox page: All and Unread tabs, grouped by day in the app's timezone, filter by type, mark read or unread, delete, mark all as read, pagination and empty states. Opening an item marks it read and follows its link through a redirect.
- Preferences page and storage: per type and channel, with labels, descriptions, groups, defaults and required types.
- `RespectsInboxPreferences` trait for filtering channels in `via()`.
- Presentation by convention (`title`, `body`, `url`, `icon`, `actor`), the `InboxNotification` contract with `toInbox()`, `Inbox::present()` presenters, and a humanised class name as the fallback.
- Session-authenticated JSON API for notifications and preferences.
- PHP API: `Inbox::unreadCount()`, `latest()`, `query()`, `find()`, `markRead()`, `markUnread()`, `markAllRead()`, `delete()`, `message()`, `present()`, `preferences()`, `wants()`, `filterChannels()`, `preferencesFor()`, `updatePreferences()`.
- `NotificationRead`, `NotificationUnread`, `AllNotificationsRead`, `NotificationDeleted` and `PreferencesUpdated` events; `UnknownPreference`, `RequiredPreference` and `InvalidPreferenceType` exceptions.
- `Notification::factory()` for tests in host apps.
- Guests are sent to the login page, or get a 403 in apps without one, never a 500.
- Ruvelo house style UI: light and dark, scoped styles, no build step, themable through CSS variables, and the option to use the app's own layout.

[Unreleased]: https://github.com/Ruvelo/laravel-inbox/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/Ruvelo/laravel-inbox/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Ruvelo/laravel-inbox/releases/tag/v1.0.0
