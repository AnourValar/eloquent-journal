<?php

namespace AnourValar\EloquentJournal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JournalCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var \AnourValar\EloquentJournal\Journal
     */
    public $journal;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(\AnourValar\EloquentJournal\Journal $journal)
    {
        $this->journal = $journal;
    }
}
