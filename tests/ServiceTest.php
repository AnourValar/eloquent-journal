<?php

namespace AnourValar\EloquentJournal\Tests;

use AnourValar\EloquentJournal\Events\JournalCreated;
use AnourValar\EloquentJournal\Journal;
use AnourValar\LaravelAtom\Exceptions\InternalValidationException;
use Illuminate\Database\Eloquent\Relations\Relation;

class ServiceTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_capture_metric_guest()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain', null, ['foo' => 'bar'], true, 'web');

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertTrue($journal->exists);
        $this->assertNull($journal->user_id);
        $this->assertNull($journal->ip_address);
        $this->assertNull($journal->entity);
        $this->assertNull($journal->entity_id);
        $this->assertSame('metric', $journal->type);
        $this->assertSame('user_token_obtain', $journal->event);
        $this->assertSame(['foo' => 'bar'], $journal->data);
        $this->assertTrue($journal->success);
        $this->assertSame(['web'], $journal->tags);

        $this->assertDatabaseHas('journals', ['id' => $journal->id, 'type' => 'metric', 'event' => 'user_token_obtain']);
    }

    /**
     * @return void
     */
    public function test_capture_metric_with_entity()
    {
        $user = $this->createUser();

        $journal = $this->getService()->captureMetric('user_token_obtain', $user);

        $this->assertSame('user', $journal->entity);
        $this->assertSame($user->id, $journal->entity_id);
        $this->assertNull($journal->data);
        $this->assertNull($journal->tags);
    }

    /**
     * @return void
     */
    public function test_capture_metric_uses_authenticated_user()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $journal = $this->getService()->captureMetric('user_token_obtain');

        $this->assertSame($user->id, $journal->user_id);
    }

    /**
     * @return void
     */
    public function test_user_setter_is_immutable()
    {
        $user = $this->createUser();
        $service = $this->getService();

        $custom = $service->user($user);

        $this->assertNotSame($service, $custom);
        $this->assertSame($user->id, $custom->captureMetric('user_token_obtain')->user_id);
        $this->assertNull($service->captureMetric('user_token_obtain')->user_id);
    }

    /**
     * @return void
     */
    public function test_ip_address_setter_is_immutable()
    {
        $service = $this->getService();

        $custom = $service->ipAddress('10.1.2.3');

        $this->assertNotSame($service, $custom);
        $this->assertSame('10.1.2.3', $custom->captureMetric('user_token_obtain')->ip_address);
        $this->assertNull($service->captureMetric('user_token_obtain')->ip_address);
    }

    /**
     * @return void
     */
    public function test_capture_with_invalid_ip_address()
    {
        $this->expectException(InternalValidationException::class);

        $this->getService()->ipAddress('not-an-ip')->captureMetric('user_token_obtain');
    }

    /**
     * @return void
     */
    public function test_capture_dispatches_event()
    {
        \Event::fake([JournalCreated::class]);

        $journal = $this->getService()->captureMetric('user_token_obtain');

        \Event::assertDispatched(JournalCreated::class, fn ($event) => $event->journal->is($journal));
    }

    /**
     * @return void
     */
    public function test_capture_metric_with_unknown_event()
    {
        $this->expectException(InternalValidationException::class);

        $this->getService()->captureMetric('unknown_event');
    }

    /**
     * @return void
     */
    public function test_capture_metric_rejects_model_events()
    {
        $this->expectException(InternalValidationException::class);

        $this->getService()->captureMetric('create');
    }

    /**
     * @return void
     */
    public function test_capture_model_create()
    {
        $user = $this->createUser(['email' => 'john@example.com', 'phone' => '79990001122']);

        $journal = $this->getService()->captureModel('create', $user);

        $this->assertSame('model', $journal->type);
        $this->assertSame('create', $journal->event);
        $this->assertSame('user', $journal->entity);
        $this->assertSame($user->id, $journal->entity_id);
        $this->assertTrue($journal->success);

        $this->assertArrayNotHasKey('old', $journal->data);
        $this->assertSame('john@example.com', $journal->data['new']['email']);
        $this->assertArrayNotHasKey('updated_at', $journal->data['new']);

        // hidden attribute is hashed
        $this->assertStringEndsWith(' [HASH]', $journal->data['new']['phone']);
        $this->assertStringNotContainsString('79990001122', $journal->data['new']['phone']);
    }

    /**
     * @return void
     */
    public function test_capture_model_update()
    {
        $user = $this->createUser(['email' => 'old@example.com']);

        // nothing changed => no journal
        $unchanged = $this->getService()->captureModel('update', $user);
        $this->assertNull($unchanged);

        $user->email = 'new@example.com';
        $journal = $this->getService()->captureModel('update', $user);
        $this->assertNotNull($journal);

        $this->assertSame('update', $journal->event);
        $this->assertSame('old@example.com', $journal->data['old']['email']);
        $this->assertSame('new@example.com', $journal->data['new']['email']);
    }

    /**
     * @return void
     */
    public function test_capture_model_delete()
    {
        $user = $this->createUser(['email' => 'john@example.com']);

        $journal = $this->getService()->captureModel('delete', $user);

        $this->assertSame('delete', $journal->event);
        $this->assertSame('john@example.com', $journal->data['old']['email']);
        $this->assertArrayNotHasKey('new', $journal->data);
    }

    /**
     * @return void
     */
    public function test_capture_model_failed_with_errors()
    {
        $user = $this->createUser();
        $user->email = 'changed@example.com';

        $journal = $this->getService()->captureModel(
            'update',
            $user,
            ['errors' => ['email' => ['Invalid.']]],
            false,
            ['attempt']
        );

        $this->assertFalse($journal->success);
        $this->assertSame(['email' => ['Invalid.']], $journal->data['errors']);
        $this->assertSame('changed@example.com', $journal->data['new']['email']);
        $this->assertSame(['attempt'], $journal->tags);
    }

    /**
     * @return void
     */
    public function test_capture_model_failed_requires_errors()
    {
        $user = $this->createUser();
        $user->email = 'changed@example.com';

        $this->expectException(InternalValidationException::class);

        $this->getService()->captureModel('update', $user, null, false);
    }

    /**
     * @return void
     */
    public function test_capture_model_without_morph_map()
    {
        $user = $this->createUser();
        Relation::morphMap([], false); // reset

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MorphMap must be configured.');

        $this->getService()->captureModel('create', $user);
    }

    /**
     * @return void
     */
    public function test_capture_integration()
    {
        $user = $this->createUser();

        $journal = $this->getService()->captureIntegration(
            'api_request',
            $user,
            ['request' => ['url' => 'https://example.com'], 'response' => ['code' => 200]],
            true,
            ['provider_x']
        );

        $this->assertSame('integration', $journal->type);
        $this->assertSame('api_request', $journal->event);
        $this->assertSame('user', $journal->entity);
        $this->assertSame($user->id, $journal->entity_id);
        $this->assertTrue($journal->success);
        $this->assertSame(['url' => 'https://example.com'], $journal->data['request']);
        $this->assertSame(['code' => 200], $journal->data['response']);
        $this->assertSame(['provider_x'], $journal->tags);
    }

    /**
     * @return void
     */
    public function test_capture_integration_without_entity()
    {
        $journal = $this->getService()->captureIntegration('api_request', null, null, false);

        $this->assertNull($journal->entity);
        $this->assertNull($journal->entity_id);
        $this->assertNull($journal->data);
        $this->assertFalse($journal->success);
    }

    /**
     * @return void
     */
    public function test_capture_integration_rejects_model_events()
    {
        $this->expectException(InternalValidationException::class);

        $this->getService()->captureIntegration('create');
    }

    /**
     * @return void
     */
    public function test_publish_config_for_guest()
    {
        $config = $this->getService()->publishConfig('journal_');

        $this->assertSame(['user' => ['title' => 'tests.entity.user']], $config['journal_entities']);

        // all events are present (including non-public)
        $this->assertEqualsCanonicalizing(
            array_keys(config('eloquent_journal.event')),
            array_keys($config['journal_events'])
        );
        $this->assertSame('Create', $config['journal_events']['create']['title']);
        $this->assertSame('Model', $config['journal_events']['create']['optgroup']);
    }

    /**
     * @return void
     */
    public function test_publish_config_for_user_without_permission()
    {
        $this->actingAs($this->createUser());

        $config = $this->getService()->publishConfig();

        $this->assertSame(['user_session_obtain', 'user_token_obtain'], array_keys($config['events']));
    }

    /**
     * @return void
     */
    public function test_publish_config_for_user_with_permission()
    {
        \Illuminate\Support\Facades\Gate::define('admin.administration|cabinet.journal.read', fn ($user) => true);
        $this->actingAs($this->createUser());

        $config = $this->getService()->publishConfig();

        $this->assertEqualsCanonicalizing(
            array_keys(config('eloquent_journal.event')),
            array_keys($config['events'])
        );
    }
}
