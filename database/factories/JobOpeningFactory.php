<?php

namespace Database\Factories;

use App\Models\JobOpening;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobOpening> */
class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    public function definition(): array
    {
        $title = fake()->jobTitle();

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999), 'department' => 'Operations', 'location' => fake()->city(), 'employment_type' => 'full-time', 'workplace_type' => 'on-site', 'summary' => fake()->sentence(), 'application_email' => fake()->safeEmail(), 'is_active' => true, 'display_order' => 0];
    }
}
