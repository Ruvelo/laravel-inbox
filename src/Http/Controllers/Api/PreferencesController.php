<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Ruvelo\Inbox\Exceptions\RequiredPreference;
use Ruvelo\Inbox\Exceptions\UnknownPreference;
use Ruvelo\Inbox\Http\Controllers\Controller;
use Ruvelo\Inbox\Inbox;

class PreferencesController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->listing($this->notifiable($request));
    }

    /**
     * Body: {"preferences": {"App\\Notifications\\InvoicePaid": {"mail": false}}}.
     * Only the pairs sent are changed.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => ['required', 'array'],
        ]);

        $choices = [];
        foreach ((array) $request->json('preferences', $request->input('preferences')) as $type => $channels) {
            if (! is_array($channels)) {
                throw ValidationException::withMessages(['preferences' => 'Each type maps channel names to true or false.']);
            }

            foreach ($channels as $channel => $on) {
                if (! is_bool($on) && ! in_array($on, [0, 1, '0', '1'], true)) {
                    throw ValidationException::withMessages(['preferences' => 'Each type maps channel names to true or false.']);
                }
                $choices[(string) $type][(string) $channel] = (bool) $on;
            }
        }

        $user = $this->notifiable($request);

        try {
            Inbox::updatePreferences($user, $choices);
        } catch (UnknownPreference|RequiredPreference $e) {
            throw ValidationException::withMessages(['preferences' => $e->getMessage()]);
        }

        return $this->listing($user);
    }

    private function listing(Model $user): JsonResponse
    {
        $choices = Inbox::preferencesFor($user);
        $data = [];

        foreach (Inbox::preferenceTypes() as $key => $type) {
            $data[] = [
                'type' => $key,
                'label' => $type->label,
                'description' => $type->description,
                'group' => $type->group,
                'required' => $type->required,
                'channels' => $choices[$key] ?? [],
            ];
        }

        return response()->json(['data' => $data]);
    }
}
