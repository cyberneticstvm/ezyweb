<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Template> */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'suitable_categories' => ['agency'],
            'preview_desktop_path' => "/images/templates/{$key}-desktop.svg",
            'preview_mobile_path' => "/images/templates/{$key}-mobile.svg",
            'default_theme' => config('templates.catalog.horizon.default_theme'),
            'variant_map' => ['hero' => 'split'],
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
