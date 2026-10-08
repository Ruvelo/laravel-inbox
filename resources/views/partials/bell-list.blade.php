@forelse ($notifications as $notification)
    @php($message = $notification->message())
    <li>
        <a @class(['inbox-bell-item', 'is-unread' => $notification->isUnread()])
           href="{{ route('inbox.open', $notification->id) }}"
           data-inbox-item
           data-read-url="{{ route('inbox.read', $notification->id) }}"
           @if ($message->url !== null) data-url="{{ $message->url }}" @endif
           @if ($notification->isUnread()) data-unread @endif>
            @include('inbox::partials.avatar', ['message' => $message])
            <span class="inbox-bell-text">
                <span class="inbox-bell-title">{{ $message->title }}</span>
                @if ($message->excerpt(96) !== null)
                    <span class="inbox-bell-body">{{ $message->excerpt(96) }}</span>
                @endif
                <time class="inbox-bell-time" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans(short: true) }}</time>
            </span>
            @if ($notification->isUnread())
                <span class="inbox-bell-dot"><span class="inbox-sr">Unread</span></span>
            @endif
        </a>
    </li>
@empty
    <li class="inbox-bell-empty">
        <strong>You’re all caught up</strong>
        <span>New notifications will show up here.</span>
    </li>
@endforelse
