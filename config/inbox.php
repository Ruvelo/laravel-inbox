<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | The inbox lives under `path` (e.g. /inbox) and its preferences under
    | /inbox/preferences. `middleware` wraps every route. The inbox always
    | needs a signed-in user: guests go to your `login` route if you have
    | one, and get a 403 if you don't (a fresh app, before a starter kit),
    | so you don't need `auth` here. Set `routes` to false to register your
    | own (copy routes/web.php).
    |
    */

    'routes' => true,

    'path' => env('INBOX_PATH', 'inbox'),

    'domain' => null,

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | For React, Vue and Inertia front ends. It only ever exposes the signed-
    | in user's own notifications, so it authenticates with the session: keep
    | `web` in the middleware (cookies and CSRF), or use your own guard.
    |
    */

    'api' => [
        'enabled' => (bool) env('INBOX_API', true),
        'prefix' => 'api/inbox',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The inbox and preferences pages use the package's own layout. To put
    | them inside your app's chrome instead, name your layout view here: the
    | pages fill the `section` you choose (and a `title` section).
    |
    */

    'layout' => null,

    'section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | The bell
    |--------------------------------------------------------------------------
    |
    | `<x-inbox::bell />` shows the latest `limit` notifications. It checks
    | the unread count every `poll` seconds while the tab is visible (0 turns
    | polling off). If Laravel Echo is on the page, it also updates the
    | moment a broadcast notification arrives.
    |
    */

    'bell' => [
        'limit' => 6,
        'poll' => 30,
        'echo' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbox page
    |--------------------------------------------------------------------------
    |
    | Notifications per page. Days are grouped in `timezone`, which defaults
    | to your app's.
    |
    */

    'per_page' => 25,

    'timezone' => null,

    /*
    |--------------------------------------------------------------------------
    | Preferences
    |--------------------------------------------------------------------------
    |
    | The notification types people can switch on and off, keyed by the
    | notification's class (or the alias it returns from inboxType()). Each
    | lists the channels the user may toggle; `defaults` says which start
    | on (all of them, unless you say otherwise) and `required` types are
    | shown but can't be turned off. You can also register them with
    | Inbox::preferences([...]) in a service provider.
    |
    |   App\Notifications\InvoicePaid::class => [
    |       'label' => 'Invoice paid',
    |       'description' => 'When a customer pays one of your invoices.',
    |       'group' => 'Billing',
    |       'channels' => ['mail', 'database'],
    |       'defaults' => ['mail' => false],
    |   ],
    |
    */

    'preferences' => [],

    /*
    |--------------------------------------------------------------------------
    | Channel names
    |--------------------------------------------------------------------------
    |
    | How channels are labelled on the preferences page.
    |
    */

    'channels' => [
        'database' => 'In app',
        'mail' => 'Email',
        'broadcast' => 'Live',
        'vonage' => 'SMS',
        'slack' => 'Slack',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Notifications stay in Laravel's own `notifications` table. The inbox
    | adds one table for preferences: {prefix}preferences. Set
    | `run_migrations` to false if you publish the migration and run it
    | yourself.
    |
    */

    'notifications_table' => 'notifications',

    'table_prefix' => 'inbox_',

    'run_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | People
    |--------------------------------------------------------------------------
    |
    | The attribute shown as a person's name, for the page header.
    |
    */

    'user_name_attribute' => 'name',

];
