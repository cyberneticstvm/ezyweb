<?php

return [
    'websites_limit' => ['type' => 'limit', 'label' => 'Websites', 'description' => 'Maximum websites per tenant.', 'max' => 100],
    'pages_limit' => ['type' => 'limit', 'label' => 'Pages', 'description' => 'Maximum pages per website.', 'max' => 1000],
    'services_limit' => ['type' => 'limit', 'label' => 'Services', 'description' => 'Maximum service entries.', 'max' => 10000],
    'products_limit' => ['type' => 'limit', 'label' => 'Products', 'description' => 'Maximum catalogue products.', 'max' => 100000],
    'blog_posts_limit' => ['type' => 'limit', 'label' => 'Blog posts', 'description' => 'Maximum blog posts. Zero disables the blog module.', 'max' => 100000, 'fallback_key' => 'posts_limit'],
    'news_posts_limit' => ['type' => 'limit', 'label' => 'News articles', 'description' => 'Maximum news articles. Zero disables the news module.', 'max' => 100000, 'fallback_key' => 'posts_limit'],
    'events_limit' => ['type' => 'limit', 'label' => 'Events', 'description' => 'Maximum event entries. Zero disables the events module.', 'max' => 100000, 'fallback_key' => 'posts_limit'],
    'careers_limit' => ['type' => 'limit', 'label' => 'Careers', 'description' => 'Maximum job openings. Zero disables the careers module.', 'max' => 100000, 'fallback_key' => 'posts_limit'],
    'storage_mb' => ['type' => 'limit', 'label' => 'Storage (MB)', 'description' => 'Media storage allowance in megabytes.', 'max' => 1048576],
    'version_history_limit' => ['type' => 'limit', 'label' => 'Version history', 'description' => 'Maximum retained website versions.', 'max' => 1000],
    'team_members_limit' => ['type' => 'limit', 'label' => 'Team members', 'description' => 'Maximum tenant members.', 'max' => 10000],
    'custom_domain_enabled' => ['type' => 'boolean', 'label' => 'Custom domains', 'description' => 'Allows custom-domain eligibility.'],
    'remove_branding_enabled' => ['type' => 'boolean', 'label' => 'Remove Ezyweb branding', 'description' => 'Allows platform branding to be removed.'],
    'advanced_seo_enabled' => ['type' => 'boolean', 'label' => 'Advanced SEO', 'description' => 'Allows advanced SEO capabilities.'],
];
