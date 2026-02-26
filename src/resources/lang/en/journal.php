<?php

return [
    'attributes' => [
        'id' => 'ID',
        'user_id' => 'User (initiator)',
        'ip_address' => 'IP address (initiator)',
        'entity' => 'Entity',
        'entity_id.*' => 'Entity ID',
        'type' => 'Journal type',
        'event' => 'Event',
        'data' => 'Data',
        'success' => 'Success',
        'tags' => 'Tags',
            'tags.*' => 'Tag',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    'user_id_not_exists' => 'Non-existent user.',

    'entity' => [
        //'payment' => 'Transactions',
    ],

    'type' => [
        'model' => 'Model',
        'integration' => 'Integration',
        'metric' => 'Metric',
    ],

    'type_handler' => [
        'model' => [
            'short_description_named' => 'Change :names',
            'short_description_qty' => 'Change :qty fields',

            'full_description_true' => 'Yes',
            'full_description_false' => 'No',
            'full_description_null' => '—',
        ],
    ],

    'event' => [
        // Model
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',

        // Integration
        //'mts_sms_send' => 'MTS (send SMS)',

        // Metric
        'user_token_obtain' => 'Authentication (token)',
        //'user_session_obtain' => 'Authentication (session)',
    ],
];
