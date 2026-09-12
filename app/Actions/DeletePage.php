<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePage
{
    public function __construct(private readonly DraftState $drafts) {}

    public function execute(Page $page): void
    {
        if ($page->is_home) {
            throw ValidationException::withMessages(['page' => 'The home page cannot be deleted.']);
        }

        DB::transaction(function () use ($page): void {
            $page->sections()->delete();
            $page->delete();
            $this->drafts->touch($page->website);
        });
    }
}
