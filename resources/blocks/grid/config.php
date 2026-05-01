<?php

use function Codefy\Framework\Helpers\trans;

return [
        'category' => trans('Layout'),
        'title' => trans('Grid'),
        'icon' => 'fa fa-border-all',
        'settings' => [
            'columns-lg' => [
                'type' => 'select',
                'label' => trans('Columns'),
                'options' => [
                    ['value' => '6-6', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                    ['value' => '4-8', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;66%'],
                    ['value' => '8-4', 'label' => '66%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                    ['value' => '3-9', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;75%'],
                    ['value' => '9-3', 'label' => '75%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                    ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                    ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                    ['value' => '2-2-2-2-2-2', 'label' => '16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%'],
                    ['value' => '3-3-3-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                    ['value' => '4-4-4', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;33%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                    ['value' => '6-3-3', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                    ['value' => '3-3-6', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                    ['value' => '3-6-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;50%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                    ['value' => '2-8-2', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;60%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                    ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                    ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                ],
                'value' => '6-6'
            ],
                'columns-md' => [
                        'type' => 'select',
                        'label' => trans('On small displays'),
                        'options' => [
                                ['value' => 0, 'label' => trans('Use default layout')],
                                ['value' => '12', 'label' => trans('Columns become rows')],
                                ['value' => '6-6', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '4-8', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;66%'],
                                ['value' => '8-4', 'label' => '66%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '3-9', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;75%'],
                                ['value' => '9-3', 'label' => '75%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-2-2-2-2-2', 'label' => '16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%'],
                                ['value' => '3-3-3-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '4-4-4', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;33%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '6-3-3', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '3-3-6', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '3-6-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;50%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-8-2', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;60%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                        ],
                        'value' => 0
                ],
                'columns-sm' => [
                        'type' => 'select',
                        'label' => trans('On tablets'),
                        'options' => [
                                ['value' => 0, 'label' => trans('Same layout as on small displays')],
                                ['value' => '12', 'label' => trans('Columns become rows')],
                                ['value' => '6-6', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '4-8', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;66%'],
                                ['value' => '8-4', 'label' => '66%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '3-9', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;75%'],
                                ['value' => '9-3', 'label' => '75%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-2-2-2-2-2', 'label' => '16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%'],
                                ['value' => '3-3-3-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '4-4-4', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;33%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '6-3-3', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '3-3-6', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '3-6-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;50%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-8-2', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;60%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                        ],
                        'value' => '12'
                ],
                'columns-xs' => [
                        'type' => 'select',
                        'label' => trans('On mobile'),
                        'options' => [
                                ['value' => 0, 'label' => trans('Same layout as on tablets')],
                                ['value' => '12', 'label' => trans('Columns become rows')],
                                ['value' => '6-6', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '4-8', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;66%'],
                                ['value' => '8-4', 'label' => '66%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '3-9', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;75%'],
                                ['value' => '9-3', 'label' => '75%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-2-2-2-2-2', 'label' => '16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%&nbsp;&nbsp;|&nbsp;&nbsp;16%'],
                                ['value' => '3-3-3-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '4-4-4', 'label' => '33%&nbsp;&nbsp;|&nbsp;&nbsp;33%&nbsp;&nbsp;|&nbsp;&nbsp;33%'],
                                ['value' => '6-3-3', 'label' => '50%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '3-3-6', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;25%&nbsp;&nbsp;|&nbsp;&nbsp;50%'],
                                ['value' => '3-6-3', 'label' => '25%&nbsp;&nbsp;|&nbsp;&nbsp;50%&nbsp;&nbsp;|&nbsp;&nbsp;25%'],
                                ['value' => '2-8-2', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;60%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                                ['value' => '2-10', 'label' => '20%&nbsp;&nbsp;|&nbsp;&nbsp;80%'],
                                ['value' => '10-2', 'label' => '80%&nbsp;&nbsp;|&nbsp;&nbsp;20%'],
                        ],
                        'value' => '12'
                ],
                'gutter' => [
                        'type' => 'select',
                        'label' => trans('Space between columns'),
                        'options' => [
                                ['value' => 'g-0', 'label' => trans('None')],
                                ['value' => 'g-2', 'label' => trans('Subtle')],
                                ['value' => 'g-3', 'label' => trans('Small')],
                                ['value' => 'g-4', 'label' => trans('Reduced')],
                                ['value' => 'g-5', 'label' => trans('Default')],
                                ['value' => 'g-wide', 'label' => trans('Wide')],
                                ['value' => 'g-6', 'label' => trans('Large')],
                                ['value' => 'g-7', 'label' => trans('Maximized')]
                        ],
                        'value' => 'g-5'
                ],
                'column_count' => [
                        'type' => 'text',
                        'label' => trans('Number of columns'),
                        'value' => ''
                ],
                'order-lg' => [
                        'type' => 'select',
                        'label' => trans('Order of columns'),
                        'options' => [
                                ['value' => 'flex--row', 'label' => trans('Columns in standard order')],
                                ['value' => 'flex--row-reverse', 'label' => trans('Columns in reversed order')]
                        ],
                        'value' => 'flex--row'
                ],
                'order-md' => [
                        'type' => 'select',
                        'label' => trans('Order on small displays'),
                        'options' => [
                                ['value' => 0, 'label' => trans('Use same as above')],
                                ['value' => 'flex--row', 'label' => trans('Columns in standard order')],
                                ['value' => 'flex--row-reverse', 'label' => trans('Columns in reversed order')]
                        ],
                        'value' => 0
                ],
                'order-sm' => [
                        'type' => 'select',
                        'label' => 'Order on tablets',
                        'options' => [
                                ['value' => 0, 'label' => trans('Use same as on small displays')],
                                ['value' => 'flex--row', 'label' => trans('Columns in standard order')],
                                ['value' => 'flex--row-reverse', 'label' => trans('Columns in reversed order')]
                        ],
                        'value' => 0
                ],
                'order-xs' => [
                        'type' => 'select',
                        'label' => trans('Order on mobile'),
                        'options' => [
                                ['value' => 0, 'label' => trans('Use same as on small displays')],
                                ['value' => 'flex-column', 'label' => trans('Columns in standard order')],
                                ['value' => 'flex-column-reverse', 'label' => trans('Columns in reversed order')]
                        ],
                        'value' => 0
                ],
        ]
];
