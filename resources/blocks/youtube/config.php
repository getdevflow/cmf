<?php

return [
    'category' => 'Basic',
    'title' => 'YouTube Video',
    'icon' => 'fa fa-brands fa-square-youtube',
    'settings' => [
        'youtube_embed_url' => [
            'type' => 'text',
            'label' => 'Youtube Embed URL',
            'value' => 'https://www.youtube.com/embed/K5QMMNxB0ck?si=mFDkBdQih93F1TUH'
        ],
        'youtube_video_width' => [
            'type' => 'text',
            'label' => 'Embed Width',
            'value' => '560'
        ],
        'youtube_video_height' => [
            'type' => 'text',
            'label' => 'Embed Height',
            'value' => '315'
        ],
    ],
];
