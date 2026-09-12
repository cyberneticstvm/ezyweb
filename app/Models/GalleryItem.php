<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'website_id', 'title', 'image', 'alt_text', 'caption', 'category', 'aspect_ratio', 'is_active', 'display_order'])]
class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
