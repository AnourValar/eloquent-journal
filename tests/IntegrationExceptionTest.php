<?php

namespace AnourValar\EloquentJournal\Tests;

use AnourValar\EloquentJournal\IntegrationException;
use AnourValar\EloquentJournal\Journal;

class IntegrationExceptionTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_report_captures_failed_integration()
    {
        $user = $this->createUser();

        $exception = new IntegrationException(
            'api_request',
            ['response' => ['code' => 500]],
            $user,
            'provider_x'
        );
        $exception->report();

        $journal = Journal::where('event', 'api_request')->first();
        $this->assertNotNull($journal);
        $this->assertSame('integration', $journal->type);
        $this->assertFalse($journal->success);
        $this->assertSame('user', $journal->entity);
        $this->assertSame($user->id, $journal->entity_id);
        $this->assertSame(['response' => ['code' => 500]], $journal->data);
        $this->assertSame(['provider_x'], $journal->tags);
    }

    /**
     * @return void
     */
    public function test_report_without_entity()
    {
        $exception = new IntegrationException('api_request', null);
        $exception->report();

        $journal = Journal::where('event', 'api_request')->first();
        $this->assertNotNull($journal);
        $this->assertFalse($journal->success);
        $this->assertNull($journal->entity);
        $this->assertNull($journal->entity_id);
        $this->assertNull($journal->data);
        $this->assertNull($journal->tags);
    }

    /**
     * @return void
     */
    public function test_report_via_helper()
    {
        report(new IntegrationException('api_request', ['foo' => 'bar']));

        $this->assertSame(1, Journal::where('event', 'api_request')->count());
    }
}
