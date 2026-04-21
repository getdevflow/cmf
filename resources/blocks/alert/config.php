<?php

return [
    'category' => 'Component',
    'title' => 'Alert',
    'icon' => 'fa fa-bell',
    'settings' => [
        'alert-context' => [
            'type' => 'select',
            'label' => 'Context',
            'options' => [
                ['value' => 'alert-primary', 'label' => 'Primary'],
                ['value' => 'alert-secondary', 'label' => 'Secondary'],
                ['value' => 'alert-success', 'label' => 'Success'],
                ['value' => 'alert-danger', 'label' => 'Danger'],
                ['value' => 'alert-warning', 'label' => 'Warning'],
                ['value' => 'alert-info', 'label' => 'Info'],
                ['value' => 'alert-light', 'label' => 'Light'],
                ['value' => 'alert-dark', 'label' => 'Dark'],
            ],
            'value' => 'alert-primary',
        ],
    ],
];
