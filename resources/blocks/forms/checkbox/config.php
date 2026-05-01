<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Forms'),
    'title' => trans('Checkbox'),
    'icon' => 'fa fa-square-check',
    'settings' => [
        'checkbox-label' => [
            'type' => 'text',
            'label' => trans('Label'),
            'value' => 'Checkbox Label',
        ],
        'checkbox-value' => [
            'type' => 'text',
            'label' => trans('Checkbox Value'),
            'value' => '0',
        ],
        'checkbox-required' => [
            'type' => 'yes_no',
            'label' => trans('Required?'),
            'value' => '0',
        ],
    ],
];
