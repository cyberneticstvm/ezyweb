<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'name' => Str::headline($name), 'slug' => Str::slug($name), 'short_description' => fake()->sentence(), 'price' => fake()->randomFloat(2, 10, 1000), 'show_price' => true, 'is_active' => true, 'is_featured' => false, 'display_order' => 0];
    }
}
