<?php

namespace App\Actions;

use App\Enums\PublicationStatus;
use App\Enums\WebsiteStatus;
use App\Jobs\WarmPublishedWebsiteCache;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVersion;
use App\Publishing\PublishedWebsiteCache;
use App\Publishing\PublishValidator;
use App\Publishing\WebsiteSnapshotFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Subscriptions\VersionHistoryLimit;

class PublishWebsite
{
    public function __construct(
        private readonly WebsiteSnapshotFactory $snapshots,
        private readonly PublishValidator $validator,
        private readonly PublishedWebsiteCache $cache,
        private readonly VersionHistoryLimit $history,
    ) {}

    public function execute(Website $website, User $actor): WebsiteVersion
    {
        Website::query()->withoutGlobalScopes()->whereKey($website->getKey())->update([
            'publication_status' => PublicationStatus::Publishing->value,
            'last_publish_error' => null,
        ]);

        try {
            [$version, $previous, $next, $changed] = DB::transaction(function () use ($website, $actor): array {
                $locked = Website::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($website->getKey());
                $existing = $locked->published_version_id
                    ? WebsiteVersion::query()->withoutGlobalScopes()->findOrFail($locked->published_version_id)
                    : null;
                $next = $this->snapshots->capture($locked);

                if ($existing && $locked->published_revision === $locked->draft_revision && $this->publishedStructureMatches($existing->snapshot(), $next)) {
                    $locked->update(['publication_status' => PublicationStatus::Published]);

                    return [$existing, null, $next, false];
                }

                $previous = $existing?->snapshot();
                $this->validator->validate($locked, $next);
                $number = ((int) WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $locked->getKey())->max('version_number')) + 1;
                $publishedAt = now();
                $version = WebsiteVersion::query()->withoutGlobalScopes()->create([
                    'tenant_id' => $locked->tenant_id,
                    'website_id' => $locked->getKey(),
                    'version_number' => $number,
                    'reason' => 'publish',
                    'snapshot_json' => $next,
                    'created_by' => $actor->getKey(),
                    'published_at' => $publishedAt,
                ]);
                $locked->update([
                    'status' => WebsiteStatus::Published,
                    'publication_status' => PublicationStatus::Published,
                    'published_revision' => $locked->draft_revision,
                    'published_version_id' => $version->getKey(),
                    'published_by' => $actor->getKey(),
                    'published_at' => $publishedAt,
                    'last_publish_error' => null,
                ]);

                return [$version, $previous, $next, true];
            }, 3);

            if ($changed) {
                $website->refresh();
                $this->history->prune($website);
                $this->cache->forget($website, $previous, $next);
                WarmPublishedWebsiteCache::dispatch((int) $website->getKey())->afterCommit();
            }

            return $version;
        } catch (Throwable $exception) {
            Website::query()->withoutGlobalScopes()->whereKey($website->getKey())->update([
                'publication_status' => PublicationStatus::Failed->value,
                'last_publish_error' => 'Publishing failed. Review the website and retry.',
            ]);
            Log::error('Website publish failed', [
                'tenant_id' => $website->tenant_id,
                'website_id' => $website->getKey(),
                'user_id' => $actor->getKey(),
                'exception' => $exception::class,
            ]);
            throw $exception;
        }
    }

    /**
     * Compare published page structure after its storage serialization boundary.
     * Other snapshot modules can materialize database defaults on their second
     * capture and must not turn an otherwise unchanged publish into a version.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $captured
     */
    private function publishedStructureMatches(array $stored, array $captured): bool
    {
        return json_encode($stored['pages'] ?? [], JSON_THROW_ON_ERROR)
            === json_encode($captured['pages'] ?? [], JSON_THROW_ON_ERROR);
    }
}
