<?php

namespace Database\Factories;

use App\Models\GalleryItem;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GalleryItem> */
class GalleryItemFactory extends Factory
{
    protected $model = GalleryItem::class;

    public function definition(): array
    {
        $title = fake()->words(3, true);

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'title' => $title, 'image' => 'https://images.example.test/'.fake()->uuid().'.jpg', 'alt_text' => $title, 'aspect_ratio' => 'auto', 'is_active' => true, 'display_order' => 0];
    }
}
