<?php

use function Codefy\Framework\Helpers\trans;

return [
    'category' => trans('Basic'),
    'title' => trans('YouTube Video'),
    'icon' => 'fa fa-brands fa-square-youtube',
    'settings' => [
        'youtube_embed_url' => [
            'type' => 'text',
            'label' => trans('Youtube Embed URL'),
            'value' => 'https://www.youtube.com/embed/K5QMMNxB0ck?si=mFDkBdQih93F1TUH'
        ],
        'youtube_video_width' => [
            'type' => 'text',
            'label' => trans('Embed Width'),
            'value' => '560'
        ],
        'youtube_video_height' => [
            'type' => 'text',
            'label' => trans('Embed Height'),
            'value' => '315'
        ],
    ],
];
