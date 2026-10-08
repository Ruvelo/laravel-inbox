@if ($message->icon !== null)
    <span class="inbox-avatar inbox-avatar--icon" aria-hidden="true">{{ $message->icon }}</span>
@else
    <span class="inbox-avatar" aria-hidden="true">{{ $message->initials() }}</span>
@endif
