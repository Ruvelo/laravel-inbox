<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every inbox route is about the signed-in user's own notifications.
 *
 * Guests are sent to the app's login page when it has one. A fresh app has
 * no `login` route until a starter kit is installed, and Laravel's own
 * `auth` middleware fails with a 500 there; this answers 401/403 instead.
 */
class EnsureSignedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            return $next($request);
        }

        if ($user === null) {
            abort_if($request->expectsJson(), 401, 'Sign in to see your notifications.');

            if (Route::has('login')) {
                return redirect()->guest(route('login'));
            }
        }

        abort(403);
    }
}
