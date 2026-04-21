<?php

return [
    'category' => 'Basic',
    'title' => 'Heading',
    'icon' => 'fa fa-header',
    'settings' => [
        'heading-type' => [
            'type' => 'select',
            'label' => 'Heading',
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
            'label' => 'Display',
            'options' => [
                ['value' => 'display-0', 'label' => 'None'],
                ['value' => 'display-1', 'label' => 'Display 1'],
                ['value' => 'display-2', 'label' => 'Display 2'],
                ['value' => 'display-3', 'label' => 'Display 3'],
                ['value' => 'display-4', 'label' => 'Display 4'],
                ['value' => 'display-5', 'label' => 'Display 5'],
                ['value' => 'display-6', 'label' => 'Display 6'],
            ],
        ],
        'primary-text' => [
            'type' => 'text',
            'label' => 'Primary Text',
            'value' => 'Heading',
        ],
        'secondary-text' => [
            'type' => 'text',
            'label' => 'Secondary Text',
            'value' => '',
        ],
    ],
];
