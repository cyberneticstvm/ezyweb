<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Media\MediaLibrary;
use App\Models\MediaAsset;
use App\Models\User;
use App\Subscriptions\FeatureGate;
use App\Tenancy\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request, BuilderWorkspace $workspace, FeatureGate $features): Response
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $query = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->withCount('usages')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(fn ($nested) => $nested->where('original_name', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%')->orWhere('alt_text', 'like', '%'.$search.'%'));
            })
            ->when($request->string('folder')->trim()->value(), fn ($query, string $folder) => $query->where('folder', $folder));
        $assets = $query->latest()->paginate(24)->withQueryString();
        $folders = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->whereNotNull('folder')->distinct()->orderBy('folder')->pluck('folder');
        $used = (int) MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->sum('total_size');

        return Inertia::render('media/index', [
            'assets' => $assets, 'folders' => $folders, 'filters' => $request->only(['search', 'folder']),
            'storage' => ['used' => $used, 'limit' => $features->limit($website, 'storage_mb', (int) config('media.storage_limit_mb', 1024)) * 1024 * 1024],
            'maxUploadKb' => (int) config('media.max_upload_kb', 10240),
        ]);
    }

    public function store(StoreMediaRequest $request, BuilderWorkspace $workspace, MediaLibrary $library): RedirectResponse|JsonResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        /** @var User $user */
        $user = $request->user();
        $asset = $library->store($website, $user, $request->file('image'), $request->safe()->except('image'));

        if ($request->expectsJson()) {
            return response()->json([
                'asset' => [
                    'public_id' => $asset->public_id,
                    'title' => $asset->title ?: $asset->original_name,
                    'alt_text' => $asset->alt_text,
                    'url' => $asset->variants['large']['url'] ?? $asset->variants['original']['url'] ?? route('media.show', ['media' => $asset->public_id, 'variant' => 'original'], absolute: false),
                    'thumbnail_url' => $asset->variants['thumbnail']['url'] ?? route('media.show', ['media' => $asset->public_id, 'variant' => 'thumbnail'], absolute: false),
                ],
            ], 201);
        }

        return back()->with('success', 'Image uploaded and optimized.');
    }

    public function update(string $media, UpdateMediaRequest $request, BuilderWorkspace $workspace, MediaLibrary $library): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $asset = $library->find($website, $media);
        $asset->update(collect($request->validated())->map(fn (mixed $value) => is_string($value) ? trim(strip_tags($value)) ?: null : $value)->all());

        return back()->with('success', 'Image details updated.');
    }

    public function destroy(string $media, Request $request, BuilderWorkspace $workspace, MediaLibrary $library): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $library->delete($library->find($website, $media), $request->boolean('confirmed_usage'));

        return back()->with('success', 'Image deleted.');
    }
}
