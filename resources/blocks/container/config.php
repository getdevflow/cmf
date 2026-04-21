<?php

return [
    'category' => 'Layout',
    'title' => 'Container',
    'icon' => 'fa fa-border-none',
    'settings' => [
        'container-wd' => [
            'type' => 'select',
            'label' => 'Container Width',
            'options' => [
                ['value' => 'container', 'label' => 'Default'],
                ['value' => 'container-sm', 'label' => 'Small'],
                ['value' => 'container-md', 'label' => 'Medium'],
                ['value' => 'container-lg', 'label' => 'Large'],
                ['value' => 'container-xl', 'label' => 'Extra Large'],
                ['value' => 'container-xxl', 'label' => 'Extra Extra Large'],
                ['value' => 'container-fluid', 'label' => 'Fluid'],
            ],
        ],
    ]
];
