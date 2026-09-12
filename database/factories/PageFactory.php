<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'tenant_id' => Tenant::factory(),
            'website_id' => Website::factory(),
            'title' => Str::headline($title),
            'slug' => Str::slug($title),
            'navigation_label' => Str::headline($title),
            'is_home' => false,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
    }
}
