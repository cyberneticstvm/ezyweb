<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'website_id', 'page_id', 'seo_title', 'meta_description', 'canonical_url', 'open_graph_title', 'open_graph_description', 'open_graph_image', 'is_indexed', 'is_followed', 'schema_type'])]
class PageSeoSetting extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_indexed' => 'boolean', 'is_followed' => 'boolean'];
    }
}
