<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVersion;
use App\PublicSite\PublicSiteRenderer;
use App\Publishing\PreviewUrlFactory;
use App\Publishing\WebsiteSnapshotFactory;
use Illuminate\Contracts\View\View;

class WebsitePreviewController extends Controller
{
    public function draft(
        int $website,
        WebsiteSnapshotFactory $snapshots,
        PreviewUrlFactory $urls,
        PublicSiteRenderer $renderer,
        ?string $pageSlug = null,
    ): View {
        $websiteModel = $this->website($website);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $websiteModel);

        return $renderer->renderSnapshot($websiteModel, $snapshots->capture($websiteModel), $pageSlug, [
            'preview' => true,
            'label' => 'Draft preview',
            'url_for_page' => fn (?string $slug): string => $urls->draft($websiteModel, $slug),
        ]);
    }

    public function version(
        int $website,
        int $version,
        PreviewUrlFactory $urls,
        PublicSiteRenderer $renderer,
        ?string $pageSlug = null,
    ): View {
        $websiteModel = $this->website($website);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('view', $websiteModel);
        $versionModel = WebsiteVersion::query()->withoutGlobalScopes()
            ->where('website_id', $websiteModel->getKey())->findOrFail($version);

        return $renderer->renderSnapshot($websiteModel, $versionModel->snapshot(), $pageSlug, [
            'preview' => true,
            'label' => 'Version '.$versionModel->version_number.' preview',
            'url_for_page' => fn (?string $slug): string => $urls->version($websiteModel, $versionModel, $slug),
        ]);
    }

    private function website(int $id): Website
    {
        return Website::query()->withoutGlobalScopes()->findOrFail($id);
    }
}
