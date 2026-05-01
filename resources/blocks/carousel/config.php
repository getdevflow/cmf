<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Component'),
    'title' => trans('Carousel'),
    'icon' => 'fa fa-panorama',
    'settings' => [
        'carousel_item_count' => [
            'type' => 'text',
            'label' => trans('Number of Items'),
            'value' => '2'
        ],
    ],
];
