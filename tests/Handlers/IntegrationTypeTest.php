<?php

namespace AnourValar\EloquentJournal\Tests\Handlers;

use AnourValar\EloquentJournal\Handlers\IntegrationType;
use AnourValar\EloquentJournal\Tests\AbstractSuite;

class IntegrationTypeTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_short_description()
    {
        $journal = $this->getService()->captureIntegration('api_request', null, ['foo' => 'bar']);

        $this->assertNull($journal->short_description);
    }

    /**
     * @return void
     */
    public function test_full_description_without_data()
    {
        $journal = $this->getService()->captureIntegration('api_request');

        $this->assertNull($journal->full_description);
    }

    /**
     * @return void
     */
    public function test_full_description()
    {
        $journal = $this->getService()->captureIntegration('api_request', null, [
            'request' => ['url' => 'https://example.com/api'],
            'response' => ['code' => 200],
        ]);

        $html = $journal->full_description;

        $this->assertStringContainsString('journal-integration', $html);
        $this->assertStringContainsString('https://example.com/api', $html);
        $this->assertStringContainsString('200', $html);
    }

    /**
     * @return void
     */
    public function test_handler_is_bound()
    {
        $journal = $this->getService()->captureIntegration('api_request');

        $this->assertInstanceOf(IntegrationType::class, $journal->getTypeHandler());
    }
}
