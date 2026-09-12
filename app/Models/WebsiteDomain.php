<?php

namespace App\Models;

use App\Enums\DomainType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WebsiteDomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'website_id', 'hostname', 'subdomain', 'type', 'is_primary', 'is_verified', 'verified_at'])]
class WebsiteDomain extends Model
{
    /** @use HasFactory<WebsiteDomainFactory> */
    use BelongsToTenant, HasFactory;

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DomainType::class,
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
