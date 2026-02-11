<?php

namespace AnourValar\EloquentJournal\Providers;

use Illuminate\Support\ServiceProvider;

class AnourValarEloquentJournalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // config
        $this->mergeConfigFrom(__DIR__.'/../resources/config/journal.php', 'journal');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // config
        $this->publishes([__DIR__.'/../resources/config/journal.php' => config_path('journal.php')], 'config');

        // migrations
        //$this->loadMigrationsFrom(__DIR__.'/../resources/database/migrations');
        $this->publishes([__DIR__.'/../resources/database/migrations/' => database_path('migrations')], 'migrations');

        // models
        $this->publishes([__DIR__.'/../resources/stubs/' => app_path()], 'models');

        // langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang/', 'journal');
        $this->publishes([__DIR__.'/../resources/lang/' => lang_path('vendor/journal')]);

        // views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'journal');
        $this->publishes([__DIR__.'/../resources/views/' => resource_path('views/vendor/journal')]);

        // observers
        foreach (config('journal.entity') as $entity => $details) {
            if ($details['observe']) {
                $entity::observe(\AnourValar\EloquentJournal\Observer::class);
            }
        }
    }
}
