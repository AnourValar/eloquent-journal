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
        $this->mergeConfigFrom(__DIR__.'/../resources/config/eloquent_journal.php', 'eloquent_journal');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // config
        $this->publishes([__DIR__.'/../resources/config/eloquent_journal.php' => config_path('eloquent_journal.php')], 'eloquent_journal');

        // migrations
        //$this->loadMigrationsFrom(__DIR__.'/../resources/database/migrations');
        $this->publishes([__DIR__.'/../resources/database/migrations/' => database_path('migrations')], 'migrations');

        // models
        $this->publishes([__DIR__.'/../resources/stubs/' => app_path()], 'models');

        // langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang/', 'eloquent_journal');
        $this->publishes([__DIR__.'/../resources/lang/' => lang_path('vendor/eloquent_journal')]);

        // views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'eloquent_journal');
        $this->publishes([__DIR__.'/../resources/views/' => resource_path('views/vendor/eloquent_journal')]);

        // observers
        foreach (config('eloquent_journal.entity') as $entity => $details) {
            if ($details['observe']) {
                $entity::observe(\AnourValar\EloquentJournal\Observer::class);
            }
        }
    }
}
