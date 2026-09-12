<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

class DeletePageSection
{
    public function __construct(private readonly DraftState $drafts) {}

    public function execute(PageSection $section): void
    {
        DB::transaction(function () use ($section): void {
            $section->delete();
            $remaining = PageSection::query()->where('page_id', $section->page_id)->orderBy('sort_order')->orderBy('id')->get();
            foreach ($remaining as $order => $item) {
                $item->updateQuietly(['sort_order' => $order]);
            }
            $this->drafts->touch($section->website);
        });
    }
}
