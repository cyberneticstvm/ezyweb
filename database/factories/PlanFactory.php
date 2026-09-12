<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return ['public_id' => (string) Str::ulid(), 'code' => Str::slug($name), 'name' => Str::headline($name), 'annual_price' => 4999, 'currency' => 'INR', 'trial_days' => 14, 'grace_days' => 7, 'retention_days' => 90, 'is_active' => true, 'sort_order' => 0];
    }
}
