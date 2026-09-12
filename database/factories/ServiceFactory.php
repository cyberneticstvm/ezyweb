<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'title' => Str::headline($title), 'slug' => Str::slug($title), 'short_description' => fake()->sentence(), 'full_description' => fake()->paragraph(), 'is_active' => true, 'is_featured' => false, 'display_order' => 0];
    }
}
