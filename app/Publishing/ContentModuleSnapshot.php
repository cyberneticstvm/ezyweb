<?php

namespace App\Publishing;

use App\Enums\ContentPostStatus;
use App\Models\Brand;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\ContentTag;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\JobOpening;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\Website;
use App\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContentModuleSnapshot
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'services' => Service::class, 'products' => Product::class, 'brands' => Brand::class,
        'testimonials' => Testimonial::class, 'portfolio' => PortfolioProject::class,
        'gallery' => GalleryItem::class, 'team' => TeamMember::class, 'faq' => Faq::class,
        'posts' => ContentPost::class, 'careers' => JobOpening::class,
    ];

    /** @return array<string, list<array<string, mixed>>> */
    public function capture(Website $website): array
    {
        $snapshot = [];
        foreach (self::MODELS as $key => $model) {
            $query = $model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey());
            if ($key === 'posts') {
                $query->with(['category', 'tags']);
            } elseif ($key === 'portfolio') {
                $query->with('category');
            }
            $snapshot[$key] = $query->orderBy('display_order')->orderBy('id')->get()
                ->map(fn (Model $entry): array => $this->serializeEntry($entry))->values()->all();
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, list<array<string, mixed>>>
     */
    public function publicCollections(array $snapshot): array
    {
        $modules = is_array($snapshot['content_modules'] ?? null) ? $snapshot['content_modules'] : [];
        $posts = collect(is_array($modules['posts'] ?? null) ? $modules['posts'] : []);
        $map = [
            'services' => $this->active($modules, 'services'),
            'products' => $this->active($modules, 'products'),
            'brand-carousel' => $this->active($modules, 'brands'),
            'testimonials' => $this->active($modules, 'testimonials'),
            'portfolio' => $this->active($modules, 'portfolio'),
            'gallery' => $this->active($modules, 'gallery'),
            'team' => $this->active($modules, 'team'),
            'faq' => $this->active($modules, 'faq'),
            'blog-posts' => $this->publishedPosts($posts, 'blog'),
            'news' => $this->publishedPosts($posts, 'news'),
            'events' => $this->publishedPosts($posts, 'event'),
            'careers' => $this->active($modules, 'careers')->filter(fn (array $item): bool => blank($item['closing_date'] ?? null) || now()->startOfDay()->lte($item['closing_date']))->values(),
        ];

        return collect($map)->map(fn (Collection $items): array => $items->map(fn (array $item): array => $this->card($item))->values()->all())->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return Collection<int, array<mixed, mixed>>
     */
    public function publicRecords(array $snapshot, string $type): Collection
    {
        $modules = is_array($snapshot['content_modules'] ?? null) ? $snapshot['content_modules'] : [];
        if ($type === 'careers') {
            return $this->active($modules, 'careers')->filter(fn (array $item): bool => blank($item['closing_date'] ?? null) || now()->startOfDay()->lte($item['closing_date']))->values();
        }

        return $this->publishedPosts(
            collect(is_array($modules['posts'] ?? null) ? $modules['posts'] : []),
            $type === 'events' ? 'event' : $type,
        );
    }

    /** @param array<string, mixed> $modules */
    public function restore(Website $website, array $modules): void
    {
        foreach (array_reverse(self::MODELS, true) as $model) {
            $model::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->forceDelete();
        }
        PortfolioCategory::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->delete();
        ContentCategory::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->delete();
        ContentTag::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->delete();

        foreach (self::MODELS as $key => $model) {
            foreach (is_array($modules[$key] ?? null) ? $modules[$key] : [] as $record) {
                if (! is_array($record)) {
                    continue;
                }
                $category = Arr::pull($record, 'category');
                $tags = Arr::pull($record, 'tags', []);
                $entry = $model::query()->withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
                    ...Arr::only($record, (new $model)->getFillable()),
                ]);
                if ($entry instanceof PortfolioProject && is_string($category) && $category !== '') {
                    $categoryModel = PortfolioCategory::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                        'website_id' => $website->getKey(), 'slug' => Str::slug($category),
                    ], ['tenant_id' => $website->tenant_id, 'name' => $category]);
                    $entry->update(['portfolio_category_id' => $categoryModel->getKey()]);
                }
                if ($entry instanceof ContentPost) {
                    if (is_string($category) && $category !== '') {
                        $categoryModel = ContentCategory::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                            'website_id' => $website->getKey(), 'slug' => Str::slug($category),
                        ], ['tenant_id' => $website->tenant_id, 'name' => $category]);
                        $entry->update(['content_category_id' => $categoryModel->getKey()]);
                    }
                    $ids = collect(is_array($tags) ? $tags : [])->filter(fn (mixed $tag): bool => is_string($tag) && $tag !== '')->map(fn (string $tag): int => (int) ContentTag::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
                        'website_id' => $website->getKey(), 'slug' => Str::slug($tag),
                    ], ['tenant_id' => $website->tenant_id, 'name' => $tag])->getKey());
                    $entry->tags()->sync($ids);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $modules
     * @return Collection<int, array<mixed, mixed>>
     */
    private function active(array $modules, string $key): Collection
    {
        return collect(is_array($modules[$key] ?? null) ? $modules[$key] : [])->filter(fn (mixed $item): bool => is_array($item) && ($item['is_active'] ?? true) === true)->values();
    }

    /**
     * @param  Collection<int, mixed>  $posts
     * @return Collection<int, array<mixed, mixed>>
     */
    private function publishedPosts(Collection $posts, string $type): Collection
    {
        return $posts->filter(fn (mixed $item): bool => is_array($item)
            && ($item['content_type'] ?? null) === $type
            && in_array($item['status'] ?? null, [ContentPostStatus::Published->value, ContentPostStatus::Scheduled->value], true)
            && filled($item['published_at'] ?? null)
            && now()->gte($item['published_at']))
            ->map(fn (array $item): array => $this->normalizeArray($item))->values();
    }

    /**
     * @param  array<mixed, mixed>  $item
     * @return array<string, mixed>
     */
    private function card(array $item): array
    {
        return [
            'title' => (string) ($item['title'] ?? $item['name'] ?? $item['client_name'] ?? $item['question'] ?? 'Item'),
            'description' => (string) ($item['short_description'] ?? $item['excerpt'] ?? $item['quote'] ?? $item['answer'] ?? $item['bio'] ?? $item['summary'] ?? $item['caption'] ?? ''),
            'image' => $item['featured_image'] ?? $item['image'] ?? $item['logo'] ?? $item['photo'] ?? null,
            'alt' => (string) ($item['alt_text'] ?? $item['title'] ?? $item['name'] ?? ''),
            'meta' => (string) ($item['category'] ?? $item['role'] ?? $item['client_role'] ?? $item['department'] ?? $item['venue'] ?? ''),
            'url' => $this->itemUrl($item),
            'featured' => (bool) ($item['is_featured'] ?? false),
            'slug' => (string) ($item['slug'] ?? ''),
            'price' => is_numeric($item['price'] ?? null) ? (string) $item['price'] : null,
            'show_price' => (bool) ($item['show_price'] ?? false),
            'enquiry_label' => (string) ($item['enquiry_label'] ?? ''),
            'client_role' => (string) ($item['client_role'] ?? ''),
            'company' => (string) ($item['company'] ?? ''),
            'rating' => is_numeric($item['rating'] ?? null) ? (int) $item['rating'] : null,
            'client' => (string) ($item['client'] ?? ''),
            'project_date' => (string) ($item['project_date'] ?? ''),
            'location' => (string) ($item['location'] ?? ''),
            'aspect_ratio' => in_array($item['aspect_ratio'] ?? null, ['auto', 'square', 'portrait', 'landscape'], true) ? $item['aspect_ratio'] : 'auto',
            'team_key' => (string) ($item['email'] ?? $item['name'] ?? ''),
            'email' => (string) ($item['email'] ?? ''),
            'phone' => (string) ($item['phone'] ?? ''),
            'linkedin_url' => (string) ($item['linkedin_url'] ?? ''),
            'faq_key' => (string) ($item['question'] ?? ''),
            'author_name' => (string) ($item['author_name'] ?? ''),
            'published_at' => (string) ($item['published_at'] ?? ''),
            'tags' => is_array($item['tags'] ?? null) ? array_values($item['tags']) : [],
            'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags((string) ($item['body'] ?? ''))) / 200)),
            'event_start_date' => (string) ($item['event_start_date'] ?? ''),
            'event_start_time' => (string) ($item['event_start_time'] ?? ''),
            'event_end_date' => (string) ($item['event_end_date'] ?? ''),
            'event_end_time' => (string) ($item['event_end_time'] ?? ''),
            'venue' => (string) ($item['venue'] ?? ''),
            'address' => (string) ($item['address'] ?? ''),
            'map_url' => (string) ($item['map_url'] ?? ''),
            'registration_url' => (string) ($item['registration_url'] ?? ''),
            'online_event_url' => (string) ($item['online_event_url'] ?? ''),
            'department' => (string) ($item['department'] ?? ''),
            'employment_type' => (string) ($item['employment_type'] ?? ''),
            'workplace_type' => (string) ($item['workplace_type'] ?? ''),
            'salary_display' => (string) ($item['salary_display'] ?? ''),
            'closing_date' => (string) ($item['closing_date'] ?? ''),
            'application_email' => (string) ($item['application_email'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeEntry(Model $entry): array
    {
        $data = Arr::except($entry->attributesToArray(), ['id', 'tenant_id', 'website_id', 'content_category_id', 'portfolio_category_id', 'deleted_at']);
        if ($entry instanceof ContentPost) {
            $data['category'] = $entry->category?->name;
            $data['tags'] = $entry->tags->pluck('name')->values()->all();
        } elseif ($entry instanceof PortfolioProject) {
            $data['category'] = $entry->category?->name;
        }

        return $data;
    }

    /**
     * @param  array<mixed, mixed>  $item
     * @return array<mixed, mixed>
     */
    private function normalizeArray(array $item): array
    {
        return $item;
    }

    /** @param array<mixed, mixed> $item */
    private function itemUrl(array $item): ?string
    {
        if (is_string($item['slug'] ?? null) && is_string($item['content_type'] ?? null)) {
            $prefix = $item['content_type'] === 'event' ? 'events' : $item['content_type'];

            return '/'.$prefix.'/'.$item['slug'];
        }
        if (is_string($item['slug'] ?? null) && array_key_exists('application_email', $item)) {
            return '/careers/'.$item['slug'];
        }

        return $item['cta_url'] ?? $item['project_url'] ?? $item['registration_url'] ?? null;
    }
}
