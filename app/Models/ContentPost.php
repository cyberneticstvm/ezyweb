<?php

namespace App\Models;

use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\ContentPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'content_category_id', 'content_type', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'author_name', 'status', 'published_at', 'seo_title', 'seo_description', 'open_graph_image', 'event_start_date', 'event_start_time', 'event_end_date', 'event_end_time', 'venue', 'address', 'map_url', 'registration_url', 'online_event_url', 'display_order'])]
class ContentPost extends Model
{
    /** @use HasFactory<ContentPostFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    /** @return BelongsTo<ContentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    /** @return BelongsToMany<ContentTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContentTag::class, 'content_post_tag');
    }

    protected function casts(): array
    {
        return [
            'content_type' => ContentPostType::class,
            'status' => ContentPostStatus::class,
            'published_at' => 'datetime',
            'event_start_date' => 'date',
            'event_end_date' => 'date',
        ];
    }
}
