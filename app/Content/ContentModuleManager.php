<?php

namespace App\Content;

use App\Builder\DraftState;
use App\Enums\ContentPostStatus;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\ContentTag;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Models\Website;
use App\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentModuleManager
{
    public function __construct(
        private readonly ContentModuleRegistry $registry,
        private readonly ContentModuleLimit $limits,
        private readonly DraftState $drafts,
        private readonly RichTextSanitizer $richText,
    ) {}

    /** @return LengthAwarePaginator<int, Model> */
    public function paginate(string $module, Website $website, ?string $search = null): LengthAwarePaginator
    {
        $definition = $this->registry->get($module);
        $model = $definition['model'];
        $query = $model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey());
        if ($definition['content_type']) {
            $query->where('content_type', $definition['content_type'])->with(['category', 'tags']);
        } elseif ($module === 'portfolio') {
            $query->with('category');
        }
        if ($search) {
            $query->where($definition['titleKey'], 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%');
        }

        return $query->orderBy('display_order')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function present(string $module, Model $entry): array
    {
        $data = $entry->attributesToArray();
        foreach (['gallery'] as $field) {
            if (is_array($entry->getAttribute($field))) {
                $data[$field] = implode("\n", $entry->getAttribute($field));
            }
        }
        if ($entry instanceof ContentPost) {
            $data['category'] = $entry->category?->name;
            $data['tags'] = $entry->tags->pluck('name')->implode(', ');
        }
        if ($entry instanceof PortfolioProject) {
            $data['category'] = $entry->category?->name;
        }

        $definition = $this->registry->get($module);

        return [
            'id' => $entry->getKey(),
            'title' => (string) $entry->getAttribute($definition['titleKey']),
            'description' => Str::limit((string) $entry->getAttribute($definition['descriptionKey']), 150),
            'active' => $this->active($entry),
            'featured' => (bool) ($entry->getAttribute('is_featured') ?? false),
            'data' => Arr::only($data, array_column($definition['fields'], 'key')),
            'updated_at' => $entry->getAttribute('updated_at'),
        ];
    }

    /** @param array<string, mixed> $input */
    public function create(string $module, Website $website, array $input): Model
    {
        $definition = $this->registry->get($module);
        $this->limits->ensureAvailable($module, $definition['model'], $website, $definition['content_type']);

        return DB::transaction(function () use ($module, $website, $input, $definition): Model {
            [$attributes, $category, $tags] = $this->prepare($module, $website, $input, null);
            $model = $definition['model'];
            $entry = $model::query()->withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $website->tenant_id,
                'website_id' => $website->getKey(),
                ...$attributes,
            ]);
            $this->syncRelations($entry, $website, $category, $tags);
            $this->syncMediaUsages($entry, $website, $attributes);
            $this->drafts->touch($website);

            return $entry;
        }, 3);
    }

    /** @param array<string, mixed> $input */
    public function update(string $module, Website $website, int $entryId, array $input): Model
    {
        return DB::transaction(function () use ($module, $website, $entryId, $input): Model {
            $entry = $this->find($module, $website, $entryId);
            [$attributes, $category, $tags] = $this->prepare($module, $website, $input, $entry);
            $entry->update($attributes);
            $this->syncRelations($entry, $website, $category, $tags);
            $this->syncMediaUsages($entry, $website, $attributes);
            $this->drafts->touch($website);

            return $entry;
        }, 3);
    }

    public function delete(string $module, Website $website, int $entryId): void
    {
        DB::transaction(function () use ($module, $website, $entryId): void {
            $entry = $this->find($module, $website, $entryId);
            MediaUsage::query()->where('usable_type', $entry::class)->where('usable_id', $entry->getKey())->delete();
            $entry->delete();
            $this->drafts->touch($website);
        }, 3);
    }

    public function find(string $module, Website $website, int $entryId): Model
    {
        $definition = $this->registry->get($module);
        $model = $definition['model'];
        $query = $model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey());
        if ($definition['content_type']) {
            $query->where('content_type', $definition['content_type']);
        }

        return $query->findOrFail($entryId);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: ?string, 2: ?string}
     */
    private function prepare(string $module, Website $website, array $input, ?Model $entry): array
    {
        $definition = $this->registry->get($module);
        $attributes = Arr::only($input, array_column($definition['fields'], 'key'));
        $category = is_string($attributes['category'] ?? null) ? trim($attributes['category']) : null;
        $tags = is_string($attributes['tags'] ?? null) ? trim($attributes['tags']) : null;
        unset($attributes['category'], $attributes['tags']);

        foreach ($definition['fields'] as $field) {
            $key = $field['key'];
            if ($field['type'] === 'boolean') {
                $attributes[$key] = (bool) ($input[$key] ?? false);
            } elseif ($field['type'] === 'lines' && isset($attributes[$key])) {
                $attributes[$key] = collect(preg_split('/\R/', (string) $attributes[$key]))->map(fn (string $line): string => trim($line))->filter()->values()->all();
            } elseif ($field['type'] === 'richtext' && isset($attributes[$key])) {
                $attributes[$key] = $this->richText->sanitize((string) $attributes[$key]);
            } elseif ($field['type'] === 'textarea' && isset($attributes[$key])) {
                $attributes[$key] = trim(strip_tags((string) $attributes[$key]));
            }
        }

        if (array_key_exists('slug', $attributes) && blank($attributes['slug'])) {
            $attributes['slug'] = $this->uniqueSlug($definition['model'], $website, $definition['content_type'], Str::slug((string) $attributes[$definition['titleKey']]), $entry?->getKey());
        }
        if ($definition['content_type']) {
            $attributes['content_type'] = $definition['content_type'];
            if (($attributes['status'] ?? null) === ContentPostStatus::Published->value && blank($attributes['published_at'] ?? null)) {
                $attributes['published_at'] = now();
            }
            if (($attributes['status'] ?? null) === ContentPostStatus::Draft->value) {
                $attributes['published_at'] = null;
            }
        }

        return [$attributes, $category, $tags];
    }

    private function syncRelations(Model $entry, Website $website, ?string $category, ?string $tags): void
    {
        if ($entry instanceof ContentPost) {
            $categoryModel = $category ? ContentCategory::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                'website_id' => $website->getKey(), 'slug' => Str::slug($category),
            ], ['tenant_id' => $website->tenant_id, 'name' => $category]) : null;
            $entry->update(['content_category_id' => $categoryModel?->getKey()]);
            $tagIds = collect(explode(',', (string) $tags))->map(fn (string $tag): string => trim($tag))->filter()->unique()->take(20)
                ->map(fn (string $tag): int => (int) ContentTag::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                    'website_id' => $website->getKey(), 'slug' => Str::slug($tag),
                ], ['tenant_id' => $website->tenant_id, 'name' => $tag])->getKey());
            $entry->tags()->sync($tagIds);
        }
        if ($entry instanceof PortfolioProject) {
            $categoryModel = $category ? PortfolioCategory::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                'website_id' => $website->getKey(), 'slug' => Str::slug($category),
            ], ['tenant_id' => $website->tenant_id, 'name' => $category]) : null;
            $entry->update(['portfolio_category_id' => $categoryModel?->getKey()]);
        }
    }

    /** @param class-string<Model> $model */
    private function uniqueSlug(string $model, Website $website, ?string $contentType, string $base, mixed $except): string
    {
        $base = $base !== '' ? $base : 'entry';
        $slug = $base;
        $suffix = 2;
        while ($model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())
            ->when($contentType, fn ($query) => $query->where('content_type', $contentType))
            ->when($except, fn ($query) => $query->whereKeyNot($except))->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function active(Model $entry): bool
    {
        if ($entry instanceof ContentPost) {
            $status = $entry->getAttribute('status');
            $publishedAt = $entry->getAttribute('published_at');

            return in_array($status, [ContentPostStatus::Published, ContentPostStatus::Scheduled], true)
                && $publishedAt instanceof Carbon && $publishedAt->isPast();
        }

        return (bool) ($entry->getAttribute('is_active') ?? true);
    }

    /** @param array<string, mixed> $attributes */
    private function syncMediaUsages(Model $entry, Website $website, array $attributes): void
    {
        foreach (['body', 'full_description', 'case_study', 'description', 'responsibilities', 'requirements'] as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }
            MediaUsage::query()->where('usable_type', $entry::class)->where('usable_id', $entry->getKey())->where('field', $field)->delete();
            preg_match_all('~/media/([0-9A-HJKMNP-TV-Z]{26})(?:/[^"\'<>\s]*)?~i', (string) $attributes[$field], $matches);
            $assets = MediaAsset::query()->withoutGlobalScope(TenantScope::class)
                ->where('website_id', $website->getKey())->whereIn('public_id', array_unique($matches[1]))->get();
            foreach ($assets as $asset) {
                MediaUsage::query()->firstOrCreate([
                    'media_asset_id' => $asset->getKey(), 'usable_type' => $entry::class,
                    'usable_id' => $entry->getKey(), 'field' => $field,
                ]);
            }
        }
    }
}
