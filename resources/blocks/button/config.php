<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Basic'),
    'title' => trans('Button'),
    'icon' => 'fa fa-stop',
    'settings' => [
        'button-style' => [
            'type' => 'select',
            'label' => trans('Style'),
            'options' => [
                ['value' => 'btn-primary', 'label' => trans('Primary')],
                ['value' => 'btn-secondary', 'label' => trans('Secondary')],
                ['value' => 'btn-success', 'label' => trans('Success')],
                ['value' => 'btn-danger', 'label' => trans('Danger')],
                ['value' => 'btn-warning', 'label' => trans('Warning')],
                ['value' => 'btn-info', 'label' => trans('Info')],
                ['value' => 'btn-light', 'label' => trans('Light')],
                ['value' => 'btn-dark', 'label' => trans('Dark')],
            ],
        ],
        'button-size' => [
            'type' => 'select',
            'label' => trans('Size'),
            'options' => [
                ['value' => 'btn-sm', 'label' => trans('Small')],
                ['value' => 'btn-lg', 'label' => trans('Large')],
            ],
        ],
        'button-type' => [
            'type' => 'select',
            'label' => trans('Type'),
            'options' => [
                ['value' => 'button', 'label' => trans('Button')],
                ['value' => 'submit', 'label' => trans('Submit')],
            ],
        ],
        'button-text' => [
            'type' => 'text',
            'label' => trans('Text'),
            'value' => 'Button Text',
        ],
    ],
];
