<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\Models\Notification;

abstract class Controller extends BaseController
{
    /**
     * The signed-in user. EnsureSignedIn guarantees one on every route.
     */
    protected function notifiable(Request $request): Model
    {
        $user = $request->user();
        abort_unless($user instanceof Model, 403);

        return $user;
    }

    /**
     * One of the signed-in user's notifications. Anyone else's is a 404,
     * never a 403: we don't confirm it exists.
     */
    protected function owned(Request $request, string $id): Notification
    {
        return Inbox::find($this->notifiable($request), $id) ?? abort(404);
    }
}
