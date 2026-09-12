<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'website_id', 'name', 'slug'])]
class ContentCategory extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return HasMany<ContentPost, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(ContentPost::class);
    }
}
