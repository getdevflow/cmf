<?php

use function Codefy\Framework\Helpers\vendor_path;

return [
    'stubs_path' => vendor_path(path: 'codefyphp/codefy/src/Stubs'),
    'presets' => [
        'aggregate' => [
            'label' => 'Aggregate',
            'files' => [
                ['stub' => 'aggregate.stub', 'suffix' => ''],
            ],
        ],
        'event' => [
            'label' => 'Domain Event',
            'files' => [
                ['stub' => 'event.stub', 'suffix' => ''],
            ],
        ],
        'command' => [
            'label' => 'Command + Handler',
            'files' => [
                ['stub' => 'command.stub', 'suffix' => 'Command'],
                ['stub' => 'command_handler.stub', 'suffix' => 'CommandHandler'],
            ],
        ],
        'query' => [
            'label' => 'Query + Handler',
            'files' => [
                ['stub' => 'query.stub', 'suffix' => 'Query'],
                ['stub' => 'query_handler.stub', 'suffix' => 'QueryHandler'],
            ],
        ],
        'controller' => [
            'label' => 'Controller',
            'files' => [
                ['stub' => 'controller.stub', 'suffix' => 'Controller'],
            ],
        ],
        'middleware' => [
            'label' => 'Middleware',
            'files' => [
                ['stub' => 'middleware.stub', 'suffix' => 'Middleware'],
            ],
        ],
        'provider' => [
            'label' => 'Service Provider',
            'files' => [
                ['stub' => 'provider.stub', 'suffix' => 'ServiceProvider'],
            ],
        ],
        'console' => [
            'label' => 'Console Command',
            'files' => [
                ['stub' => 'console.stub', 'suffix' => 'Command'],
            ],
        ],
        'validator' => [
            'label' => 'Input Validator',
            'files' => [
                ['stub' => 'validator.stub', 'suffix' => 'Validator'],
            ],
        ],
        'request' => [
            'label' => 'Form Request',
            'files' => [
                    ['stub' => 'formrequest.stub', 'suffix' => 'Request'],
            ],
        ],
        'repository' => [
            'label' => 'Aggregate Repository',
            'files' => [
                ['stub' => 'repository.stub', 'suffix' => 'AggregateRepository'],
            ],
        ],
    ],
];
