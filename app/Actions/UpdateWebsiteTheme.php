<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\Website;

class UpdateWebsiteTheme
{
    public function __construct(private readonly DraftState $drafts) {}

    /** @param array<string, string> $theme */
    public function execute(Website $website, array $theme): void
    {
        $website->update(['theme_settings' => $theme]);
        $this->drafts->touch($website);
    }
}
