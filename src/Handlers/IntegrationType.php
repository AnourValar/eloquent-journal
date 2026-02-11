<?php

namespace AnourValar\EloquentJournal\Handlers;

class IntegrationType implements TypeInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::validate()
     */
    public function validate(\Illuminate\Validation\Validator &$validator): void
    {
        $validator->addRules([
            'event' => ['not_in:create,update,delete,restore'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::shortDescription()
     */
    public function shortDescription(\AnourValar\EloquentJournal\Journal $journal): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::fullDescription()
     */
    public function fullDescription(\AnourValar\EloquentJournal\Journal $journal): ?string
    {
        if (! isset($journal->data)) {
            return null;
        }

        return view('journal::handler.integration', ['data' => $journal->data])->render();
    }
}
