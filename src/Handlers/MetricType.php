<?php

namespace AnourValar\EloquentJournal\Handlers;

class MetricType implements TypeInterface
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

        return view('journal::handler.metric', ['data' => $this->transRecursive($journal->data)])->render();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function transRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (! is_numeric($key)) {
                $key = trans($key);
            }

            if (is_array($value)) {
                $result[$key] = $this->transRecursive($value);
            } elseif (is_string($value)) {
                $result[$key] = trans($value);
            } else {
                $result[$key ] = $value;
            }
        }

        return $result;
    }
}
