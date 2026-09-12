<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['tenant_id', 'website_id', 'name', 'slug'])]
class ContentTag extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return BelongsToMany<ContentPost, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(ContentPost::class, 'content_post_tag');
    }
}
