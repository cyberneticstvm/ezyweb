<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Publishing\ContentModuleSnapshot;
use App\Publishing\PublishedWebsiteCache;
use App\Seo\SeoPresenter;
use App\Tenancy\CurrentTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PublicContentController extends Controller
{
    public function archive(string $contentType, Request $request, CurrentTenant $context, PublishedWebsiteCache $cache, ContentModuleSnapshot $modules, SeoPresenter $seo): View
    {
        $website = $this->website($context);
        $snapshot = $cache->snapshot($website);
        $items = $this->filter($modules->publicRecords($snapshot, $contentType), $request);
        $page = max(1, $request->integer('page', 1));
        $perPage = 10;
        $paginator = new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $items->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('tenant.content.archive', [
            'website' => $website,
            'contentType' => $contentType,
            'title' => $this->label($contentType),
            'items' => $paginator,
            'filters' => $request->only(['search', 'category', 'tag']),
            'seo' => $seo->archive($website, $snapshot, $this->label($contentType), $request),
        ]);
    }

    public function show(string $contentType, string $slug, Request $request, CurrentTenant $context, PublishedWebsiteCache $cache, ContentModuleSnapshot $modules, SeoPresenter $seo): View|\Illuminate\Http\Response
    {
        $website = $this->website($context);
        $snapshot = $cache->snapshot($website);
        $records = $modules->publicRecords($snapshot, $contentType);
        $item = $records->first(fn (array $candidate): bool => ($candidate['slug'] ?? null) === $slug);
        if (! is_array($item)) {
            return response()->view('tenant.errors.404', ['website' => $website, 'seo' => $seo->notFound($website, $snapshot, $request)], 404);
        }
        $itemCategory = Arr::get($item, 'category');
        $related = $records->filter(fn (array $candidate): bool => ($candidate['slug'] ?? null) !== $slug
            && (blank($itemCategory) || Arr::get($candidate, 'category') === $itemCategory))->take(3)->values();

        return view('tenant.content.show', [
            'website' => $website,
            'contentType' => $contentType,
            'item' => $item,
            'related' => $related,
            'seo' => $seo->content($website, $snapshot, $contentType, $item, $request),
        ]);
    }

    private function website(CurrentTenant $context): Website
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);

        return $website;
    }

    /**
     * @param  Collection<int, array<mixed, mixed>>  $items
     * @return Collection<int, array<mixed, mixed>>
     */
    private function filter(Collection $items, Request $request): Collection
    {
        $search = mb_strtolower($request->string('search')->trim()->value());
        $category = mb_strtolower($request->string('category')->trim()->value());
        $tag = mb_strtolower($request->string('tag')->trim()->value());

        return $items->filter(function (array $item) use ($search, $category, $tag): bool {
            $haystack = mb_strtolower((string) ($item['title'] ?? '').' '.(string) ($item['excerpt'] ?? $item['summary'] ?? ''));
            $categoryMatches = $category === '' || mb_strtolower((string) ($item['category'] ?? '')) === $category;
            $tags = collect(is_array($item['tags'] ?? null) ? $item['tags'] : [])->map(fn (mixed $value): string => mb_strtolower((string) $value));

            return ($search === '' || str_contains($haystack, $search)) && $categoryMatches && ($tag === '' || $tags->contains($tag));
        })->values();
    }

    private function label(string $type): string
    {
        return match ($type) {
            'blog' => 'Blog', 'news' => 'News', 'events' => 'Events', 'careers' => 'Careers', default => abort(404),
        };
    }
}
