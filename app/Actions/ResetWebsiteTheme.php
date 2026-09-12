<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\Template;
use App\Models\Website;

class ResetWebsiteTheme
{
    public function __construct(private readonly DraftState $drafts) {}

    public function execute(Website $website, Template $template): void
    {
        $website->update(['theme_settings' => $template->default_theme]);
        $this->drafts->touch($website);
    }
}
