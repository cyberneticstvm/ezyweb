<?php

return [
    'reserved_subdomains' => [
        'www',
        'app',
        'admin',
        'api',
        'mail',
        'support',
        'help',
        'status',
        'billing',
        'dashboard',
        'login',
        'register',
        'static',
        'assets',
        'cdn',
        'demo',
        'preview',
        'blog',
        'docs',
    ],
    'domain_cache_seconds' => (int) env('TENANT_DOMAIN_CACHE_SECONDS', 3600),
];
