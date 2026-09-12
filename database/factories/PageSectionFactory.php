<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PageSection> */
class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'website_id' => Website::factory(),
            'page_id' => Page::factory(),
            'type' => 'hero',
            'variant' => 'split',
            'anchor' => 'hero',
            'content' => ['eyebrow' => 'Welcome', 'heading' => fake()->sentence(5), 'description' => fake()->paragraph()],
            'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
            'is_enabled' => true,
            'show_on_desktop' => true,
            'show_on_tablet' => true,
            'show_on_mobile' => true,
            'sort_order' => 0,
        ];
    }
}
