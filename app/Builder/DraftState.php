<?php

namespace App\Builder;

use App\Enums\PublicationStatus;
use App\Models\Website;

class DraftState
{
    public function touch(Website $website): void
    {
        Website::query()->withoutGlobalScopes()->whereKey($website->getKey())->increment('draft_revision', 1, [
            'draft_updated_at' => now(),
            'publication_status' => PublicationStatus::Saved->value,
            'last_publish_error' => null,
        ]);
    }
}
