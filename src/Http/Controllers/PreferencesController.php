<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ruvelo\Inbox\Inbox;
use Symfony\Component\HttpFoundation\Response;

class PreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        $types = Inbox::preferenceTypes();

        $groups = [];
        $channels = [];
        foreach ($types as $type) {
            $groups[$type->group ?? ''][] = $type;
            $channels += array_fill_keys($type->channels, true);
        }

        return response()->view('inbox::preferences', [
            'groups' => $groups,
            'channels' => array_keys($channels),
            'choices' => Inbox::preferencesFor($this->notifiable($request)),
        ]);
    }

    /**
     * The form posts `prefs[<type id>][<channel>]` = 0|1 for every switch
     * (a hidden 0 before each checkbox). Required types aren't in the form.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['prefs' => ['nullable', 'array']]);
        $submitted = $request->input('prefs', []);
        $submitted = is_array($submitted) ? $submitted : [];

        $choices = [];
        foreach (Inbox::preferenceTypes() as $key => $type) {
            if ($type->required || ! is_array($submitted[$type->id()] ?? null)) {
                continue;
            }

            foreach ($type->channels as $channel) {
                if (array_key_exists($channel, $submitted[$type->id()])) {
                    $choices[$key][$channel] = filter_var($submitted[$type->id()][$channel], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        $changes = Inbox::updatePreferences($this->notifiable($request), $choices);

        return redirect()->route('inbox.preferences')
            ->with('inbox.status', $changes === [] ? 'No changes to save.' : 'Preferences saved.');
    }
}
