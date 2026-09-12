<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'website_id', 'name', 'slug', 'display_order'])]
class PortfolioCategory extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return HasMany<PortfolioProject, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class);
    }
}
