<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\Inbox\Http\Controllers\InboxController;
use Ruvelo\Inbox\Http\Controllers\PreferencesController;
use Ruvelo\Inbox\Http\Middleware\EnsureSignedIn;

Route::group([
    'prefix' => config('inbox.path', 'inbox'),
    'domain' => config('inbox.domain'),
    'middleware' => [...config('inbox.middleware', ['web']), EnsureSignedIn::class],
    'as' => 'inbox.',
], function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('/count', [InboxController::class, 'count'])->name('count');
    Route::get('/bell', [InboxController::class, 'bell'])->name('bell');
    Route::post('/read-all', [InboxController::class, 'readAll'])->name('read-all');
    Route::get('/preferences', [PreferencesController::class, 'edit'])->name('preferences');
    Route::put('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');

    Route::get('/{notification}/open', [InboxController::class, 'open'])->name('open');
    Route::post('/{notification}/read', [InboxController::class, 'read'])->name('read');
    Route::post('/{notification}/unread', [InboxController::class, 'unread'])->name('unread');
    Route::delete('/{notification}', [InboxController::class, 'destroy'])->name('destroy');
});
