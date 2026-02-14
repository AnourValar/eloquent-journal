<?php

use AnourValar\EloquentJournal\Handlers\ModelType;

return [
    'model' => App\Journal::class,

    'entity' => [
        /*App\Payment::class => [ // Relation::morphMap() - must be set
            'title' => 'eloquent_journal::journal.entity.payment',

            'schema' => [ // modification for the values
                'data.user_ids' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => 'title'],
                'type' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.payment.type', 'display' => 'title'],
                'amount' => ['type' => ModelType::SCHEMA_MULTIPLE_ENCODED],
            ],

            'attribute_names' => [ // modification for the keys
                'models/payment.type_handler.{type}.attributes', // lang path
                'currencies' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.payment.currency', 'display' => 'title'],
            ],

            'observe' => true, // log all changes
            'exclude_attributes' => [], // exclude from the log
        ],*/
    ],

    'type' => [
        'model' => [
            'bind' => ModelType::class,
            'title' => 'eloquent_journal::journal.type.model',
        ],

        'integration' => [
            'bind' => AnourValar\EloquentJournal\Handlers\IntegrationType::class,
            'title' => 'eloquent_journal::journal.type.integration',
        ],

        'metric' => [
            'bind' => AnourValar\EloquentJournal\Handlers\MetricType::class,
            'title' => 'eloquent_journal::journal.type.metric',
        ],
    ],

    'event' => [
        // Model
        'create' => ['title' => 'eloquent_journal::journal.event.create', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'update' => ['title' => 'eloquent_journal::journal.event.update', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'delete' => ['title' => 'eloquent_journal::journal.event.delete', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'restore' => ['title' => 'eloquent_journal::journal.event.restore', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],

        // Integration
        //'mts_sms_send' => ['title' => 'eloquent_journal::journal.event.mts_sms_send', 'optgroup' => 'eloquent_journal::journal.type.integration', 'is_public' => false],

        // Metric
        'user_token_obtain' => ['title' => 'eloquent_journal::journal.event.user_token_obtain', 'optgroup' => 'eloquent_journal::journal.type.metric', 'is_public' => true],
        //'user_session_obtain' => ['title' => 'eloquent_journal::journal.event.user_session_obtain', 'optgroup' => 'eloquent_journal::journal.type.metric', 'is_public' => true],
    ],
];
