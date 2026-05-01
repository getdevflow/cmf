<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Forms'),
    'title' => trans('Input'),
    'icon' => 'fa fa-arrow-pointer',
    'settings' => [
        'input-label-text' => [
            'type' => 'text',
            'label' => trans('Label'),
            'value' => 'Field Label',
        ],
        'input-field-type' => [
            'type' => 'select',
            'label' => trans('Input Type'),
            'options' => [
                ['value' => 'text', 'label' => trans('Text')],
                ['value' => 'email', 'label' => trans('Email')],
                ['value' => 'password', 'label' => trans('Password')],
                ['value' => 'hidden', 'label' => trans('Hidden')],
                ['value' => 'number', 'label' => trans('Number')],
                ['value' => 'tel', 'label' => trans('Telephone')],
            ],
            'value' => 'text'
        ],
        'input-field-name' => [
            'type' => 'text',
            'label' => trans('Field Name'),
            'value' => 'text',
        ],
        'input-id' => [
            'type' => 'text',
            'label' => trans('Field ID'),
            'value' => '',
        ],
        'input-required' => [
            'type' => 'yes_no',
            'label' => trans('Required?'),
            'value' => '1',
        ],
        'input-placeholder' => [
            'type' => 'text',
            'label' => trans('Placeholder'),
            'value' => 'text',
        ],
    ],
];
