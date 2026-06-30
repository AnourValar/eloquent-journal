<?php

namespace AnourValar\EloquentJournal\Tests\Handlers;

use AnourValar\EloquentJournal\Handlers\ModelType;
use AnourValar\EloquentJournal\Journal;
use AnourValar\EloquentJournal\Tests\AbstractSuite;
use AnourValar\EloquentJournal\Tests\Models\User;

class ModelTypeTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_get_data_create()
    {
        $user = $this->createUser(['email' => 'john@example.com', 'phone' => '79990001122']);

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertArrayHasKey('new', $data);
        $this->assertArrayNotHasKey('old', $data);

        $this->assertSame('john@example.com', $data['new']['email']);
        $this->assertSame($user->id, $data['new']['id']);

        // datetime cast is formatted
        $this->assertSame($user->created_at->format('Y-m-d H:i:s') . ' [UTC]', $data['new']['created_at']);

        // updated_at is always excluded
        $this->assertArrayNotHasKey('updated_at', $data['new']);

        // hidden attribute is hashed
        $this->assertStringEndsWith(' [HASH]', $data['new']['phone']);
        $this->assertStringNotContainsString('79990001122', $data['new']['phone']);
    }

    /**
     * @return void
     */
    public function test_get_data_update()
    {
        $user = $this->createUser(['email' => 'old@example.com']);

        // nothing changed
        $unchanged = $this->getHandler()->getData($user, 'update');
        $this->assertNull($unchanged);

        $user->email = 'new@example.com';
        $data = $this->getHandler()->getData($user, 'update');
        $this->assertNotNull($data);

        $this->assertSame('old@example.com', $data['old']['email']);
        $this->assertSame('new@example.com', $data['new']['email']);
    }

    /**
     * @return void
     */
    public function test_get_data_delete()
    {
        $user = $this->createUser(['email' => 'john@example.com']);

        $data = $this->getHandler()->getData($user, 'delete');

        $this->assertArrayNotHasKey('new', $data);
        $this->assertSame('john@example.com', $data['old']['email']);
    }

    /**
     * @return void
     */
    public function test_get_data_restore()
    {
        $user = $this->createUser(['email' => 'john@example.com']);

        $data = $this->getHandler()->getData($user, 'restore');

        $this->assertArrayNotHasKey('old', $data);
        $this->assertSame('john@example.com', $data['new']['email']);
    }

    /**
     * @return void
     */
    public function test_get_data_exclude_attributes()
    {
        config(['eloquent_journal.entity.' . User::class . '.exclude_attributes' => ['email']]);

        $user = $this->createUser(['email' => 'old@example.com']);
        $user->email = 'new@example.com';

        // the only change is excluded => nothing to save
        $unchanged = $this->getHandler()->getData($user, 'update');
        $this->assertNull($unchanged);

        $user->status = 'active';
        $data = $this->getHandler()->getData($user, 'update');
        $this->assertNotNull($data);

        $this->assertArrayNotHasKey('email', $data['new']);
        $this->assertSame('active', $data['new']['status']);
    }

    /**
     * @return void
     */
    public function test_get_data_json_cast()
    {
        $user = $this->createUser(['settings' => ['locale' => 'en']]);
        $user->settings = ['locale' => 'ru'];

        $data = $this->getHandler()->getData($user, 'update');

        $this->assertSame(['locale' => 'en'], $data['old']['settings']);
        $this->assertSame(['locale' => 'ru'], $data['new']['settings']);
    }

    /**
     * @return void
     */
    public function test_get_data_unsupported_model()
    {
        $model = new class () extends \Illuminate\Database\Eloquent\Model
        {};

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is not supported');

        $this->getHandler()->getData($model, 'create');
    }

    /**
     * @return void
     */
    public function test_get_data_not_configured_model()
    {
        config(['eloquent_journal.entity' => []]);
        $user = $this->createUser();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is not configured');

        $this->getHandler()->getData($user, 'create');
    }

    /**
     * @return void
     */
    public function test_schema_config()
    {
        config(['tests.statuses' => ['active' => ['title' => 'tests.status.active']]]);
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'status' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'tests.statuses', 'display' => 'title'],
        ]]);

        $user = $this->createUser(['status' => 'active']);

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertSame('tests.status.active', $data['schema_new']['status']);
        $this->assertArrayNotHasKey('email', $data['schema_new']);
    }

    /**
     * @return void
     */
    public function test_schema_multiple_encoded()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'balance' => ['type' => ModelType::SCHEMA_MULTIPLE_ENCODED],
        ]]);

        $user = $this->createUser(['balance' => 12345]); // multiple = 100

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertSame('123.45', $data['schema_new']['balance']);
    }

    /**
     * @return void
     */
    public function test_schema_model()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'settings.friend_ids' => ['type' => ModelType::SCHEMA_MODEL, 'model' => User::class, 'display' => 'email'],
        ]]);

        $friend = $this->createUser(['email' => 'friend@example.com']);
        $user = $this->createUser(['settings' => ['friend_ids' => [$friend->id]]]);

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertSame(["friend@example.com [#{$friend->id}]"], $data['schema_new']['settings']['friend_ids']);
    }

    /**
     * A single (scalar) value is resolved through the model schema.
     *
     * @return void
     */
    public function test_schema_model_scalar_value()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'settings.manager_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => User::class, 'display' => 'email'],
        ]]);

        $manager = $this->createUser(['email' => 'manager@example.com']);
        $user = $this->createUser(['settings' => ['manager_id' => $manager->id]]);

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertSame("manager@example.com [#{$manager->id}]", $data['schema_new']['settings']['manager_id']);
    }

    /**
     * A scalar value pointing to a missing model keeps its original value
     * instead of being replaced with null.
     *
     * @return void
     */
    public function test_schema_model_scalar_missing_keeps_original()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'settings.manager_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => User::class, 'display' => 'email'],
        ]]);

        $user = $this->createUser(['settings' => ['manager_id' => 999999]]); // no such model

        $data = $this->getHandler()->getData($user, 'create');

        $this->assertSame(999999, $data['schema_new']['settings']['manager_id']);
    }

    /**
     * "display" may be an array of attributes - the first available one is used.
     *
     * @return void
     */
    public function test_schema_model_multiple_display()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'settings.friend_ids' => ['type' => ModelType::SCHEMA_MODEL, 'model' => User::class, 'display' => ['email', 'phone']],
        ]]);

        $friend = $this->createUser(['email' => 'friend@example.com', 'phone' => '79990001111']);
        $user = $this->createUser(['settings' => ['friend_ids' => [$friend->id]]]);

        $data = $this->getHandler()->getData($user, 'create');

        // the first display attribute (email) is set => it wins
        $this->assertSame(["friend@example.com [#{$friend->id}]"], $data['schema_new']['settings']['friend_ids']);
    }

    /**
     * When the first "display" attribute is empty, the next one is used as a fallback.
     *
     * @return void
     */
    public function test_schema_model_multiple_display_fallback()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'settings.friend_ids' => ['type' => ModelType::SCHEMA_MODEL, 'model' => User::class, 'display' => ['email', 'phone']],
        ]]);

        $friend = $this->createUser(['email' => null, 'phone' => '79990009999']);
        $user = $this->createUser(['settings' => ['friend_ids' => [$friend->id]]]);

        $data = $this->getHandler()->getData($user, 'create');

        // email is null => the next display attribute (phone) is used
        $this->assertSame(["79990009999 [#{$friend->id}]"], $data['schema_new']['settings']['friend_ids']);
    }

    /**
     * @return void
     */
    public function test_schema_incorrect_type()
    {
        config(['eloquent_journal.entity.' . User::class . '.schema' => [
            'status' => ['type' => 'unknown'],
        ]]);

        $user = $this->createUser(['status' => 'active']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Incorrect schema type.');

        $this->getHandler()->getData($user, 'create');
    }

    /**
     * @return void
     */
    public function test_short_description_named()
    {
        $user = $this->createUser();
        $user->email = 'changed@example.com';

        $journal = $this->getService()->captureModel('update', $user);

        $this->assertSame(
            trans('eloquent_journal::journal.type_handler.model.short_description_named', ['names' => '«email»']),
            $journal->short_description
        );
    }

    /**
     * @return void
     */
    public function test_short_description_qty()
    {
        $user = $this->createUser(['email' => 'a@a.a', 'phone' => '111', 'status' => 'a', 'balance' => 1]);
        $user->fill(['email' => 'b@b.b', 'phone' => '222', 'status' => 'b', 'balance' => 2]);

        $journal = $this->getService()->captureModel('update', $user);

        $this->assertSame(
            trans('eloquent_journal::journal.type_handler.model.short_description_qty', ['qty' => 4]),
            $journal->short_description
        );
    }

    /**
     * @return void
     */
    public function test_short_description_for_create()
    {
        $user = $this->createUser();

        $journal = $this->getService()->captureModel('create', $user);

        $this->assertNull($journal->short_description);
    }

    /**
     * @return void
     */
    public function test_full_description()
    {
        $user = $this->createUser(['email' => 'old@example.com', 'balance' => 1000, 'status' => null]);
        $user->fill(['email' => 'new@example.com', 'settings' => ['flag' => true]]);

        $journal = $this->getService()->captureModel('update', $user);
        $html = $journal->full_description;

        $this->assertStringContainsString('journal-model', $html);
        $this->assertStringContainsString('old@example.com', $html);
        $this->assertStringContainsString('new@example.com', $html);

        // formatting: boolean, null, number
        $this->assertStringContainsString(trans('eloquent_journal::journal.type_handler.model.full_description_true'), $html);
        $this->assertStringContainsString(trans('eloquent_journal::journal.type_handler.model.full_description_null'), $html);
        $this->assertStringContainsString('1,000', $html);
    }

    /**
     * @return void
     */
    public function test_full_description_without_data()
    {
        $journal = new Journal();
        $journal->entity = 'user';
        $journal->type = 'model';
        $journal->event = 'update';
        $journal->data = null;

        $this->assertNull($journal->full_description);
    }

    /**
     * @return void
     */
    public function test_attribute_names()
    {
        config(['eloquent_journal.entity.' . User::class . '.attribute_names' => [
            'eloquent_journal::journal.attributes', // lang path
        ]]);

        $user = $this->createUser();
        $journal = $this->getService()->captureModel('create', $user);

        $this->assertSame(
            trans('eloquent_journal::journal.attributes.created_at'),
            $journal->data['attribute_names_new']['created_at']
        );

        $this->assertStringContainsString(
            trans('eloquent_journal::journal.attributes.created_at'),
            $journal->full_description
        );
    }

    /**
     * @return \AnourValar\EloquentJournal\Handlers\ModelType
     */
    protected function getHandler(): ModelType
    {
        return \App::make(ModelType::class);
    }
}
