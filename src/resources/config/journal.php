<?php

use AnourValar\EloquentJournal\Handlers\ModelType;

return [
    'model' => App\Journal::class,

    'entity' => [
        /*App\Payment::class => [ // Relation::morphMap() - must be set
            'title' => 'journal::journal.entity.payment',

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
            'title' => 'journal::journal.type.model',
        ],

        'integration' => [
            'bind' => AnourValar\EloquentJournal\Handlers\IntegrationType::class,
            'title' => 'journal::journal.type.integration',
        ],

        'metric' => [
            'bind' => AnourValar\EloquentJournal\Handlers\MetricType::class,
            'title' => 'journal::journal.type.metric',
        ],
    ],

    'event' => [
        // Model
        'create' => ['title' => 'journal::journal.event.create', 'optgroup' => 'journal::journal.type.model', 'is_public' => false],
        'update' => ['title' => 'journal::journal.event.update', 'optgroup' => 'journal::journal.type.model', 'is_public' => false],
        'delete' => ['title' => 'journal::journal.event.delete', 'optgroup' => 'journal::journal.type.model', 'is_public' => false],
        'restore' => ['title' => 'journal::journal.event.restore', 'optgroup' => 'journal::journal.type.model', 'is_public' => false],

        // Integration
        //'mts_sms_send' => ['title' => 'journal::journal.event.mts_sms_send', 'optgroup' => 'journal::journal.type.integration', 'is_public' => false],

        // Metric
        'user_token_obtain' => ['title' => 'journal::journal.event.user_token_obtain', 'optgroup' => 'journal::journal.type.metric', 'is_public' => true],
        //'user_session_obtain' => ['title' => 'journal::journal.event.user_session_obtain', 'optgroup' => 'journal::journal.type.metric', 'is_public' => true],
    ],
];
