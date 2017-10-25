<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        // Application settings
        'GoMasjid' => [
            'api_url' => 'http://localhost:8000',
            // 'api_url' => 'https://apps.gomasjid.org',
            'image_dir' => __DIR__.'/../public/slideshows/',
            'album_dir' => __DIR__.'/../public/albums/',
        ],
    ],
];
