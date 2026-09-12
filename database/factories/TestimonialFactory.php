<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Testimonial;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'client_name' => fake()->name(), 'client_role' => fake()->jobTitle(), 'company' => fake()->company(), 'quote' => fake()->paragraph(), 'rating' => 5, 'is_active' => true, 'is_featured' => false, 'display_order' => 0];
    }
}
