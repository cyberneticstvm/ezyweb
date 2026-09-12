<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'title', 'slug', 'navigation_label', 'is_home', 'is_enabled', 'sort_order'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** @return HasMany<PageSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<PageSeoSetting, $this> */
    public function seoSetting(): HasOne
    {
        return $this->hasOne(PageSeoSetting::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_home' => 'boolean', 'is_enabled' => 'boolean'];
    }
}
