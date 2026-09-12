<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'website_id', 'created_by', 'from_template_key', 'to_template_key', 'theme_settings', 'initial_sections', 'created_at'])]
class WebsiteTemplateSnapshot extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'theme_settings' => 'array',
            'initial_sections' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
