<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'page_id', 'type', 'variant', 'anchor', 'content', 'design_settings', 'is_enabled', 'show_on_desktop', 'show_on_tablet', 'show_on_mobile', 'sort_order'])]
class PageSection extends Model
{
    /** @use HasFactory<PageSectionFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'design_settings' => 'array',
            'is_enabled' => 'boolean',
            'show_on_desktop' => 'boolean',
            'show_on_tablet' => 'boolean',
            'show_on_mobile' => 'boolean',
        ];
    }
}
