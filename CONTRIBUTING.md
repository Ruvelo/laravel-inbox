# Contributing to Laravel Inbox

Thanks for helping! Bug reports, docs fixes and features are all welcome.

## Before you start

- **Bugs:** [open an issue](https://github.com/Ruvelo/laravel-inbox/issues/new/choose) with the steps to reproduce, or go straight to a pull request with a failing test.
- **Features:** open an issue first if it's more than a small change, so we can agree on the shape before you spend time on it.
- **Security issues:** don't open an issue. See [SECURITY.md](https://github.com/Ruvelo/.github/blob/main/SECURITY.md).

## Setup

You need PHP 8.3+ and Composer. No database server: tests run on in-memory SQLite.

```bash
git clone https://github.com/<you>/laravel-inbox && cd laravel-inbox
composer install
composer check
```

`composer check` runs exactly what CI runs:

| Command | What it does |
|---|---|
| `composer test` | PHPUnit, through Orchestra Testbench |
| `composer lint` | Code style check (Laravel Pint) |
| `composer format` | Fix code style |
| `composer analyse` | Static analysis (PHPStan level 8 with Larastan) |
| `composer demo` | Build the static demo into `build/` |

## Making a change

1. Branch from `main`.
2. Write a test that fails without your change. Feature tests live in `tests/Feature`, unit tests in `tests/Unit`.
3. Keep the public API stable: `Ruvelo\Inbox\Inbox`, `InboxMessage`, the `InboxNotification` contract, the `RespectsInboxPreferences` trait, the models, events, exceptions, config keys, routes and the JSON shapes. If you must change one, say so in the PR.
4. Run `composer format` and `composer check`.
5. Add a line under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request. Screenshots help for anything visual.

## Where things live

```
src/Inbox.php                    The public PHP API, and the one write path for reads, deletes and preferences
src/InboxMessage.php             How a notification looks: the value object presenters return
src/Notifications/               The RespectsInboxPreferences trait, and the database channel that stores toInbox()
src/Models/                      Notification (Laravel's table) and Preference
src/View/Components/Bell.php     <x-inbox::bell />
src/Http/Controllers/            The pages, and Api/ for the JSON API
resources/views/                 Blade views; components/bell.blade.php carries the bell's styles and script
demo/                            The demo app (deployed to GitHub Pages), its build script and screenshots
```

## Style

- `declare(strict_types=1)` everywhere, typed properties and return types.
- No `@phpstan-ignore` or baseline entries: fix the cause.
- Comments explain *why*, not *what*.
- UI follows the [Ruvelo house style](https://github.com/Ruvelo/.github/blob/main/BRAND.md): CSS variables, no build step, works without JavaScript, light and dark. The bell renders inside other people's apps: keep its class names prefixed with `inbox-` and its styles scoped.

By contributing you agree that your work is released under the [MIT license](LICENSE) and that you'll follow the [code of conduct](https://github.com/Ruvelo/.github/blob/main/CODE_OF_CONDUCT.md).
