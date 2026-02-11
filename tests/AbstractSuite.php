<?php

namespace AnourValar\EloquentJournal\Tests;

use Illuminate\Database\Schema\Blueprint;

abstract class AbstractSuite extends \Orchestra\Testbench\TestCase
{
    use \AnourValar\EloquentValidation\Tests\ValidationTrait;

    /**
     * Init
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../src/resources/database/migrations');
        $this->setUpDatabase($this->app);

        config(['journal.model' => \AnourValar\EloquentJournal\Journal::class]);

        //\Illuminate\Database\Eloquent\Factories\Factory::guessModelNamesUsing(fn () => \AnourValar\EloquentJournal\Journal::class);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function setUpDatabase(\Illuminate\Foundation\Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \AnourValar\EloquentValidation\Providers\EloquentValidationServiceProvider::class,
            \AnourValar\EloquentJournal\Providers\AnourValarEloquentJournalServiceProvider::class,
        ];
    }
}
