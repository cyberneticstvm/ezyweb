<?php

namespace App\Subscriptions;

use App\Models\Website;
use App\Models\WebsiteVersion;

class VersionHistoryLimit
{
    public function __construct(private readonly FeatureGate $features) {}

    public function prune(Website $website): void
    {
        $limit = $this->features->limit($website, 'version_history_limit');
        if ($limit < 1) return;
        $versions = WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->orderByDesc('id')->pluck('id');
        $delete = $versions->slice($limit)->filter(fn (int $id): bool => $id !== (int) $website->published_version_id);
        if ($delete->isNotEmpty()) WebsiteVersion::query()->withoutGlobalScopes()->whereIn('id', $delete)->delete();
    }
}
