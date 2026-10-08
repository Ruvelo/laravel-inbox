<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        @include('inbox::partials.tokens', ['scope' => 'body.inbox-page'])
        body.inbox-page { margin: 0; background: var(--inbox-bg); color-scheme: light dark; }
        body.inbox-page::before { content: ""; position: fixed; inset: 0 0 auto; height: 3px; z-index: 20; background: linear-gradient(90deg, var(--inbox-accent), var(--inbox-accent-2)); }
        .inbox-bar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem; padding: .75rem max(16px, calc((100% - 72rem) / 2 + 24px)); background: color-mix(in srgb, var(--inbox-bg) 88%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--inbox-line); }
        .inbox-page a.inbox-brand { display: inline-flex; align-items: center; gap: .6rem; font-weight: 600; font-size: .95rem; color: var(--inbox-ink); letter-spacing: -.01em; }
        .inbox-page a.inbox-brand:hover { text-decoration: none; }
        .inbox-mark { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: 7px; background: linear-gradient(135deg, var(--inbox-accent), var(--inbox-accent-2)); color: #fff; font-size: .85rem; font-weight: 700; box-shadow: 0 4px 12px -4px color-mix(in srgb, var(--inbox-accent) 60%, transparent); }
        .inbox-bar nav { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; align-items: center; margin-left: auto; font-size: .9rem; }
        .inbox-page .inbox-bar nav a { color: var(--inbox-text-2); }
        .inbox-page .inbox-bar nav a:hover, .inbox-page .inbox-bar nav a[aria-current] { color: var(--inbox-ink); text-decoration: none; }
        .inbox-page .inbox-bar nav a[aria-current] { font-weight: 500; }
        .inbox-foot { max-width: 46rem; margin: 0 auto; padding: 0 16px 2.5rem; color: var(--inbox-text-3); font-size: .8rem; }
        .inbox-page .inbox-foot a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }
    </style>
    @stack('inbox-head')
</head>
<body class="inbox-page">
    <header class="inbox-bar">
        <a class="inbox-brand" href="{{ url('/') }}"><span class="inbox-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) config('app.name'), 0, 1)) }}</span>{{ config('app.name') }}</a>
        <nav aria-label="Notifications">
            <a href="{{ route('inbox.index') }}" @if (request()->routeIs('inbox.index')) aria-current="page" @endif>Notifications</a>
            <a href="{{ route('inbox.preferences') }}" @if (request()->routeIs('inbox.preferences')) aria-current="page" @endif>Preferences</a>
        </nav>
    </header>

    @yield('content')

    <footer class="inbox-foot">Powered by <a href="https://github.com/Ruvelo/laravel-inbox">Laravel Inbox</a> by Ruvelo</footer>
</body>
</html>
