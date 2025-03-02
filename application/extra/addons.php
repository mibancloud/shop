<?php

return [
    'autoload' => false,
    'hooks' => [
        'upgrade' => [
            'shopro',
        ],
        'app_init' => [
            'shopro',
        ],
        'config_init' => [
            'shopro',
            'summernote',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
