<?php

namespace Database\Factories;

use App\Models\PortfolioProject;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PortfolioProject> */
class PortfolioProjectFactory extends Factory
{
    protected $model = PortfolioProject::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'title' => Str::headline($title), 'slug' => Str::slug($title), 'short_description' => fake()->sentence(), 'case_study' => fake()->paragraph(), 'is_active' => true, 'is_featured' => false, 'display_order' => 0];
    }
}
