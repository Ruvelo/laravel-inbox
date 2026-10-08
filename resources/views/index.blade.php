@extends($inboxLayout)

@section('title', 'Notifications')

@section($inboxSection)
@include('inbox::partials.styles')
<div class="inbox-page">
    <main class="inbox-main">
        @if (session('inbox.status'))
            <p class="inbox-flash" role="status">{{ session('inbox.status') }}</p>
        @endif

        <div class="inbox-head">
            <div>
                <h1>Notifications</h1>
                <p class="inbox-sub">
                    @if ($unread > 0)
                        You have <strong>{{ $unread }} unread</strong>.
                    @else
                        You’re all caught up.
                    @endif
                </p>
            </div>
            <div class="inbox-actions">
                <form method="post" action="{{ route('inbox.read-all') }}">
                    @csrf
                    <button type="submit" class="inbox-btn inbox-btn--quiet" @disabled($unread === 0)>
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m1.5 8.5 3 3 6-7"/><path d="m8 11.5 6-7"/></svg>
                        Mark all as read
                    </button>
                </form>
                <a class="inbox-btn inbox-btn--quiet" href="{{ route('inbox.preferences') }}">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M2 4h7M13 4h1M2 12h1M7 12h7"/><circle cx="11" cy="4" r="2"/><circle cx="5" cy="12" r="2"/></svg>
                    Preferences
                </a>
            </div>
        </div>

        <div class="inbox-toolbar">
            <nav class="inbox-tabs" aria-label="Show">
                <a href="{{ route('inbox.index', array_filter(['type' => $type])) }}" @if ($filter === 'all') aria-current="page" @endif>All</a>
                <a href="{{ route('inbox.index', array_filter(['filter' => 'unread', 'type' => $type])) }}" @if ($filter === 'unread') aria-current="page" @endif>
                    Unread @if ($unread > 0)<span class="inbox-count">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                </a>
            </nav>

            @if (count($types) > 1 || $type !== null)
                <form class="inbox-filter" method="get" action="{{ route('inbox.index') }}" data-inbox-filter>
                    @if ($filter === 'unread')
                        <input type="hidden" name="filter" value="unread">
                    @endif
                    <label class="inbox-sr" for="inbox-type">Type</label>
                    <select id="inbox-type" name="type">
                        <option value="">All types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inbox-btn inbox-btn--quiet" data-inbox-filter-button>Filter</button>
                </form>
            @endif
        </div>

        @forelse ($groups as $day => $items)
            <section class="inbox-day" aria-labelledby="inbox-day-{{ $loop->index }}">
                <h2 id="inbox-day-{{ $loop->index }}">{{ $day }}</h2>
                <ul class="inbox-list">
                    @foreach ($items as $notification)
                        @php($message = $notification->message())
                        <li @class(['inbox-item', 'is-unread' => $notification->isUnread()])>
                            @include('inbox::partials.avatar', ['message' => $message])
                            <div class="inbox-body">
                                <a class="inbox-title" href="{{ route('inbox.open', $notification->id) }}">
                                    @if ($notification->isUnread())<span class="inbox-sr">Unread: </span>@endif{{ $message->title }}
                                </a>
                                @if ($message->body !== null)
                                    <p class="inbox-text">{{ $message->body }}</p>
                                @endif
                                <div class="inbox-meta">
                                    @if ($message->actor !== null)
                                        <span>{{ $message->actor }}</span>
                                    @endif
                                    <time datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->copy()->setTimezone(\Ruvelo\Inbox\Inbox::timezone())->toDayDateTimeString() }}">{{ $notification->created_at->diffForHumans() }}</time>
                                    <span class="inbox-chip">{{ \Ruvelo\Inbox\Inbox::typeLabel($notification->type) }}</span>
                                </div>
                            </div>
                            <div class="inbox-tools">
                                @if ($notification->isUnread())
                                    <form method="post" action="{{ route('inbox.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="inbox-icon" title="Mark as read">
                                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 8.5 3 3 7-7"/></svg>
                                            <span class="inbox-sr">Mark “{{ $message->title }}” as read</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('inbox.unread', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="inbox-icon" title="Mark as unread">
                                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="8" cy="8" r="3.25"/></svg>
                                            <span class="inbox-sr">Mark “{{ $message->title }}” as unread</span>
                                        </button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('inbox.destroy', $notification->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inbox-icon inbox-icon--danger" title="Delete">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 4h11M6.5 4V2.5h3V4M4 4l.6 9.5h6.8L12 4"/></svg>
                                        <span class="inbox-sr">Delete “{{ $message->title }}”</span>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="inbox-empty">
                <span class="inbox-empty-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                @if ($type !== null)
                    <h2>Nothing of this type{{ $filter === 'unread' ? ' unread' : '' }}</h2>
                    <p><a href="{{ route('inbox.index', array_filter(['filter' => $filter === 'unread' ? 'unread' : null])) }}">Show every type</a></p>
                @elseif ($filter === 'unread')
                    <h2>You’re all caught up</h2>
                    <p>Nothing unread. <a href="{{ route('inbox.index') }}">See everything</a></p>
                @else
                    <h2>No notifications yet</h2>
                    <p>When something needs your attention, like a payment or a mention, it shows up here.</p>
                @endif
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <nav class="inbox-pages" aria-label="Pages">
                <span>Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>
                <div>
                    @if (! $notifications->onFirstPage())
                        <a class="inbox-btn inbox-btn--quiet" href="{{ $notifications->previousPageUrl() }}" rel="prev">Newer</a>
                    @endif
                    @if ($notifications->hasMorePages())
                        <a class="inbox-btn inbox-btn--quiet" href="{{ $notifications->nextPageUrl() }}" rel="next">Older</a>
                    @endif
                </div>
            </nav>
        @endif
    </main>
</div>
<script>
    // The type filter applies as soon as you pick one.
    document.querySelectorAll('[data-inbox-filter]').forEach((form) => {
        form.querySelector('[data-inbox-filter-button]')?.setAttribute('hidden', '');
        form.querySelector('select')?.addEventListener('change', () => form.submit());
    });
</script>
@endsection
