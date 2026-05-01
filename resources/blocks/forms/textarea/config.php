<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Forms'),
    'title' => trans('Textarea'),
    'icon' => 'fa fa-paragraph',
    'settings' => [
        'textarea-label' => [
            'type' => 'text',
            'label' => trans('Label'),
            'value' => 'Textarea Label',
        ],
        'textarea-required' => [
            'type' => 'yes_no',
            'label' => trans('Required?'),
            'value' => '0',
        ],
        'textarea-placeholder' => [
            'type' => 'text',
            'label' => trans('Placeholder'),
            'value' => 'textarea',
        ],
    ],
];
