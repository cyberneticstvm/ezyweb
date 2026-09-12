<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Content\ContentModuleLimit;
use App\Content\ContentModuleManager;
use App\Content\ContentModuleRegistry;
use App\Http\Requests\UpsertContentModuleRequest;
use App\Models\MediaAsset;
use App\Tenancy\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentModuleController extends Controller
{
    public function index(string $module, Request $request, BuilderWorkspace $workspace, ContentModuleRegistry $registry, ContentModuleManager $manager, ContentModuleLimit $limits): Response
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $definition = $registry->get($module);
        $entries = $manager->paginate($module, $website, $request->string('search')->trim()->value() ?: null);

        return Inertia::render('content-modules/index', [
            'module' => [
                'key' => $module,
                'label' => $definition['label'],
                'singular' => $definition['singular'],
                'fields' => $definition['fields'],
                'limit' => $limits->limit($module, $website),
            ],
            'entries' => $entries->through(fn ($entry): array => $manager->present($module, $entry)),
            'filters' => ['search' => $request->string('search')->value()],
            'mediaAssets' => MediaAsset::query()->withoutGlobalScope(TenantScope::class)
                ->where('website_id', $website->getKey())->latest()->limit(100)->get()
                ->map(fn (MediaAsset $asset): array => [
                    'public_id' => $asset->public_id,
                    'title' => $asset->title ?: $asset->original_name,
                    'alt_text' => $asset->alt_text,
                    'url' => $asset->variants['large']['url'] ?? $asset->variants['original']['url'] ?? route('media.show', ['media' => $asset->public_id, 'variant' => 'original'], absolute: false),
                    'thumbnail_url' => $asset->variants['thumbnail']['url'] ?? route('media.show', ['media' => $asset->public_id, 'variant' => 'thumbnail'], absolute: false),
                ])->values(),
            'mediaUploadUrl' => route('media.store', absolute: false),
        ]);
    }

    public function store(string $module, UpsertContentModuleRequest $request, BuilderWorkspace $workspace, ContentModuleManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->create($module, $website, $request->validated());

        return back()->with('success', 'Content entry created.');
    }

    public function update(string $module, int $entry, UpsertContentModuleRequest $request, BuilderWorkspace $workspace, ContentModuleManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->update($module, $website, $entry, $request->validated());

        return back()->with('success', 'Content entry updated.');
    }

    public function destroy(string $module, int $entry, BuilderWorkspace $workspace, ContentModuleManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->delete($module, $website, $entry);

        return back()->with('success', 'Content entry removed.');
    }
}
