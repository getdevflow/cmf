<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Layout'),
    'title' => trans('Container'),
    'icon' => 'fa fa-border-none',
    'settings' => [
        'container-wd' => [
            'type' => 'select',
            'label' => trans('Container Width'),
            'options' => [
                ['value' => 'container', 'label' => trans('Default')],
                ['value' => 'container-sm', 'label' => trans('Small')],
                ['value' => 'container-md', 'label' => trans('Medium')],
                ['value' => 'container-lg', 'label' => trans('Large')],
                ['value' => 'container-xl', 'label' => trans('Extra Large')],
                ['value' => 'container-xxl', 'label' => trans('Extra Extra Large')],
                ['value' => 'container-fluid', 'label' => trans('Fluid')],
            ],
        ],
    ]
];
