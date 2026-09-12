<?php

namespace Database\Factories;

use App\Enums\WebsiteStatus;
use App\Enums\WebsiteType;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Website> */
class WebsiteFactory extends Factory
{
    protected $model = Website::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company().' Website',
            'slug' => 'main',
            'type' => WebsiteType::Multipage,
            'status' => WebsiteStatus::Draft,
            'template_key' => 'horizon',
            'theme_settings' => [
                'primary' => '#0f766e',
                'secondary' => '#334155',
                'accent' => '#f59e0b',
            ],
            'initial_sections' => ['hero', 'about', 'services', 'contact'],
            'onboarding_completed_at' => now(),
        ];
    }
}
