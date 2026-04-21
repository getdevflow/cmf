<?php

return [
    'category' => 'Basic',
    'title' => 'Button',
    'icon' => 'fa fa-stop',
    'settings' => [
        'button-style' => [
            'type' => 'select',
            'label' => 'Style',
            'options' => [
                ['value' => 'btn-primary', 'label' => 'Primary'],
                ['value' => 'btn-secondary', 'label' => 'Secondary'],
                ['value' => 'btn-success', 'label' => 'Success'],
                ['value' => 'btn-danger', 'label' => 'Danger'],
                ['value' => 'btn-warning', 'label' => 'Warning'],
                ['value' => 'btn-info', 'label' => 'Info'],
                ['value' => 'btn-light', 'label' => 'Light'],
                ['value' => 'btn-dark', 'label' => 'Dark'],
            ],
        ],
        'button-size' => [
            'type' => 'select',
            'label' => 'Size',
            'options' => [
                ['value' => 'btn-sm', 'label' => 'Small'],
                ['value' => 'btn-lg', 'label' => 'Large'],
            ],
        ],
        'button-type' => [
            'type' => 'select',
            'label' => 'Type',
            'options' => [
                ['value' => 'button', 'label' => 'Button'],
                ['value' => 'submit', 'label' => 'Submit'],
            ],
        ],
        'button-text' => [
            'type' => 'text',
            'label' => 'Text',
            'value' => 'Button Text',
        ],
    ],
];
