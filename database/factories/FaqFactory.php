<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Faq> */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'question' => fake()->sentence().'?', 'answer' => fake()->paragraph(), 'is_active' => true, 'display_order' => 0];
    }
}
