<?php

namespace App\Content;

use App\Models\Website;
use App\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Subscriptions\FeatureGate;

class ContentModuleLimit
{
    /** @var array<string, string> */
    private const FEATURE_KEYS = [
        'blog' => 'blog_posts_limit',
        'news' => 'news_posts_limit',
        'events' => 'events_limit',
        'careers' => 'careers_limit',
    ];

    public function __construct(private readonly FeatureGate $features) {}

    public function limit(string $module, Website $website): int
    {
        return $this->features->limit($website, $this->featureKey($module), $this->fallback($module, $website));
    }

    /** @param class-string<Model> $model */
    public function ensureAvailable(string $module, string $model, Website $website, ?string $contentType): void
    {
        $query = $model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey());
        if ($contentType) {
            $query->where('content_type', $contentType);
        }
        $this->features->ensureWithinLimit($website, $this->featureKey($module), $query->count(), "{$module} entries", $this->fallback($module, $website));
    }

    private function featureKey(string $module): string
    {
        return self::FEATURE_KEYS[$module] ?? $module.'_limit';
    }

    private function fallback(string $module, Website $website): int
    {
        $configured = (int) config('content-modules.limits.'.$module, 0);

        return isset(self::FEATURE_KEYS[$module])
            ? $this->features->limit($website, 'posts_limit', $configured)
            : $configured;
    }
}
