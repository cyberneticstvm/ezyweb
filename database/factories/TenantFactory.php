<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 99999),
            'business_category' => fake()->randomElement(['agency', 'clinic', 'consulting', 'education']),
            'country_code' => 'IN',
            'timezone' => 'Asia/Kolkata',
            'default_locale' => 'en',
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->numerify('+91 ##########'),
            'status' => TenantStatus::Active,
        ];
    }
}
