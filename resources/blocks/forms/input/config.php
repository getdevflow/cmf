<?php

return [
    'category' => 'Forms',
    'title' => 'Input',
    'icon' => 'fa fa-arrow-pointer',
    'settings' => [
        'input-label-text' => [
            'type' => 'text',
            'label' => 'Label',
            'value' => 'Field Label',
        ],
        'input-field-type' => [
            'type' => 'select',
            'label' => 'Input Type',
            'options' => [
                ['value' => 'text', 'label' => 'Text'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'password', 'label' => 'Password'],
                ['value' => 'hidden', 'label' => 'Hidden'],
                ['value' => 'number', 'label' => 'Number'],
                ['value' => 'tel', 'label' => 'Telephone'],
            ],
            'value' => 'text'
        ],
        'input-field-name' => [
            'type' => 'text',
            'label' => 'Field Name',
            'value' => 'text',
        ],
        'input-id' => [
            'type' => 'text',
            'label' => 'Field ID',
            'value' => '',
        ],
        'input-required' => [
            'type' => 'yes_no',
            'label' => 'Required?',
            'value' => '1',
        ],
        'input-placeholder' => [
            'type' => 'text',
            'label' => 'Placeholder',
            'value' => 'text',
        ],
    ],
];
