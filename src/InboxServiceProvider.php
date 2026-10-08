<?php

declare(strict_types=1);

namespace Ruvelo\Inbox;

use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Ruvelo\Inbox\Notifications\DatabaseChannel;

class InboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inbox.php', 'inbox');

        // Lets InboxNotification classes skip toDatabase()/toArray(). An app
        // that already swapped the database channel keeps its own.
        if (! $this->app->bound(LaravelDatabaseChannel::class)) {
            $this->app->bind(LaravelDatabaseChannel::class, DatabaseChannel::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'inbox');
        Blade::componentNamespace('Ruvelo\\Inbox\\View\\Components', 'inbox');

        View::composer(['inbox::index', 'inbox::preferences'], function ($view): void {
            $layout = config('inbox.layout');
            $view->with('inboxLayout', is_string($layout) && $layout !== '' ? $layout : 'inbox::layout');
            $view->with('inboxSection', (string) config('inbox.section', 'content'));
        });

        if (config('inbox.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('inbox.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        if (config('inbox.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/inbox.php' => config_path('inbox.php'),
            ], 'inbox-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/inbox'),
            ], 'inbox-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'inbox-migrations');
        }
    }
}
