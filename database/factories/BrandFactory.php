<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Brand> */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->company();

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'name' => $name, 'alt_text' => $name.' logo', 'is_active' => true, 'display_order' => 0];
    }
}
