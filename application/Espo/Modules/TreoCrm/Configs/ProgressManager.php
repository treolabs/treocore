<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Configs;

return [
    'actionService' => [
        'cancel' => 'CancelStatusAction',
        'close'  => 'CloseStatusAction'
    ],
    'statusAction'  => [
        'new'         => [
            'cancel'
        ],
        'in_progress' => [
            'cancel'
        ],
        'error'       => [
            'close'
        ],
        'success'     => [
            'close'
        ],
    ],
    'type'          => [
    // add types
    ]
];
