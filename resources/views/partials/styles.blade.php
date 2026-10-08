@once
<style>
    @include('inbox::partials.tokens', ['scope' => '.inbox-page'])
    .inbox-page { color: var(--inbox-ink); font: 400 15px/1.6 var(--inbox-sans); -webkit-font-smoothing: antialiased; }
    .inbox-page *, .inbox-page *::before, .inbox-page *::after { box-sizing: border-box; }
    .inbox-page a { color: var(--inbox-accent); text-decoration: none; }
    .inbox-page a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .inbox-page :focus-visible { outline: 2px solid var(--inbox-accent); outline-offset: 2px; border-radius: 4px; }
    .inbox-page [hidden] { display: none !important; }
    .inbox-page .inbox-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .inbox-page h1, .inbox-page h2 { font-family: var(--inbox-sans); font-weight: 600; letter-spacing: -.02em; line-height: 1.25; color: var(--inbox-ink); }
    .inbox-page h1 { font-size: 1.875rem; margin: 0; }

    .inbox-main { max-width: 46rem; margin: 0 auto; padding: 2.5rem 16px 4rem; }
    .inbox-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.75rem; }
    .inbox-sub { margin: .35rem 0 0; color: var(--inbox-text-3); font-size: .9rem; }
    .inbox-sub strong { color: var(--inbox-accent-ink); font-weight: 600; }
    .inbox-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .inbox-actions form { margin: 0; }

    .inbox-btn { display: inline-flex; align-items: center; gap: .45rem; font: 500 .875rem/1 var(--inbox-sans); padding: .6rem .95rem; border-radius: var(--inbox-radius); border: 1px solid var(--inbox-accent); background: var(--inbox-accent); color: var(--inbox-on-accent); cursor: pointer; box-shadow: 0 6px 16px -8px color-mix(in srgb, var(--inbox-accent) 70%, transparent); text-decoration: none; }
    .inbox-page a.inbox-btn { color: var(--inbox-on-accent); }
    .inbox-btn:hover { background: var(--inbox-accent-ink); border-color: var(--inbox-accent-ink); text-decoration: none; }
    .inbox-page a.inbox-btn:hover { text-decoration: none; }
    .inbox-btn svg { width: 1rem; height: 1rem; }
    .inbox-btn--quiet, .inbox-page a.inbox-btn--quiet { background: var(--inbox-bg); color: var(--inbox-ink); border-color: var(--inbox-line); box-shadow: none; }
    .inbox-btn--quiet:hover, .inbox-page a.inbox-btn--quiet:hover { background: var(--inbox-subtle); border-color: var(--inbox-text-3); color: var(--inbox-ink); }
    .inbox-btn[disabled] { opacity: .5; cursor: default; }
    .inbox-btn[disabled]:hover { background: var(--inbox-bg); border-color: var(--inbox-line); }

    .inbox-flash { display: flex; align-items: center; gap: .6rem; background: var(--inbox-subtle); border: 1px solid var(--inbox-line); padding: .6rem .9rem; border-radius: var(--inbox-radius); margin: 0 0 1.5rem; font-size: .9rem; }
    .inbox-flash::before { content: ""; width: .5rem; height: .5rem; border-radius: 50%; background: var(--inbox-accent); flex: none; }

    .inbox-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; padding-bottom: .9rem; border-bottom: 1px solid var(--inbox-line); }
    .inbox-tabs { display: inline-flex; gap: .25rem; padding: .25rem; background: var(--inbox-muted); border-radius: 10px; }
    .inbox-page .inbox-tabs a { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .85rem; border-radius: 7px; color: var(--inbox-text-2); font-size: .875rem; font-weight: 500; }
    .inbox-page .inbox-tabs a:hover { color: var(--inbox-ink); text-decoration: none; }
    .inbox-page .inbox-tabs a[aria-current] { background: var(--inbox-bg); color: var(--inbox-ink); box-shadow: 0 0 0 1px var(--inbox-line); }
    .inbox-count { display: inline-grid; place-items: center; min-width: 1.35rem; height: 1.35rem; padding: 0 .4rem; border-radius: 999px; background: var(--inbox-accent-soft); color: var(--inbox-accent-ink); font-size: .7rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .inbox-filter { display: flex; gap: .5rem; align-items: center; margin: 0; }
    .inbox-filter select { font: 400 .875rem/1.2 var(--inbox-sans); color: var(--inbox-ink); background: var(--inbox-bg); border: 1px solid var(--inbox-line); border-radius: var(--inbox-radius); padding: .5rem 2rem .5rem .75rem; max-width: 15rem; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2374748b' stroke-width='1.5'%3E%3Cpath d='m4 6 4 4 4-4'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .6rem center; background-size: 1rem; }
    .inbox-filter select:focus { outline: none; border-color: var(--inbox-accent); box-shadow: 0 0 0 3px var(--inbox-accent-soft); }

    .inbox-day h2 { font-size: .8rem; font-weight: 600; letter-spacing: 0; color: var(--inbox-text-3); margin: 1.75rem 0 .5rem; }
    .inbox-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .25rem; }
    .inbox-item { position: relative; display: grid; grid-template-columns: 2.5rem minmax(0, 1fr) auto; gap: .9rem; align-items: start; padding: .9rem 1rem; border-radius: var(--inbox-radius-lg); border: 1px solid transparent; }
    .inbox-item:hover { background: var(--inbox-subtle); }
    .inbox-item.is-unread { background: color-mix(in srgb, var(--inbox-accent-soft) 60%, transparent); }
    .inbox-item.is-unread:hover { background: var(--inbox-accent-soft); }
    .inbox-item.is-unread::before { content: ""; position: absolute; left: -.35rem; top: 1.45rem; width: .45rem; height: .45rem; border-radius: 50%; background: var(--inbox-accent); }
    .inbox-page .inbox-avatar { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 999px; background: var(--inbox-accent-soft); color: var(--inbox-accent-ink); font-size: .8rem; font-weight: 600; }
    .inbox-page .inbox-avatar--icon { background: var(--inbox-muted); font-size: 1.15rem; }
    .inbox-item.is-unread .inbox-avatar--icon { background: var(--inbox-bg); }
    .inbox-body { min-width: 0; }
    .inbox-page .inbox-title { display: block; font-weight: 500; color: var(--inbox-text-2); line-height: 1.4; overflow-wrap: anywhere; }
    .inbox-item.is-unread .inbox-title { font-weight: 600; color: var(--inbox-ink); }
    .inbox-page .inbox-title:hover { color: var(--inbox-accent-ink); text-decoration: none; }
    .inbox-page .inbox-title::after { content: ""; position: absolute; inset: 0; border-radius: inherit; }
    .inbox-text { margin: .2rem 0 0; color: var(--inbox-text-2); font-size: .9rem; line-height: 1.55; overflow-wrap: anywhere; }
    .inbox-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .5rem; margin-top: .4rem; color: var(--inbox-text-3); font-size: .8rem; }
    .inbox-meta > * + *::before { content: "·"; margin-right: .5rem; }
    .inbox-chip { display: inline-block; padding: .05rem .55rem; border-radius: 999px; background: var(--inbox-muted); color: var(--inbox-text-2); font-size: .75rem; font-weight: 500; }
    .inbox-meta > .inbox-chip::before { content: none; }
    .inbox-tools { position: relative; z-index: 1; display: flex; gap: .15rem; opacity: .55; transition: opacity .15s; }
    .inbox-item:hover .inbox-tools, .inbox-item:focus-within .inbox-tools { opacity: 1; }
    .inbox-tools form { margin: 0; }
    .inbox-icon { display: grid; place-items: center; width: 2rem; height: 2rem; padding: 0; border-radius: var(--inbox-radius); border: 1px solid transparent; background: transparent; color: var(--inbox-text-3); cursor: pointer; box-shadow: none; }
    .inbox-icon svg { width: 1rem; height: 1rem; }
    .inbox-icon:hover { background: var(--inbox-bg); border-color: var(--inbox-line); color: var(--inbox-ink); }
    .inbox-icon--danger:hover { color: var(--inbox-danger); border-color: color-mix(in srgb, var(--inbox-danger) 35%, transparent); background: var(--inbox-danger-soft); }

    .inbox-empty { text-align: center; padding: 4rem 1rem 3rem; }
    .inbox-empty-mark { display: inline-grid; place-items: center; width: 3.5rem; height: 3.5rem; border-radius: 999px; background: var(--inbox-accent-soft); color: var(--inbox-accent); margin-bottom: 1rem; }
    .inbox-empty-mark svg { width: 1.5rem; height: 1.5rem; }
    .inbox-empty h2 { font-size: 1.125rem; margin: 0 0 .35rem; }
    .inbox-empty p { margin: 0 auto; max-width: 26rem; color: var(--inbox-text-3); font-size: .9rem; }

    .inbox-pages { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--inbox-line); font-size: .875rem; color: var(--inbox-text-3); }
    .inbox-pages div { display: flex; gap: .5rem; }

    .inbox-prefs-intro { margin: .35rem 0 0; color: var(--inbox-text-3); font-size: .9rem; max-width: 34rem; }
    .inbox-prefs-group { margin-top: 2rem; }
    .inbox-prefs-group h2 { font-size: 1rem; margin: 0 0 .75rem; }
    .inbox-prefs { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--inbox-line); border-radius: var(--inbox-radius-lg); overflow: hidden; font-size: .9rem; }
    .inbox-prefs th, .inbox-prefs td { padding: .9rem 1rem; text-align: left; vertical-align: middle; border-top: 1px solid var(--inbox-line); }
    .inbox-prefs thead th { border-top: 0; background: var(--inbox-subtle); font-size: .8rem; font-weight: 500; color: var(--inbox-text-3); }
    .inbox-prefs .inbox-prefs-channel { width: 5.5rem; text-align: center; }
    .inbox-prefs tbody th { font-weight: 400; }
    .inbox-prefs-label { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-weight: 500; color: var(--inbox-ink); }
    .inbox-prefs-desc { display: block; margin-top: .15rem; color: var(--inbox-text-3); font-size: .85rem; line-height: 1.5; }
    .inbox-chip--accent { background: var(--inbox-accent-soft); color: var(--inbox-accent-ink); }
    .inbox-none { color: var(--inbox-text-3); }
    .inbox-switch { position: relative; display: inline-block; width: 2.25rem; height: 1.3rem; vertical-align: middle; }
    .inbox-switch input { appearance: none; margin: 0; position: absolute; inset: 0; width: 100%; height: 100%; border-radius: 999px; background: var(--inbox-line); cursor: pointer; transition: background-color .15s; }
    .inbox-switch input::after { content: ""; position: absolute; top: .15rem; left: .15rem; width: 1rem; height: 1rem; border-radius: 50%; background: #fff; transition: transform .15s; box-shadow: 0 1px 2px rgb(22 22 42 / .2); }
    .inbox-switch input:checked { background: var(--inbox-accent); }
    .inbox-switch input:checked::after { transform: translateX(.95rem); }
    .inbox-switch input:disabled { opacity: .55; cursor: not-allowed; }
    .inbox-switch input:focus-visible { outline: 2px solid var(--inbox-accent); outline-offset: 2px; }
    .inbox-prefs-save { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; margin-top: 1.75rem; }
    .inbox-prefs-save p { margin: 0; color: var(--inbox-text-3); font-size: .85rem; }

    @media (max-width: 40rem) {
        .inbox-main { padding-top: 1.5rem; }
        .inbox-page h1 { font-size: 1.5rem; }
        .inbox-item { grid-template-columns: 2.25rem minmax(0, 1fr); padding: .85rem .75rem; gap: .75rem; }
        .inbox-page .inbox-avatar { width: 2.25rem; height: 2.25rem; }
        .inbox-tools { grid-column: 2; opacity: 1; margin: -.25rem 0 0 -.5rem; }
        .inbox-item.is-unread::before { left: -.2rem; }
        .inbox-toolbar { align-items: stretch; }
        .inbox-filter { flex: 1 1 100%; }
        .inbox-filter select { flex: 1; max-width: none; }
        .inbox-prefs th, .inbox-prefs td { padding: .8rem .65rem; }
        .inbox-prefs .inbox-prefs-channel { width: 4rem; }
    }
</style>
@endonce
