<?php

return [
    'limits' => [
        'services' => (int) env('CONTENT_LIMIT_SERVICES', 100),
        'products' => (int) env('CONTENT_LIMIT_PRODUCTS', 250),
        'brands' => (int) env('CONTENT_LIMIT_BRANDS', 100),
        'testimonials' => (int) env('CONTENT_LIMIT_TESTIMONIALS', 100),
        'portfolio' => (int) env('CONTENT_LIMIT_PORTFOLIO', 100),
        'gallery' => (int) env('CONTENT_LIMIT_GALLERY', 250),
        'team' => (int) env('CONTENT_LIMIT_TEAM', 100),
        'faq' => (int) env('CONTENT_LIMIT_FAQ', 200),
        'blog' => (int) env('CONTENT_LIMIT_BLOG', 500),
        'news' => (int) env('CONTENT_LIMIT_NEWS', 500),
        'events' => (int) env('CONTENT_LIMIT_EVENTS', 500),
        'careers' => (int) env('CONTENT_LIMIT_CAREERS', 100),
    ],
];
