<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Component'),
    'title' => trans('Accordion'),
    'icon' => 'fa fa-layer-group',
    'settings' => [
        'accordion_item_count' => [
            'type' => 'text',
            'label' => trans('Number of Items'),
            'value' => '2'
        ],
    ],
];
