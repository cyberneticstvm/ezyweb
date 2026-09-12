<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderPageSections
{
    public function __construct(private readonly DraftState $drafts) {}

    /** @param list<int> $sectionIds */
    public function execute(Page $page, array $sectionIds): void
    {
        $current = PageSection::query()->where('page_id', $page->getKey())->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $submitted = array_map('intval', $sectionIds);
        $sortedSubmitted = $submitted;
        sort($current);
        sort($sortedSubmitted);
        if ($current !== $sortedSubmitted) {
            throw ValidationException::withMessages(['section_ids' => 'The section order must contain every section on this page exactly once.']);
        }

        DB::transaction(function () use ($page, $submitted): void {
            foreach ($submitted as $order => $sectionId) {
                PageSection::query()->where('page_id', $page->getKey())->whereKey($sectionId)->update(['sort_order' => $order]);
            }
            $this->drafts->touch($page->website);
        });
    }
}
