<?php

namespace App\PublicSite;

class SectionViewResolver
{
    public function view(string $type): string
    {
        return match ($type) {
            'hero' => 'tenant.sections.hero',
            'hero-slider' => 'tenant.sections.hero-slider',
            'rich-text' => 'tenant.sections.rich-text',
            'image-text' => 'tenant.sections.image-text',
            'video' => 'tenant.sections.video',
            'about' => 'tenant.sections.about',
            'mission-vision' => 'tenant.sections.mission-vision',
            'counters' => 'tenant.sections.counters',
            'statistics' => 'tenant.sections.statistics',
            'brand-carousel' => 'tenant.sections.brand-carousel',
            'logo-cloud' => 'tenant.sections.logo-cloud',
            'products' => 'tenant.sections.products',
            'testimonials' => 'tenant.sections.testimonials',
            'portfolio' => 'tenant.sections.portfolio',
            'gallery' => 'tenant.sections.gallery',
            'team' => 'tenant.sections.team',
            'faq' => 'tenant.sections.faq',
            'blog-posts' => 'tenant.sections.blog-posts',
            'news' => 'tenant.sections.news',
            'events' => 'tenant.sections.events',
            'careers' => 'tenant.sections.careers',
            'cta' => 'tenant.sections.cta',
            'features' => 'tenant.sections.features',
            'process' => 'tenant.sections.process',
            'pricing' => 'tenant.sections.pricing',
            'contact' => 'tenant.sections.contact',
            'map' => 'tenant.sections.map',
            'newsletter' => 'tenant.sections.newsletter',
            'services',
            'spacer-divider' => 'tenant.sections.spacer-divider',
            default => 'tenant.sections.content',
        };
    }
}
