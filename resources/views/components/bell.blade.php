@once
<style>
    @include('inbox::partials.tokens', ['scope' => '.inbox-bell'])
    .inbox-bell { position: relative; display: inline-flex; font: 400 15px/1.5 var(--inbox-sans); color: var(--inbox-ink); -webkit-font-smoothing: antialiased; }
    .inbox-bell *, .inbox-bell *::before, .inbox-bell *::after { box-sizing: border-box; }
    .inbox-bell :focus-visible { outline: 2px solid var(--inbox-accent); outline-offset: 2px; }
    .inbox-bell .inbox-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .inbox-bell-button { position: relative; display: grid; place-items: center; width: 2.375rem; height: 2.375rem; border-radius: var(--inbox-radius); color: var(--inbox-text-2); text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background-color .15s, color .15s; }
    .inbox-bell-button:hover, .inbox-bell-button[aria-expanded="true"] { background: var(--inbox-muted); color: var(--inbox-ink); text-decoration: none; }
    .inbox-bell-button svg { width: 1.25rem; height: 1.25rem; }
    .inbox-bell-badge { position: absolute; top: .1rem; right: .05rem; min-width: 1.15rem; height: 1.15rem; padding: 0 .3rem; border-radius: 999px; background: var(--inbox-accent); color: var(--inbox-on-accent); border: 2px solid var(--inbox-bg); font: 600 .625rem/calc(1.15rem - 4px) var(--inbox-sans); text-align: center; font-variant-numeric: tabular-nums; }
    .inbox-bell-badge[hidden] { display: none; }
    .inbox-bell-panel { position: absolute; z-index: 1000; top: calc(100% + .5rem); right: 0; width: min(24rem, calc(100vw - 32px)); background: var(--inbox-bg); border: 1px solid var(--inbox-line); border-radius: var(--inbox-radius-lg); box-shadow: 0 24px 60px -32px color-mix(in srgb, var(--inbox-accent) 60%, transparent); overflow: hidden; text-align: left; }
    .inbox-bell-panel[hidden] { display: none; }
    .inbox-bell-panel:focus { outline: none; }
    .inbox-bell-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem .75rem; border-bottom: 1px solid var(--inbox-line); }
    .inbox-bell-head h2 { margin: 0; font: 600 .95rem/1.3 var(--inbox-sans); letter-spacing: -.01em; color: var(--inbox-ink); }
    .inbox-bell-head form { margin: 0; }
    .inbox-bell-link { appearance: none; background: none; border: 0; padding: .2rem .35rem; margin: -.2rem -.35rem; border-radius: 6px; font: 500 .8rem/1.3 var(--inbox-sans); color: var(--inbox-accent); cursor: pointer; text-decoration: none; box-shadow: none; }
    .inbox-bell-link:hover { background: var(--inbox-accent-soft); color: var(--inbox-accent-ink); text-decoration: none; }
    .inbox-bell-link[disabled] { color: var(--inbox-text-3); background: none; cursor: default; }
    .inbox-bell-list { list-style: none; margin: 0; padding: .35rem; max-height: min(26rem, 65vh); overflow-y: auto; scrollbar-width: thin; }
    .inbox-bell-list li { margin: 0; padding: 0; }
    .inbox-bell-item { position: relative; display: grid; grid-template-columns: 2rem minmax(0, 1fr) .5rem; gap: .75rem; align-items: start; padding: .65rem .65rem .7rem; border-radius: 10px; color: var(--inbox-ink); text-decoration: none; }
    .inbox-bell-item:hover { background: var(--inbox-subtle); text-decoration: none; }
    .inbox-bell-item.is-unread { background: color-mix(in srgb, var(--inbox-accent-soft) 55%, transparent); }
    .inbox-bell-item.is-unread:hover { background: var(--inbox-accent-soft); }
    .inbox-bell .inbox-avatar { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--inbox-accent-soft); color: var(--inbox-accent-ink); font: 600 .7rem/1 var(--inbox-sans); flex: none; }
    .inbox-bell .inbox-avatar--icon { background: var(--inbox-muted); color: var(--inbox-text-2); font-size: .95rem; }
    .inbox-bell-text { display: grid; gap: .1rem; min-width: 0; }
    .inbox-bell-title { font-size: .875rem; line-height: 1.35; font-weight: 500; color: var(--inbox-text-2); overflow-wrap: anywhere; }
    .inbox-bell-item.is-unread .inbox-bell-title { color: var(--inbox-ink); font-weight: 600; }
    .inbox-bell-body { font-size: .8125rem; line-height: 1.45; color: var(--inbox-text-3); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    .inbox-bell-time { font-size: .75rem; color: var(--inbox-text-3); margin-top: .15rem; }
    .inbox-bell-dot { width: .5rem; height: .5rem; margin-top: .4rem; border-radius: 50%; background: var(--inbox-accent); }
    .inbox-bell-empty { display: grid; gap: .2rem; padding: 2rem 1rem; text-align: center; font-size: .85rem; color: var(--inbox-text-3); }
    .inbox-bell-empty strong { color: var(--inbox-ink); font-weight: 600; font-size: .9rem; }
    .inbox-bell-foot { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .7rem 1rem; border-top: 1px solid var(--inbox-line); background: var(--inbox-subtle); }
    .inbox-bell-foot .inbox-bell-link { color: var(--inbox-text-2); }
    .inbox-bell-foot .inbox-bell-link:first-child { color: var(--inbox-accent); }
    @media (max-width: 30rem) {
        .inbox-bell-panel { position: fixed; left: 16px; right: 16px; width: auto; top: 4rem; }
    }
</style>
@endonce

<div {{ $attributes->class('inbox-bell') }}
     data-inbox-bell
     data-unread="{{ $unread }}"
     data-poll="{{ $poll }}"
     data-count-url="{{ route('inbox.count') }}"
     data-list-url="{{ route('inbox.bell', ['limit' => $limit]) }}"
     data-csrf="{{ csrf_token() }}"
     @if ($channel) data-channel="{{ $channel }}" @endif>
    <a class="inbox-bell-button" href="{{ route('inbox.index') }}" data-inbox-toggle aria-controls="{{ $id }}"
       aria-label="{{ $label }}{{ $unread > 0 ? ', '.$unread.' unread' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <span class="inbox-bell-badge" data-inbox-badge aria-hidden="true" @if ($unread === 0) hidden @endif>{{ $unread > 99 ? '99+' : $unread }}</span>
    </a>

    <div class="inbox-bell-panel" id="{{ $id }}" data-inbox-panel role="region" aria-labelledby="{{ $id }}-title" tabindex="-1" hidden>
        <div class="inbox-bell-head">
            <h2 id="{{ $id }}-title">{{ $label }}</h2>
            <form method="post" action="{{ route('inbox.read-all') }}" data-inbox-read-all>
                @csrf
                <button type="submit" class="inbox-bell-link" @disabled($unread === 0)>Mark all as read</button>
            </form>
        </div>
        <ul class="inbox-bell-list" data-inbox-list>
            @include('inbox::partials.bell-list', ['notifications' => $notifications])
        </ul>
        <div class="inbox-bell-foot">
            <a class="inbox-bell-link" href="{{ route('inbox.index') }}">View all</a>
            <a class="inbox-bell-link" href="{{ route('inbox.preferences') }}">Preferences</a>
        </div>
    </div>
</div>

@once
<script>
(() => {
    const SEL = '[data-inbox-bell]';
    const fmt = (n) => (n > 99 ? '99+' : String(n));

    function init(root) {
        if (root.dataset.inboxReady) return;
        root.dataset.inboxReady = '1';

        const button = root.querySelector('[data-inbox-toggle]');
        const panel = root.querySelector('[data-inbox-panel]');
        const badge = root.querySelector('[data-inbox-badge]');
        const list = root.querySelector('[data-inbox-list]');
        const readAll = root.querySelector('[data-inbox-read-all]');
        const label = root.querySelector('h2')?.textContent.trim() || 'Notifications';
        const demo = 'inboxDemo' in root.dataset;
        const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': root.dataset.csrf || '' };
        let count = Number(root.dataset.unread) || 0;
        let stale = false;

        button.setAttribute('role', 'button');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-haspopup', 'true');

        const setCount = (n) => {
            count = Math.max(0, n);
            badge.textContent = fmt(count);
            badge.hidden = count === 0;
            button.setAttribute('aria-label', label + (count > 0 ? ', ' + count + ' unread' : ''));
            const submit = readAll?.querySelector('button');
            if (submit) submit.disabled = count === 0;
        };

        const markItemRead = (item) => {
            if (!item.hasAttribute('data-unread')) return false;
            item.removeAttribute('data-unread');
            item.classList.remove('is-unread');
            item.querySelector('.inbox-bell-dot')?.remove();
            return true;
        };

        const isOpen = () => !panel.hidden;

        const open = () => {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            if (matchMedia('(max-width: 30rem)').matches) {
                panel.style.top = Math.round(button.getBoundingClientRect().bottom + 8) + 'px';
            } else {
                panel.style.top = '';
            }
            if (stale) refresh();
            panel.focus({ preventScroll: true });
        };

        const close = (refocus) => {
            if (!isOpen()) return;
            panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            if (refocus) button.focus();
        };

        button.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            isOpen() ? close(false) : open();
        });
        button.addEventListener('keydown', (e) => {
            if (e.key === ' ') { e.preventDefault(); button.click(); }
            if (e.key === 'ArrowDown' && !isOpen()) { e.preventDefault(); open(); }
        });
        root.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen()) { e.preventDefault(); close(true); }
        });
        document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(false); });
        root.addEventListener('focusout', (e) => { if (e.relatedTarget && !root.contains(e.relatedTarget)) close(false); });

        // Opening an item marks it read in the background, then goes straight to it.
        list.addEventListener('click', (e) => {
            const item = e.target.closest('[data-inbox-item]');
            if (!item) return;
            const modified = e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1;
            const wasUnread = markItemRead(item);
            if (wasUnread) setCount(count - 1);
            if (demo) { e.preventDefault(); return; }
            if (modified || !item.dataset.url) return; // the server-side link marks it read itself
            e.preventDefault();
            if (wasUnread) {
                fetch(item.dataset.readUrl, { method: 'POST', headers, credentials: 'same-origin', keepalive: true }).catch(() => {});
            }
            location.assign(item.dataset.url);
        });

        readAll?.addEventListener('submit', (e) => {
            e.preventDefault();
            list.querySelectorAll('[data-inbox-item][data-unread]').forEach(markItemRead);
            setCount(0);
            if (demo) return;
            fetch(readAll.action, { method: 'POST', headers, credentials: 'same-origin' })
                .then((r) => (r.ok ? r.json() : null))
                .then((json) => { if (json && typeof json.unread === 'number') setCount(json.unread); })
                .catch(() => {});
        });

        const refresh = () => {
            stale = false;
            return fetch(root.dataset.listUrl, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then((r) => {
                    if (!r.ok) throw r;
                    const n = Number(r.headers.get('X-Inbox-Unread'));
                    if (!Number.isNaN(n)) setCount(n);
                    return r.text();
                })
                .then((html) => { list.innerHTML = html; })
                .catch(() => { stale = true; });
        };

        const check = () => {
            if (demo) return;
            fetch(root.dataset.countUrl, { headers, credentials: 'same-origin' })
                .then((r) => (r.ok ? r.json() : null))
                .then((json) => {
                    if (!json || typeof json.unread !== 'number' || json.unread === count) return;
                    setCount(json.unread);
                    isOpen() ? refresh() : (stale = true);
                })
                .catch(() => {});
        };

        const poll = Number(root.dataset.poll) || 0;
        if (poll > 0 && !demo) {
            setInterval(() => { if (!document.hidden) check(); }, poll * 1000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
        }

        // Live updates when Laravel Echo is on the page.
        const listen = () => {
            const channel = root.dataset.channel;
            if (!channel || demo || root.dataset.inboxLive || !window.Echo || typeof window.Echo.private !== 'function') return;
            root.dataset.inboxLive = '1';
            window.Echo.private(channel).notification(() => { stale = true; isOpen() ? refresh() : check(); });
        };
        listen();
        window.addEventListener('load', listen, { once: true });
    }

    const boot = () => document.querySelectorAll(SEL).forEach(init);
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
})();
</script>
@endonce
