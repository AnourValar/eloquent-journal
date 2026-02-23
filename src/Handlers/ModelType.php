<?php

namespace AnourValar\EloquentJournal\Handlers;

class ModelType implements TypeInterface
{
    /**
     * @var string
     */
    public const SCHEMA_MODEL = 'schema_model';
    public const SCHEMA_CONFIG = 'schema_config';
    public const SCHEMA_MULTIPLE_ENCODED = 'schema_multiple_encoded';

    /**
     * @var string
     */
    protected string $timezone;

    /**
     * DI
     *
     * @param \AnourValar\EloquentValidation\ValidatorHelper $validatorHelper
     * @param \AnourValar\LaravelAtom\Helpers\DateHelper $dateHelper
     * @return void
     */
    public function __construct(
        protected \AnourValar\EloquentValidation\ValidatorHelper $validatorHelper,
        protected \AnourValar\LaravelAtom\Helpers\DateHelper $dateHelper,
    ) {
        $this->timezone = config('app.timezone_client') ?? 'UTC';
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::validate()
     */
    public function validate(\Illuminate\Validation\Validator &$validator): void
    {
        $validator->addRules([
            'entity' => ['required'],
            'event' => ['in:create,update,delete,restore'],
            'data' => ['required', 'array_keys'],
                'data.old' => ['sometimes', 'required', 'array'],
                'data.new' => ['sometimes', 'required', 'array'],
                'data.schema_old' => ['sometimes', 'required', 'array'],
                'data.schema_new' => ['sometimes', 'required', 'array'],
                'data.attribute_names_old' => ['sometimes', 'required', 'array'],
                'data.attribute_names_new' => ['sometimes', 'required', 'array'],
                'data.errors' => ['required_if_declined:success', 'prohibited_if_accepted:success', 'not_empty', 'array'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::shortDescription()
     */
    public function shortDescription(\AnourValar\EloquentJournal\Journal $journal): ?string
    {
        if ($journal->event == 'update') {
            $changed = [];

            $attributeNames = (new ($journal->entity_class))->getAttributeNames();
            foreach ($journal->data['new'] as $key => $value) {
                if ($value !== ($journal->data['old'][$key] ?? null)) {
                    $changed[] = $attributeNames[$key] ?? $key;
                }
            }

            if (count($changed) >= 1 && count($changed) <= 3) {
                return trans('eloquent_journal::journal.type_handler.model.short_description_named', ['names' => '«' . implode('», «', $changed) . '»']);
            }

            return trans('eloquent_journal::journal.type_handler.model.short_description_qty', ['qty' => count($changed)]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentJournal\Handlers\TypeInterface::fullDescription()
     */
    public function fullDescription(\AnourValar\EloquentJournal\Journal $journal): ?string
    {
        $data = $this->fullDescriptionData($journal);
        if (! $data) {
            return null;
        }

        return view('eloquent_journal::handler.model', ['data' => $data])->render();
    }

    /**
     * @param \AnourValar\EloquentJournal\Journal $journal
     * @return array|null
     */
    protected function fullDescriptionData(\AnourValar\EloquentJournal\Journal $journal): ?array
    {
        $result = [];
        $attributeNamesOld = array_replace($journal->data['attribute_names_old'] ?? [], $this->getAttributeNamesAfter($journal, 'old'));
        $attributeNamesNew = array_replace($journal->data['attribute_names_new'] ?? [], $this->getAttributeNamesAfter($journal, 'new'));
        $attributesSort = array_keys((new ($journal->entity_class))->getAttributeNames());

        if (isset($journal->data['old'])) {
            $data = $journal->data['old'];
            uksort($data, fn ($a, $b) => array_search($a, $attributesSort) <=> array_search($b, $attributesSort));

            $result['old'] = $this->format(array_replace_recursive($data, $journal->data['schema_old'] ?? []), $attributeNamesOld);
        }

        if (isset($journal->data['new'])) {
            $data = $journal->data['new'];
            uksort($data, fn ($a, $b) => array_search($a, $attributesSort) <=> array_search($b, $attributesSort));

            $result['new'] = $this->format(array_replace_recursive($data, $journal->data['schema_new'] ?? []), $attributeNamesNew);
        }

        if (isset($journal->data['errors'])) {
            $result['errors'] = $journal->data['errors'];
        }

        return $result ? $result : null;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $event
     * @return array|null
     * @throws \LogicException
     */
    public function getData(\Illuminate\Database\Eloquent\Model $model, string $event): ?array
    {
        $modelClass = get_class($model);
        if (! in_array(\AnourValar\EloquentValidation\ModelTrait::class, class_uses($model))) {
            throw new \LogicException('The model ['.$modelClass.'] is not supported.');
        }

        $data = $model->getAttributes();
        $dataOriginal = $model->getRawOriginal();
        $schema = config('eloquent_journal.entity.' . $modelClass . '.schema');
        if (! isset($schema)) {
            throw new \LogicException('The model ['.$modelClass.'] is not configured.');
        }

        $this->cleanData($data, $dataOriginal, $model, config('eloquent_journal.entity.' . $modelClass . '.exclude_attributes', []));
        if ($data == $dataOriginal && $event == 'update') {
            return null; // nothing to save
        }

        if (in_array($event, ['create', 'restore'])) {
            $result = [
                'new' => $data,
                'schema_new' => $this->schema($data, $schema),
                'attribute_names_new' => $this->getAttributeNamesBefore($modelClass, $data),
            ];
        } elseif (in_array($event, ['delete'])) {
            $result = [
                'old' => $dataOriginal,
                'schema_old' => $this->schema($dataOriginal, $schema),
                'attribute_names_old' => $this->getAttributeNamesBefore($modelClass, $dataOriginal),
            ];
        } else {
            $result = [
                'old' => $dataOriginal,
                'new' => $data,
                'schema_old' => $this->schema($dataOriginal, $schema),
                'schema_new' => $this->schema($data, $schema),
                'attribute_names_old' => $this->getAttributeNamesBefore($modelClass, $dataOriginal),
                'attribute_names_new' => $this->getAttributeNamesBefore($modelClass, $data),
            ];
        }

        return array_filter($result, fn ($item) => $item !== null && $item !== []);
    }

    /**
     * @param mixed $data
     * @param mixed $dataOriginal
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $excludeAttributes
     * @return void
     */
    protected function cleanData(&$data, &$dataOriginal, \Illuminate\Database\Eloquent\Model $model, array $excludeAttributes): void
    {
        $data = (array) $data;
        $dataOriginal = (array) $dataOriginal;

        $casts = $model->getCasts();
        $hidden = $model->getHidden();
        $computed = array_merge($model->getComputed(), [$model->getUpdatedAtColumn()], $excludeAttributes);

        ksort($data);
        ksort($dataOriginal);

        foreach (array_unique(array_keys(array_merge($data, $dataOriginal))) as $attribute) {
            // computed?
            if (in_array($attribute, $computed)) {
                unset($data[$attribute], $dataOriginal[$attribute]);
                continue;
            }

            // json?
            if (isset($casts[$attribute]) && in_array($casts[$attribute], ['json:unicode', 'json', 'array'])) {
                if (isset($data[$attribute])) {
                    $data[$attribute] = json_decode($data[$attribute], true);
                }
                if (isset($dataOriginal[$attribute])) {
                    $dataOriginal[$attribute] = json_decode($dataOriginal[$attribute], true);
                }
            }

            // scalar?
            if (isset($casts[$attribute]) && in_array($casts[$attribute], ['integer', 'string', 'float', 'boolean'])) {
                if (isset($data[$attribute])) {
                    settype($data[$attribute], $casts[$attribute]);
                }

                if (isset($dataOriginal[$attribute])) {
                    settype($dataOriginal[$attribute], $casts[$attribute]);
                }
            }

            // hidden or encrypted?
            if (in_array($attribute, $hidden) || stripos($casts[$attribute] ?? '', 'encrypted') === 0) {
                if (array_key_exists($attribute, $data)) {
                    $data[$attribute] = isset($data[$attribute]) ? hash('sha256', json_encode($data[$attribute])) . ' [HASH]' : null;
                }

                if (array_key_exists($attribute, $dataOriginal)) {
                    $dataOriginal[$attribute] = isset($dataOriginal[$attribute]) ? hash('sha256', json_encode($dataOriginal[$attribute])) . ' [HASH]' : null;
                }
            }

            // custom casts?
            if (method_exists($casts[$attribute] ?? '', 'castUsing')) {
                if (isset($data[$attribute])) {
                    $data[$attribute] = json_decode($data[$attribute], true);
                }

                if (isset($dataOriginal[$attribute])) {
                    $dataOriginal[$attribute] = json_decode($dataOriginal[$attribute], true);
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $schema
     * @param array $path
     * @return array
     */
    protected function schema(array $data, array $schema, array $path = []): array
    {
        foreach ($data as $key => &$value) {
            $currPath = [...$path, $key];

            foreach (array_keys($schema) as $schemaPath) {
                if ($this->validatorHelper->isMatching($schemaPath, $currPath)) {
                    if (is_array($value)) {
                        $value = $this->applySchema($value, $schema[$schemaPath]);
                    } elseif (isset($value)) {
                        $value = $this->applySchema([$value], $schema[$schemaPath])[0];
                    }

                    continue 2;
                }
            }

            if (is_array($value)) {
                $value = $this->schema($value, $schema, $currPath);
                if (! $value) {
                    unset($data[$key]);
                }
            } else {
                unset($data[$key]);
            }
        }
        unset($value);

        return $data;
    }

    /**
     * @param array $values
     * @param array $details
     * @return array
     * @throws \LogicException
     */
    protected function applySchema(array $values, array $details): array // @TODO: schema handlers
    {
        if ($details['type'] == self::SCHEMA_MODEL) {

            $class = $details['model'];
            $display = $details['display'];
            $select = [$display, (new $class())->getKeyName()];

            return \Cache::driver('array')->rememberForever(implode(' / ', [$class, $display, ...$values]), function () use ($values, $class, $display, $select) {
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($class))) {
                    $collection = $class::withTrashed()->select($select)->find($values);
                } else {
                    $collection = $class::select($select)->find($values);
                }

                return $collection
                    ->sort(fn ($a, $b) => array_search($a->getKey(), $values) <=> array_search($b->getKey(), $values))
                    ->transform(fn ($item) => sprintf('%s [#%s]', (string) $item->$display, (string) $item->getKey()))
                    ->values()
                    ->toArray();
            });

        } elseif ($details['type'] == self::SCHEMA_CONFIG) {

            foreach ($values as &$value) {
                $value = trans(config($details['config'] . '.' . $value)[$details['display']], [], config('app.fallback_locale'));
            }
            unset($value);
            return $values;

        } elseif ($details['type'] == self::SCHEMA_MULTIPLE_ENCODED) {

            foreach ($values as &$value) {
                $value = number_format(
                    \App::make(\AnourValar\LaravelAtom\Helpers\NumberHelper::class)->decodeMultiple($value),
                    strlen(config('atom.number.multiple')) - 1,
                    '.',
                    ''
                );
            }
            unset($value);
            return $values;

        } else {

            throw new \LogicException('Incorrect schema type.');

        }
    }

    /**
     * @param array $attributes
     * @param array $names
     * @param array $path
     * @return array
     */
    protected function format(array $attributes, array $names, array $path = []): array
    {
        $data = [];

        foreach ($attributes as $key => $value) {
            $currPath = $path;
            $currPath[] = $key;

            foreach ($names as $nameKey => $nameValue) {
                if (is_numeric($key) || mb_substr($nameKey, -1) == '*') {
                    continue;
                }

                if ($this->validatorHelper->isMatching($nameKey, $currPath) && ! isset($data[$nameValue])) {
                    $key = $nameValue;
                    break;
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->format($value, $names, $currPath);
            } else {
                $data[$key] = $this->applyFormat($value);
            }
        }

        return $data;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function applyFormat($value)
    {
        if ($value === true) {
            return trans('eloquent_journal::journal.type_handler.model.full_description_true');
        }

        if ($value === false) {
            return trans('eloquent_journal::journal.type_handler.model.full_description_false');
        }

        if ($value === null) {
            return trans('eloquent_journal::journal.type_handler.model.full_description_null');
        }

        if (is_string($value) && preg_match('#^\d{4}\-\d{2}\-\d{2} \d{2}\:\d{2}\:\d{2}$#uS', $value)) {
            return $this->dateHelper->formatDateTime($value, $this->timezone) . " [{$this->timezone}]";
        }

        return $value;
    }

    /**
     * @param string $modelClass
     * @param array $data
     * @return array
     */
    protected function getAttributeNamesBefore(string $modelClass, array $data): array
    {
        $attributeNames = (new $modelClass())->getAttributeNames();

        foreach (config("eloquent_journal.entity.{$modelClass}.attribute_names") as $attributePath => $attributeTitle) {
            if (is_numeric($attributePath)) {

                $extraNames = preg_replace_callback('#\{([a-z\d\_]+)\}#iu', function (array $patterns) use ($data) {
                    return $data[$patterns[1]] ?? null;
                }, $attributeTitle);

                $extraNames = trans($extraNames, [], config('app.fallback_locale'));
                if (! is_array($extraNames)) {
                    $extraNames = [];
                }

            } else {

                $extraNames = [];
                $curr = $data;
                foreach (explode('.', $attributePath) as $attributePathPart) {
                    $curr = $curr[$attributePathPart] ?? null;
                }

                if (isset($curr)) {
                    $newTitles = $this->applySchema(array_keys($curr), $attributeTitle);
                    foreach (array_keys($curr) as $oldTitle) {
                        $extraNames[$attributePath . '.' . $oldTitle] = array_shift($newTitles);
                    }
                }

            }

            $attributeNames = array_replace($attributeNames, $extraNames);
        }

        return $attributeNames;
    }

    /**
     * @param \AnourValar\EloquentJournal\Journal $journal
     * @param string $key
     * @return array
     */
    protected function getAttributeNamesAfter(\AnourValar\EloquentJournal\Journal $journal, string $key): array
    {
        $attributeNames = (new ($journal->entity_class))->getAttributeNames();

        foreach ($journal->entity_details['attribute_names'] as $attributePath => $attributeTitle) {
            if (! is_numeric($attributePath)) {
                continue;
            }

            $extraNames = preg_replace_callback('#\{([a-z\d\_]+)\}#iu', function (array $patterns) use ($journal, $key) {
                return $journal->data[$key][$patterns[1]] ?? null;
            }, $attributeTitle);

            $extraNames = trans($extraNames);
            if (! is_array($extraNames)) {
                $extraNames = [];
            }

            $attributeNames = array_replace($attributeNames, $extraNames);
        }

        return $attributeNames;
    }
}
