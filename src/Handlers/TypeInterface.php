<?php

namespace AnourValar\EloquentJournal\Handlers;

interface TypeInterface
{
    /**
     * Validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\Illuminate\Validation\Validator &$validator): void;

    /**
     * Short description
     *
     * @param \AnourValar\EloquentJournal\Journal $journal
     * @return string|null
     */
    public function shortDescription(\AnourValar\EloquentJournal\Journal $journal): ?string;

    /**
     * Long description
     *
     * @param \AnourValar\EloquentJournal\Journal $journal
     * @return string|null
     */
    public function fullDescription(\AnourValar\EloquentJournal\Journal $journal): ?string;
}
