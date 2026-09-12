<?php

return [
    'disk' => env('MEDIA_DISK', 'local'),
    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 10240),
    'max_dimension' => (int) env('MEDIA_MAX_DIMENSION', 12000),
    'storage_limit_mb' => (int) env('MEDIA_STORAGE_LIMIT_MB', 1024),
    'quality' => (int) env('MEDIA_WEBP_QUALITY', 84),
    'variants' => ['thumbnail' => 240, 'small' => 640, 'medium' => 1280, 'large' => 1920, 'original' => 2560],
];
