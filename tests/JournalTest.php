<?php

namespace AnourValar\EloquentJournal\Tests;

use AnourValar\EloquentJournal\Journal;
use AnourValar\EloquentJournal\Tests\Models\User;

class JournalTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_save_success()
    {
        $journal = $this->makeJournal(['type' => 'metric', 'event' => 'user_token_obtain', 'success' => true]);

        $this->assertValidationSuccess($journal)->save();
        $this->assertDatabaseHas('journals', ['id' => $journal->id, 'type' => 'metric']);
    }

    /**
     * @return void
     */
    public function test_type_is_required()
    {
        $journal = $this->makeJournal(['event' => 'user_token_obtain', 'success' => true]);

        $this->assertValidationFailed($journal, 'type');
    }

    /**
     * @return void
     */
    public function test_type_must_be_configured()
    {
        $journal = $this->makeJournal(['type' => 'unknown', 'event' => 'user_token_obtain', 'success' => true]);

        $this->assertValidationFailed($journal, 'type');
    }

    /**
     * @return void
     */
    public function test_event_must_be_configured()
    {
        $journal = $this->makeJournal(['type' => 'metric', 'event' => 'unknown_event', 'success' => true]);

        $this->assertValidationFailed($journal, 'event');
    }

    /**
     * @return void
     */
    public function test_model_type_requires_entity()
    {
        $journal = $this->makeJournal([
            'type' => 'model',
            'event' => 'create',
            'data' => ['new' => ['email' => 'john@example.com']],
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'entity');
    }

    /**
     * @return void
     */
    public function test_model_type_requires_data()
    {
        $user = $this->createUser();

        $journal = $this->makeJournal([
            'type' => 'model',
            'event' => 'create',
            'entity' => 'user',
            'entity_id' => $user->id,
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'data');
    }

    /**
     * @return void
     */
    public function test_model_type_restricts_data_keys()
    {
        $user = $this->createUser();

        $journal = $this->makeJournal([
            'type' => 'model',
            'event' => 'create',
            'entity' => 'user',
            'entity_id' => $user->id,
            'data' => ['new' => ['email' => 'john@example.com'], 'custom_key' => 'value'],
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'data');
    }

    /**
     * @return void
     */
    public function test_model_type_rejects_metric_event()
    {
        $user = $this->createUser();

        $journal = $this->makeJournal([
            'type' => 'model',
            'event' => 'user_token_obtain',
            'entity' => 'user',
            'entity_id' => $user->id,
            'data' => ['new' => ['email' => 'john@example.com']],
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'event');
    }

    /**
     * @return void
     */
    public function test_metric_type_rejects_model_event()
    {
        $journal = $this->makeJournal(['type' => 'metric', 'event' => 'create', 'success' => true]);

        $this->assertValidationFailed($journal, 'event');
    }

    /**
     * @return void
     */
    public function test_integration_type_rejects_model_event()
    {
        $journal = $this->makeJournal(['type' => 'integration', 'event' => 'update', 'success' => true]);

        $this->assertValidationFailed($journal, 'event');
    }

    /**
     * @return void
     */
    public function test_entity_must_be_in_morph_map()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'entity' => 'ghost',
            'entity_id' => 1,
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'entity');
    }

    /**
     * @return void
     */
    public function test_entity_requires_entity_id()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'entity' => 'user',
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'entity_id');
    }

    /**
     * @return void
     */
    public function test_entity_id_requires_entity()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'entity_id' => 1,
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'entity');
    }

    /**
     * @return void
     */
    public function test_user_id_must_exist()
    {
        $journal = $this->makeJournal([
            'user_id' => 999999,
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'success' => true,
        ]);

        $this->assertValidationFailed($journal, 'user_id', trans('eloquent_journal::journal.user_id_not_exists'));
    }

    /**
     * @return void
     */
    public function test_user_id_of_soft_deleted_user_is_allowed()
    {
        $user = $this->createUser();
        $user->delete();

        $journal = $this->makeJournal([
            'user_id' => $user->id,
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'success' => true,
        ]);

        $this->assertValidationSuccess($journal)->save();
    }

    /**
     * @return void
     */
    public function test_tags_max_limit()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'success' => true,
            'tags' => array_map(fn ($item) => "tag_{$item}", range(1, 11)),
        ]);

        $this->assertValidationFailed($journal, 'tags');
    }

    /**
     * @return void
     */
    public function test_tag_max_length()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'success' => true,
            'tags' => [str_repeat('a', 101)],
        ]);

        $this->assertValidationFailed($journal, 'tags.0');
    }

    /**
     * @return void
     */
    public function test_tags_mutation()
    {
        $journal = $this->makeJournal([
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'success' => true,
            'tags' => [123], // integer => string (jsonNested types)
        ]);

        $this->assertSame(['123'], $journal->tags);

        $journal->tags = []; // [] => null (nullable)
        $this->assertNull($journal->tags);
    }

    /**
     * @return void
     */
    public function test_unchangeable_columns()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain');

        $journal->success = false;

        $this->assertValidationFailed($journal, 'success');
    }

    /**
     * @return void
     */
    public function test_virtual_attributes()
    {
        $user = $this->createUser();
        $journal = $this->getService()->captureModel('create', $user);

        $this->assertSame('Model', $journal->type_title);
        $this->assertSame(config('eloquent_journal.type.model'), $journal->type_details);

        $this->assertSame('Create', $journal->event_title);
        $this->assertSame(config('eloquent_journal.event.create'), $journal->event_details);

        $this->assertSame('tests.entity.user', $journal->entity_title);
        $this->assertSame(User::class, $journal->entity_class);
        $this->assertSame(config('eloquent_journal.entity.' . User::class), $journal->entity_details);
    }

    /**
     * @return void
     */
    public function test_virtual_attributes_without_entity()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain');

        $this->assertSame('Metric', $journal->type_title);
        $this->assertNull($journal->entity_title);
        $this->assertNull($journal->entity_class);
        $this->assertNull($journal->entity_details);
        $this->assertNull($journal->short_description);
    }

    /**
     * @return void
     */
    public function test_relations()
    {
        $user = $this->createUser();
        $journal = $this->getService()->user($user)->captureMetric('user_token_obtain', $user);

        $this->assertTrue($journal->user->is($user));
        $this->assertTrue($journal->entitable->is($user));

        // soft deleted user is still resolvable
        $user->delete();
        $journal = $journal->fresh();
        $this->assertTrue($journal->user->is($user));
        $this->assertTrue($journal->entitable->is($user));
    }

    /**
     * @return void
     */
    public function test_scope_acl_without_permission()
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        $visible = $this->getService()->user($alice)->captureMetric('user_token_obtain');
        $this->getService()->user($alice)->captureIntegration('api_request'); // not public
        $this->getService()->user($bob)->captureMetric('user_token_obtain'); // another user

        $this->assertSame([$visible->getKey()], Journal::acl($alice)->pluck('id')->all());

        // fallback to the authenticated user
        $this->actingAs($alice);
        $this->assertSame([$visible->getKey()], Journal::acl()->pluck('id')->all());
    }

    /**
     * @return void
     */
    public function test_scope_acl_with_permission()
    {
        \Illuminate\Support\Facades\Gate::define('admin.administration|cabinet.journal.read', fn ($user) => true);

        $alice = $this->createUser();
        $bob = $this->createUser();

        $journal1 = $this->getService()->user($alice)->captureMetric('user_token_obtain');
        $journal2 = $this->getService()->user($alice)->captureIntegration('api_request');
        $journal3 = $this->getService()->user($bob)->captureMetric('user_token_obtain');

        $this->assertEqualsCanonicalizing(
            [$journal1->id, $journal2->id, $journal3->id],
            Journal::acl($alice)->pluck('id')->all()
        );
    }

    /**
     * @return void
     */
    public function test_scope_heavy()
    {
        $user = $this->createUser();
        $journal = $this->getService()->user($user)->captureMetric('user_token_obtain', $user);

        $found = Journal::heavy()->find($journal->id);

        $this->assertTrue($found->relationLoaded('user'));
        $this->assertTrue($found->user->is($user));

        $array = $found->toArray();
        $this->assertArrayHasKey('type_title', $array);
        $this->assertArrayHasKey('event_title', $array);
        $this->assertArrayHasKey('entity_title', $array);
        $this->assertArrayNotHasKey('user_id', $array); // not published
    }

    /**
     * @return void
     */
    public function test_prunable()
    {
        $old = $this->getService()->captureMetric('user_token_obtain');
        \Illuminate\Support\Facades\DB::table('journals')->where('id', $old->id)->update(['created_at' => now()->subMonths(13)]);

        $fresh = $this->getService()->captureMetric('user_token_obtain');

        $prunable = (new Journal())->prunable()->pluck('id')->all();

        $this->assertContains($old->id, $prunable);
        $this->assertNotContains($fresh->id, $prunable);
    }

    /**
     * @return void
     */
    public function test_factory()
    {
        $journal = Journal::factory()->createOne();
        $this->assertInstanceOf(Journal::class, $journal);

        $this->assertDatabaseHas('journals', ['id' => $journal->id]);
        $this->assertDatabaseHas('users', ['id' => $journal->user_id]);
    }

    /**
     * @return void
     */
    public function test_factory_existing_user()
    {
        $users = collect([$this->createUser(), $this->createUser()]);

        $journal = Journal::factory()->existingUser()->createOne();
        $this->assertInstanceOf(Journal::class, $journal);

        $this->assertContains($journal->user_id, $users->pluck('id')->all());
    }

    /**
     * @param array $attributes
     * @return \AnourValar\EloquentJournal\Journal
     */
    protected function makeJournal(array $attributes): Journal
    {
        return Journal::fields(array_keys($attributes))->fill($attributes);
    }
}
