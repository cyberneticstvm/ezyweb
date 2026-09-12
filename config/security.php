<?php

return [
    'headers_enabled' => env('SECURITY_HEADERS_ENABLED', true),
    'csp_report_only' => env('CSP_REPORT_ONLY', env('APP_ENV', 'production') !== 'production'),
    'csp_report_uri' => env('CSP_REPORT_URI'),
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),
    'hsts_include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
    'hsts_preload' => env('HSTS_PRELOAD', false),
];
