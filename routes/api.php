<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\Inbox\Http\Controllers\Api\NotificationController;
use Ruvelo\Inbox\Http\Controllers\Api\PreferencesController;
use Ruvelo\Inbox\Http\Middleware\EnsureSignedIn;

// The JSON API: the signed-in user's own notifications, over the session.
Route::group([
    'prefix' => config('inbox.api.prefix', 'api/inbox'),
    'domain' => config('inbox.domain'),
    'middleware' => [...config('inbox.api.middleware', ['web']), EnsureSignedIn::class],
    'as' => 'inbox.api.',
], function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('index');
    Route::get('notifications/count', [NotificationController::class, 'count'])->name('count');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('read');
    Route::post('notifications/{notification}/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('destroy');

    Route::get('preferences', [PreferencesController::class, 'show'])->name('preferences');
    Route::put('preferences', [PreferencesController::class, 'update'])->name('preferences.update');
});
