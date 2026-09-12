<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'title', 'slug', 'short_description', 'full_description', 'icon', 'featured_image', 'cta_label', 'cta_url', 'is_featured', 'is_active', 'display_order', 'seo_title', 'seo_description'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_active' => 'boolean'];
    }
}
