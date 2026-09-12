<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Builder\SectionRegistry;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\PageSection;
use App\Tenancy\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePageSection
{
    public function __construct(private readonly SectionRegistry $registry, private readonly DraftState $drafts) {}

    /** @param array<string, mixed> $data */
    public function execute(PageSection $section, array $data): PageSection
    {
        if (! in_array($data['variant'], $this->registry->variants($section->type), true)) {
            throw ValidationException::withMessages(['variant' => 'This variant is not supported by the selected section.']);
        }

        if ($data['anchor'] && PageSection::query()->where('page_id', $section->page_id)->where('anchor', $data['anchor'])->whereKeyNot($section->getKey())->exists()) {
            throw ValidationException::withMessages(['anchor' => 'This section anchor is already in use on the page.']);
        }

        $content = $this->registry->normalizeContent($section->type, $data['content']);
        [$mediaIds, $usageField] = $this->validateMedia($section, $content);

        return DB::transaction(function () use ($section, $data, $content, $mediaIds, $usageField): PageSection {
            $section->update([
                'variant' => $data['variant'],
                'anchor' => $data['anchor'] ?: null,
                'content' => $content,
                'design_settings' => $this->registry->normalizeDesign($data['design_settings']),
                'is_enabled' => $data['is_enabled'],
                'show_on_desktop' => $data['show_on_desktop'],
                'show_on_tablet' => $data['show_on_tablet'],
                'show_on_mobile' => $data['show_on_mobile'],
            ]);
            if ($usageField !== null) {
                MediaUsage::query()->where('usable_type', PageSection::class)->where('usable_id', $section->getKey())->where('field', $usageField)->delete();
                $assets = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $section->website_id)->whereIn('public_id', $mediaIds)->get();
                foreach ($assets as $asset) {
                    MediaUsage::query()->create(['media_asset_id' => $asset->getKey(), 'usable_type' => PageSection::class, 'usable_id' => $section->getKey(), 'field' => $usageField]);
                }
            }
            $this->drafts->touch($section->website);

            return $section;
        });
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array{list<string>, ?string}
     */
    private function validateMedia(PageSection $section, array $content): array
    {
        if ($section->type === 'hero') {
            $ids = collect([$content['image_id'] ?? null, $content['mobile_image_id'] ?? null]);
            $usageField = 'hero_images';
            $errorKey = 'content.image_id';
        } elseif ($section->type === 'cta') {
            $ids = collect([$content['image_id'] ?? null, $content['mobile_image_id'] ?? null]);
            $usageField = 'cta_images';
            $errorKey = 'content.image_id';
        } elseif ($section->type === 'features') {
            $items = is_array($content['feature_items'] ?? null) ? $content['feature_items'] : [];
            $ids = collect($items)->map(fn (mixed $item): mixed => is_array($item) ? ($item['image_id'] ?? null) : null);
            $usageField = 'feature_images';
            $errorKey = 'content.feature_items';
        } elseif ($section->type === 'process') {
            $items = is_array($content['process_items'] ?? null) ? $content['process_items'] : [];
            $ids = collect($items)->map(fn (mixed $item): mixed => is_array($item) ? ($item['image_id'] ?? null) : null);
            $usageField = 'process_images';
            $errorKey = 'content.process_items';
        } elseif ($section->type === 'about') {
            $ids = collect([$content['image_id'] ?? null, $content['mobile_image_id'] ?? null]);
            $usageField = 'about_images';
            $errorKey = 'content.image_id';
        } elseif ($section->type === 'mission-vision') {
            $ids = collect([
                $content['story_image_id'] ?? null, $content['story_mobile_image_id'] ?? null,
                $content['mission_image_id'] ?? null, $content['mission_mobile_image_id'] ?? null,
                $content['vision_image_id'] ?? null, $content['vision_mobile_image_id'] ?? null,
            ]);
            $usageField = 'mission_vision_images';
            $errorKey = 'content.story_image_id';
        } elseif ($section->type === 'brand-carousel') {
            $logos = is_array($content['logos'] ?? null) ? $content['logos'] : [];
            $ids = collect($logos)->map(fn (mixed $logo): mixed => is_array($logo) ? ($logo['image_id'] ?? null) : null);
            $usageField = 'brand_carousel_logos';
            $errorKey = 'content.logos';
        } elseif ($section->type === 'hero-slider') {
            $slides = is_array($content['slides'] ?? null) ? $content['slides'] : [];
            $ids = collect($slides)->flatMap(fn (mixed $slide): array => is_array($slide) ? [$slide['image_id'] ?? null, $slide['mobile_image_id'] ?? null] : []);
            $usageField = 'hero_slider';
            $errorKey = 'content.slides';
        } elseif ($section->type === 'rich-text') {
            $ids = collect([$content['image_id'] ?? null]);
            $usageField = 'rich_text_image';
            $errorKey = 'content.image_id';
        } else {
            return [[], null];
        }
        $ids = $ids->filter(fn (mixed $id): bool => is_string($id) && $id !== '')->unique()->values();
        $available = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $section->website_id)->whereIn('public_id', $ids)->count();
        if ($available !== $ids->count()) {
            throw ValidationException::withMessages([$errorKey => 'The selected image is not available in this website media library.']);
        }

        return [$ids->all(), $usageField];
    }
}
