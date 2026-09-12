<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'website_id', 'name', 'logo', 'website_url', 'alt_text', 'is_active', 'display_order'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
