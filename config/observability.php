<?php

return [
    'slow_request_ms' => (int) env('SLOW_REQUEST_THRESHOLD_MS', 1500),
    'slow_query_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 500),
    'readiness' => [
        'database' => env('READINESS_CHECK_DATABASE', true),
        'cache' => env('READINESS_CHECK_CACHE', true),
    ],
];
