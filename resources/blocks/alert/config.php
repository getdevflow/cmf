<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Component'),
    'title' => trans('Alert'),
    'icon' => 'fa fa-bell',
    'settings' => [
        'alert-context' => [
            'type' => 'select',
            'label' => trans('Context'),
            'options' => [
                ['value' => 'alert-primary', 'label' => trans('Primary')],
                ['value' => 'alert-secondary', 'label' => trans('Secondary')],
                ['value' => 'alert-success', 'label' => trans('Success')],
                ['value' => 'alert-danger', 'label' => trans('Danger')],
                ['value' => 'alert-warning', 'label' => trans('Warning')],
                ['value' => 'alert-info', 'label' => trans('Info')],
                ['value' => 'alert-light', 'label' => trans('Light')],
                ['value' => 'alert-dark', 'label' => trans('Dark')],
            ],
            'value' => 'alert-primary',
        ],
    ],
];
