<?php

return [
    'attributes' => [
        'id' => 'ID',
        'user_id' => 'Пользователь (инициатор)',
        'ip_address' => 'IP адрес (инициатора)',
        'entity' => 'Сущность',
        'entity_id.*' => 'ID сущности',
        'type' => 'Тип журнала',
        'event' => 'Событие',
        'data' => 'Карточка',
        'success' => 'Успешность',
        'tags' => 'Теги',
            'tags.*' => 'Тег',
        'created_at' => 'Дата создания',
        'updated_at' => 'Дата изменения',
    ],

    'user_id_not_exists' => 'Несуществующий пользователь.',

    'entity' => [
        //'payment' => 'Транзакция',
    ],

    'type' => [
        'model' => 'Модель',
        'integration' => 'Интеграция',
        'metric' => 'Метрика',
    ],

    'type_handler' => [
        'model' => [
            'short_description_named' => 'Изменение :names',
            'short_description_qty' => 'Изменение :qty полей',

            'full_description_true' => 'Да',
            'full_description_false' => 'Нет',
            'full_description_null' => '—',
        ],
    ],

    'event' => [
        // Model
        'create' => 'Создание',
        'update' => 'Изменение',
        'delete' => 'Удаление',
        'restore' => 'Восстановление',

        // Integration
        //'mts_sms_send' => 'МТС (отправка смс)',

        // Metric
        'user_token_obtain' => 'Аутентификация (токен)',
        //'user_session_obtain' => 'Аутентификация (сессия)',
    ],
];
