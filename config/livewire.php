<?php

return [
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['file', 'max:20480'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma', 'avif',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
