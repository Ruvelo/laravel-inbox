// Demo mode for the static inbox and preferences pages: what would be a POST
// happens on the page only, and the tabs and type filter work from the URL.
(() => {
    const main = document.querySelector('.inbox-main');
    if (!main) return;

    const note = 'This is a read-only demo, so it resets when you reload.';
    const flash = (text) => {
        let el = main.querySelector('.inbox-flash');
        if (!el) {
            el = document.createElement('p');
            el.className = 'inbox-flash';
            el.setAttribute('role', 'status');
            main.prepend(el);
        }
        el.textContent = text + ' ' + note;
    };

    // --- The inbox ------------------------------------------------------------
    const params = new URLSearchParams(location.search);
    const unreadOnly = params.get('filter') === 'unread';
    const type = params.get('type') || '';
    const items = () => [...main.querySelectorAll('.inbox-item')];

    const icons = {};
    main.querySelectorAll('.inbox-tools form').forEach((form) => {
        const action = form.getAttribute('action') || '';
        if (action.endsWith('/read')) icons.read ??= form.querySelector('svg').outerHTML;
        if (action.endsWith('/unread')) icons.unread ??= form.querySelector('svg').outerHTML;
    });

    const setUnread = (li, unread) => {
        li.classList.toggle('is-unread', unread);
        const form = [...li.querySelectorAll('.inbox-tools form')].find((f) => /\/(un)?read$/.test(f.getAttribute('action') || ''));
        const title = li.querySelector('.inbox-title');
        title.querySelector('.inbox-sr')?.remove();
        if (unread) title.insertAdjacentHTML('afterbegin', '<span class="inbox-sr">Unread: </span>');
        if (!form) return;
        form.setAttribute('action', form.getAttribute('action').replace(/\/(un)?read$/, unread ? '/read' : '/unread'));
        const button = form.querySelector('button');
        const label = unread ? 'Mark as read' : 'Mark as unread';
        button.title = label;
        button.innerHTML = (unread ? icons.read : icons.unread) || '';
        button.insertAdjacentHTML('beforeend', '<span class="inbox-sr"></span>');
        button.lastChild.textContent = label;
    };

    const counts = () => {
        const n = items().filter((li) => li.classList.contains('is-unread')).length;
        const sub = main.querySelector('.inbox-sub');
        if (sub) sub.innerHTML = n > 0 ? 'You have <strong>' + n + ' unread</strong>.' : 'You’re all caught up.';
        const tab = main.querySelector('.inbox-tabs a:last-child');
        if (tab) {
            tab.querySelector('.inbox-count')?.remove();
            if (n > 0) tab.insertAdjacentHTML('beforeend', '<span class="inbox-count">' + n + '</span>');
        }
        const all = main.querySelector('form[action$="/read-all"] button');
        if (all) all.disabled = n === 0;
    };

    const apply = () => {
        let shown = 0;
        items().forEach((li) => {
            const show = (!unreadOnly || li.classList.contains('is-unread')) && (!type || li.dataset.inboxType === type);
            li.hidden = !show;
            shown += show ? 1 : 0;
        });
        main.querySelectorAll('.inbox-day').forEach((day) => {
            day.hidden = !day.querySelector('.inbox-item:not([hidden])');
        });
        let empty = main.querySelector('[data-demo-empty]');
        if (!shown && !empty && main.querySelector('.inbox-toolbar')) {
            main.querySelector('.inbox-toolbar').insertAdjacentHTML('afterend',
                '<div class="inbox-empty" data-demo-empty><h2>You’re all caught up</h2><p>Nothing ' + (unreadOnly ? 'unread' : 'here') + '. <a href="./">See everything</a></p></div>');
        } else if (shown && empty) {
            empty.remove();
        }
    };

    if (main.querySelector('.inbox-tabs')) {
        const [all, unread] = main.querySelectorAll('.inbox-tabs a');
        const withType = (url) => { const u = new URL(url, location.href); type ? u.searchParams.set('type', type) : u.searchParams.delete('type'); return u.pathname + u.search; };
        all.href = withType(all.href);
        unread.href = withType(unread.href);
        if (unreadOnly) all.removeAttribute('aria-current'); else unread.removeAttribute('aria-current');
        if (unreadOnly) unread.setAttribute('aria-current', 'page'); else all.setAttribute('aria-current', 'page');
        const select = main.querySelector('[data-inbox-filter] select');
        if (select) select.value = type;
        const filterForm = main.querySelector('[data-inbox-filter]');
        if (filterForm && unreadOnly && !filterForm.querySelector('[name=filter]')) {
            filterForm.insertAdjacentHTML('afterbegin', '<input type="hidden" name="filter" value="unread">');
        }
        apply();
    }

    main.addEventListener('click', (e) => {
        const link = e.target.closest('.inbox-title');
        if (!link) return;
        e.preventDefault();
        const li = link.closest('.inbox-item');
        if (li.classList.contains('is-unread')) setUnread(li, false);
        counts();
        apply();
        flash('Marked as read. In your app, this opens the page the notification is about.');
    });

    main.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('[data-inbox-filter]')) return;
        e.preventDefault();
        const action = form.getAttribute('action') || '';
        const method = (form.querySelector('[name=_method]')?.value || form.getAttribute('method') || 'get').toUpperCase();
        const li = form.closest('.inbox-item');

        if (form.querySelector('.inbox-switch, [role=switch]') || method === 'PUT') {
            flash('Preferences saved.');
            main.querySelector('.inbox-flash')?.scrollIntoView({ block: 'nearest' });
            return;
        }
        if (method === 'DELETE' && li) {
            li.remove();
            flash('Notification deleted.');
        } else if (action.endsWith('/read-all')) {
            items().forEach((item) => item.classList.contains('is-unread') && setUnread(item, false));
            flash('Marked everything as read.');
        } else if (action.endsWith('/unread') && li) {
            setUnread(li, true);
            flash('Marked as unread.');
        } else if (action.endsWith('/read') && li) {
            setUnread(li, false);
            flash('Marked as read.');
        }
        counts();
        apply();
    });
})();
