<?php

namespace AnourValar\EloquentJournal\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
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

        config(['eloquent_journal.model' => \AnourValar\EloquentJournal\Journal::class]);

        config(['auth.providers.users.model' => Models\User::class]);

        config(['eloquent_journal.entity' => [
            Models\User::class => [
                'title' => 'tests.entity.user',
                'schema' => [],
                'attribute_names' => [],
                'observe' => false,
                'exclude_attributes' => [],
            ],
        ]]);

        config(['eloquent_journal.event' => array_replace(config('eloquent_journal.event'), [
            'api_request' => [
                'title' => 'tests.event.api_request',
                'optgroup' => 'eloquent_journal::journal.type.integration',
                'is_public' => false,
            ],
        ])]);

        Relation::morphMap(['user' => Models\User::class]);

        \Illuminate\Database\Eloquent\Factories\Factory::guessModelNamesUsing(fn () => \AnourValar\EloquentJournal\Journal::class);
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
            $table->string('status')->nullable();
            $table->jsonb('settings')->nullable();
            $table->bigInteger('balance')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \AnourValar\LaravelAtom\Providers\LaravelAtomServiceProvider::class,
            \AnourValar\EloquentValidation\Providers\EloquentValidationServiceProvider::class,
            \AnourValar\EloquentJournal\Providers\AnourValarEloquentJournalServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Atom' => \AnourValar\LaravelAtom\Facades\AtomFacade::class,
        ];
    }

    /**
     * @return \AnourValar\EloquentJournal\Service
     */
    protected function getService(): \AnourValar\EloquentJournal\Service
    {
        return \App::make(\AnourValar\EloquentJournal\Service::class);
    }

    /**
     * @param array $attributes
     * @return \AnourValar\EloquentJournal\Tests\Models\User
     */
    protected function createUser(array $attributes = []): Models\User
    {
        return Factories\UserFactory::new()->createOne($attributes);
    }
}
