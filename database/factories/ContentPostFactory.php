<?php

namespace Database\Factories;

use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Models\ContentPost;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ContentPost> */
class ContentPostFactory extends Factory
{
    protected $model = ContentPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return ['tenant_id' => Tenant::factory(), 'website_id' => Website::factory(), 'content_type' => ContentPostType::Blog, 'title' => $title, 'slug' => Str::slug($title), 'excerpt' => fake()->sentence(), 'body' => fake()->paragraphs(3, true), 'author_name' => fake()->name(), 'status' => ContentPostStatus::Draft, 'display_order' => 0];
    }
}
