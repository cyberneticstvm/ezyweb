<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Builder\SectionRegistry;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddPageSection
{
    public function __construct(private readonly SectionRegistry $registry, private readonly DraftState $drafts) {}

    public function execute(Page $page, string $type): PageSection
    {
        return DB::transaction(function () use ($page, $type): PageSection {
            $definition = $this->registry->get($type);
            abort_if($definition === [], 422);
            $order = PageSection::query()->where('page_id', $page->getKey())->max('sort_order');
            $section = PageSection::query()->create([
                'tenant_id' => $page->tenant_id,
                'website_id' => $page->website_id,
                'page_id' => $page->getKey(),
                'type' => $type,
                'variant' => $definition['variants'][0]['key'],
                'anchor' => $this->uniqueAnchor($page, $type),
                'content' => $definition['default_content'],
                'design_settings' => $definition['default_design'],
                'is_enabled' => true,
                'show_on_desktop' => true,
                'show_on_tablet' => true,
                'show_on_mobile' => true,
                'sort_order' => $order === null ? 0 : ((int) $order + 1),
            ]);
            $this->drafts->touch($page->website);

            return $section;
        });
    }

    private function uniqueAnchor(Page $page, string $type): string
    {
        $base = Str::slug($type);
        $anchor = $base;
        $suffix = 2;
        while (PageSection::query()->where('page_id', $page->getKey())->where('anchor', $anchor)->exists()) {
            $anchor = $base.'-'.$suffix++;
        }

        return $anchor;
    }
}
