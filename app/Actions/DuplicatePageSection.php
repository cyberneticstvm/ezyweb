<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

class DuplicatePageSection
{
    public function __construct(private readonly DraftState $drafts) {}

    public function execute(PageSection $section): PageSection
    {
        return DB::transaction(function () use ($section): PageSection {
            PageSection::query()->where('page_id', $section->page_id)->where('sort_order', '>', $section->sort_order)->increment('sort_order');
            $copy = $section->replicate();
            $copy->anchor = $this->copyAnchor($section);
            $copy->sort_order = $section->sort_order + 1;
            $copy->save();
            $this->drafts->touch($section->website);

            return $copy;
        });
    }

    private function copyAnchor(PageSection $section): ?string
    {
        if (! $section->anchor) {
            return null;
        }

        $base = $section->anchor.'-copy';
        $anchor = $base;
        $suffix = 2;
        while (PageSection::query()->where('page_id', $section->page_id)->where('anchor', $anchor)->exists()) {
            $anchor = $base.'-'.$suffix++;
        }

        return $anchor;
    }
}
