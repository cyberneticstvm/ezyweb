<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\PortfolioProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'portfolio_category_id', 'title', 'slug', 'client', 'project_date', 'location', 'short_description', 'case_study', 'featured_image', 'gallery', 'project_url', 'is_featured', 'is_active', 'display_order', 'seo_title', 'seo_description'])]
class PortfolioProject extends Model
{
    /** @use HasFactory<PortfolioProjectFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    /** @return BelongsTo<PortfolioCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PortfolioCategory::class, 'portfolio_category_id');
    }

    protected function casts(): array
    {
        return ['project_date' => 'date', 'gallery' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }
}
