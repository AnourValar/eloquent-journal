<?php

namespace AnourValar\EloquentJournal\Tests\Handlers;

use AnourValar\EloquentJournal\Handlers\MetricType;
use AnourValar\EloquentJournal\Tests\AbstractSuite;

class MetricTypeTest extends AbstractSuite
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_short_description()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain', null, ['foo' => 'bar']);

        $this->assertNull($journal->short_description);
    }

    /**
     * @return void
     */
    public function test_full_description_without_data()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain');

        $this->assertNull($journal->full_description);
    }

    /**
     * @return void
     */
    public function test_full_description()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain', null, [
            'eloquent_journal::journal.type.metric' => 'eloquent_journal::journal.type.model', // key & value are translatable
            'qty' => 7,
            'list' => ['eloquent_journal::journal.type.integration'],
        ]);

        $html = $journal->full_description;

        $this->assertStringContainsString('journal-metric', $html);
        $this->assertStringContainsString('Metric', $html); // translated key
        $this->assertStringContainsString('Model', $html); // translated value
        $this->assertStringContainsString('Integration', $html); // translated nested value
        $this->assertStringContainsString('7', $html);
    }

    /**
     * @return void
     */
    public function test_handler_is_bound()
    {
        $journal = $this->getService()->captureMetric('user_token_obtain');

        $this->assertInstanceOf(MetricType::class, $journal->getTypeHandler());
    }
}
