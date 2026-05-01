<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Basic'),
    'title' => trans('Heading'),
    'icon' => 'fa fa-header',
    'settings' => [
        'heading-type' => [
            'type' => 'select',
            'label' => trans('Heading'),
            'options' => [
                ['value' => '1', 'label' => 'H1'],
                ['value' => '2', 'label' => 'H2'],
                ['value' => '3', 'label' => 'H3'],
                ['value' => '4', 'label' => 'H4'],
                ['value' => '5', 'label' => 'H5'],
                ['value' => '6', 'label' => 'H6'],
            ],
            'value' => '3'
        ],
        'heading-display' => [
            'type' => 'select',
            'label' => trans('Display'),
            'options' => [
                ['value' => 'display-0', 'label' => trans('None')],
                ['value' => 'display-1', 'label' => trans('Display 1')],
                ['value' => 'display-2', 'label' => trans('Display 2')],
                ['value' => 'display-3', 'label' => trans('Display 3')],
                ['value' => 'display-4', 'label' => trans('Display 4')],
                ['value' => 'display-5', 'label' => trans('Display 5')],
                ['value' => 'display-6', 'label' => trans('Display 6')],
            ],
        ],
        'primary-text' => [
            'type' => 'text',
            'label' => trans('Primary Text'),
            'value' => 'Heading',
        ],
        'secondary-text' => [
            'type' => 'text',
            'label' => trans('Secondary Text'),
            'value' => '',
        ],
    ],
];
