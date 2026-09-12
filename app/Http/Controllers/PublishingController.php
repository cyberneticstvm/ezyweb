<?php

namespace App\Http\Controllers;

use App\Actions\PublishWebsite;
use App\Actions\RestoreWebsiteVersion;
use App\Builder\BuilderWorkspace;
use App\Models\User;
use App\Models\WebsiteVersion;
use App\Publishing\PreviewUrlFactory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublishingController extends Controller
{
    public function index(BuilderWorkspace $workspace, PreviewUrlFactory $urls): Response
    {
        $website = $workspace->website();
        /** @var User $user */
        $user = request()->user();
        $this->authorize('view', $website);
        $versions = WebsiteVersion::query()->withoutGlobalScopes()->with('creator')
            ->where('website_id', $website->getKey())->orderByDesc('version_number')->limit(30)->get();

        return Inertia::render('publishing/index', [
            'publishingWebsite' => [
                ...$website->only('id', 'name', 'status', 'publication_status', 'draft_revision', 'published_revision', 'published_version_id', 'published_at', 'last_publish_error'),
                'has_unpublished_changes' => $website->draft_revision !== $website->published_revision,
            ],
            'previewUrl' => $urls->draft($website),
            'versions' => $versions->map(fn (WebsiteVersion $version): array => [
                ...$version->only('id', 'version_number', 'reason', 'published_at', 'created_at'),
                'creator' => $version->creator?->name,
                'is_current' => (int) $website->published_version_id === (int) $version->getKey(),
                'preview_url' => $urls->version($website, $version),
            ]),
        ]);
    }

    public function publish(BuilderWorkspace $workspace, PublishWebsite $action): RedirectResponse
    {
        $website = $workspace->website();
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $website);
        $version = $action->execute($website, $user);

        return back()->with('success', 'Version '.$version->version_number.' is published.');
    }

    public function restore(int $version, BuilderWorkspace $workspace, RestoreWebsiteVersion $action): RedirectResponse
    {
        $website = $workspace->website();
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $website);
        $source = WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->findOrFail($version);
        $restored = $action->execute($website, $source, $user);

        return back()->with('success', 'Version '.$source->version_number.' restored to draft as version '.$restored->version_number.'.');
    }
}
