<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'name', 'slug', 'short_description', 'full_description', 'featured_image', 'gallery', 'price', 'show_price', 'enquiry_label', 'is_featured', 'is_active', 'display_order', 'seo_title', 'seo_description'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['gallery' => 'array', 'price' => 'decimal:2', 'show_price' => 'boolean', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }
}
