<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Forms'),
    'title' => trans('Form'),
    'icon' => 'fa fa-pen-to-square',
    'settings' => [
        'form-id' => [
            'type' => 'text',
            'label' => trans('Form ID'),
            'value' => 'contactForm',
        ],
        'form-action' => [
            'type' => 'text',
            'label' => trans('Form Action'),
            'value' => '',
        ],
    ],
];
