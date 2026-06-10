<?php

namespace AnourValar\EloquentJournal\Tests;

use AnourValar\EloquentJournal\Journal;
use AnourValar\EloquentJournal\Observer;
use AnourValar\EloquentJournal\Tests\Models\User;

class ObserverTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * Init
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        User::observe(Observer::class);
    }

    /**
     * @return void
     */
    public function test_nothing_logged_for_guest()
    {
        $user = $this->createUser();
        $user->update(['email' => 'new@example.com']);
        $user->delete();

        $this->assertSame(0, Journal::count());
    }

    /**
     * @return void
     */
    public function test_created()
    {
        $admin = $this->createUser();
        $this->actingAs($admin);

        $user = $this->createUser(['email' => 'john@example.com']);

        $journal = Journal::where('event', 'create')->where('entity_id', $user->id)->first();
        $this->assertNotNull($journal);
        $this->assertSame($admin->id, $journal->user_id);
        $this->assertSame('user', $journal->entity);
        $this->assertSame('model', $journal->type);
        $this->assertSame('john@example.com', $journal->data['new']['email']);
    }

    /**
     * @return void
     */
    public function test_updated()
    {
        $admin = $this->createUser();
        $user = $this->createUser(['email' => 'old@example.com']);
        $this->actingAs($admin);

        $user->update(['email' => 'new@example.com']);

        $journal = Journal::where('event', 'update')->where('entity_id', $user->id)->first();
        $this->assertNotNull($journal);
        $this->assertSame('old@example.com', $journal->data['old']['email']);
        $this->assertSame('new@example.com', $journal->data['new']['email']);
    }

    /**
     * @return void
     */
    public function test_deleted()
    {
        $admin = $this->createUser();
        $user = $this->createUser(['email' => 'john@example.com']);
        $this->actingAs($admin);

        $user->delete();

        $journal = Journal::where('event', 'delete')->where('entity_id', $user->id)->first();
        $this->assertNotNull($journal);
        $this->assertSame('john@example.com', $journal->data['old']['email']);
    }

    /**
     * @return void
     */
    public function test_restored()
    {
        $admin = $this->createUser();
        $user = $this->createUser(['email' => 'john@example.com']);
        $user->delete();
        $this->actingAs($admin);

        $user->restore();

        $journal = Journal::where('event', 'restore')->where('entity_id', $user->id)->first();
        $this->assertNotNull($journal);
        $this->assertSame('john@example.com', $journal->data['new']['email']);

        // restore is not logged as an update
        $this->assertSame(0, Journal::where('event', 'update')->count());
    }
}
