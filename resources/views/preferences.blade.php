@extends($inboxLayout)

@section('title', 'Notification preferences')

@section($inboxSection)
@include('inbox::partials.styles')
<div class="inbox-page">
    <main class="inbox-main">
        @if (session('inbox.status'))
            <p class="inbox-flash" role="status">{{ session('inbox.status') }}</p>
        @endif

        <div class="inbox-head">
            <div>
                <h1>Notification preferences</h1>
                <p class="inbox-prefs-intro">Choose how you hear about each kind of update. Changes apply to new notifications.</p>
            </div>
            <div class="inbox-actions">
                <a class="inbox-btn inbox-btn--quiet" href="{{ route('inbox.index') }}">Back to notifications</a>
            </div>
        </div>

        @if ($groups === [])
            <div class="inbox-empty">
                <span class="inbox-empty-mark" aria-hidden="true">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true"><path d="M2 4h7M13 4h1M2 12h1M7 12h7"/><circle cx="11" cy="4" r="2"/><circle cx="5" cy="12" r="2"/></svg>
                </span>
                <h2>Nothing to set up yet</h2>
                <p>Every notification is on. Options show up here once the app lets you choose.</p>
            </div>
        @else
            <form method="post" action="{{ route('inbox.preferences.update') }}">
                @csrf
                @method('PUT')

                @foreach ($groups as $group => $types)
                    <section class="inbox-prefs-group" @if ($group !== '') aria-labelledby="inbox-group-{{ $loop->index }}" @endif>
                        @if ($group !== '')
                            <h2 id="inbox-group-{{ $loop->index }}">{{ $group }}</h2>
                        @endif
                        <table class="inbox-prefs">
                            <thead>
                                <tr>
                                    <th scope="col">Notification</th>
                                    @foreach ($channels as $channel)
                                        <th scope="col" class="inbox-prefs-channel">{{ \Ruvelo\Inbox\Inbox::channelLabel($channel) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($types as $type)
                                    <tr>
                                        <th scope="row" id="{{ $type->id() }}">
                                            <span class="inbox-prefs-label">
                                                {{ $type->label }}
                                                @if ($type->required)
                                                    <span class="inbox-chip inbox-chip--accent">Always on</span>
                                                @endif
                                            </span>
                                            @if ($type->description !== null)
                                                <span class="inbox-prefs-desc">{{ $type->description }}</span>
                                            @endif
                                        </th>
                                        @foreach ($channels as $channel)
                                            <td class="inbox-prefs-channel">
                                                @if ($type->allows($channel))
                                                    @php($on = $choices[$type->key][$channel] ?? $type->defaultFor($channel))
                                                    <span class="inbox-switch">
                                                        @unless ($type->required)
                                                            <input type="hidden" name="prefs[{{ $type->id() }}][{{ $channel }}]" value="0">
                                                        @endunless
                                                        <input type="checkbox" role="switch"
                                                               name="prefs[{{ $type->id() }}][{{ $channel }}]" value="1"
                                                               aria-label="{{ \Ruvelo\Inbox\Inbox::channelLabel($channel) }}: {{ $type->label }}"
                                                               @checked($on) @disabled($type->required)>
                                                    </span>
                                                @else
                                                    <span class="inbox-none" title="Not available">–<span class="inbox-sr">Not available</span></span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endforeach

                <div class="inbox-prefs-save">
                    <button type="submit" class="inbox-btn">Save preferences</button>
                    @if (collect($groups)->flatten()->contains(fn ($type) => $type->required))
                        <p>Types marked “Always on” can’t be turned off.</p>
                    @endif
                </div>
            </form>
        @endif
    </main>
</div>
@endsection
