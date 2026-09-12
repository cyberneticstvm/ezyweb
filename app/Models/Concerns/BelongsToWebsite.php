<?php

namespace App\Models\Concerns;

use App\Models\Website;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWebsite
{
    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
