<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Builder\SectionRegistry;
use App\Models\PageSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteTemplateSnapshot;
use Illuminate\Support\Facades\DB;

class SwitchWebsiteTemplate
{
    public function __construct(private readonly SectionRegistry $sections, private readonly DraftState $drafts) {}

    public function execute(Website $website, Template $template, User $actor): void
    {
        DB::transaction(function () use ($website, $template, $actor): void {
            $locked = Website::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($website->getKey());

            WebsiteTemplateSnapshot::query()->withoutGlobalScopes()->create([
                'tenant_id' => $locked->tenant_id,
                'website_id' => $locked->getKey(),
                'created_by' => $actor->getKey(),
                'from_template_key' => $locked->template_key,
                'to_template_key' => $template->key,
                'theme_settings' => $locked->theme_settings,
                'initial_sections' => $locked->initial_sections,
                'created_at' => now(),
            ]);

            /** @var array<string, string> $defaults */
            $defaults = $template->default_theme;
            /** @var array<string, string> $current */
            $current = $locked->theme_settings;

            /** @var array<string, string> $variantMap */
            $variantMap = $template->variant_map;
            foreach ($variantMap as $type => $variant) {
                if ($this->sections->has($type) && in_array($variant, $this->sections->variants($type), true)) {
                    PageSection::query()->withoutGlobalScopes()
                        ->where('website_id', $locked->getKey())
                        ->where('type', $type)
                        ->update(['variant' => $variant]);
                }
            }

            $locked->update([
                'template_key' => $template->key,
                'theme_settings' => array_replace($defaults, $current),
            ]);
            $this->drafts->touch($locked);
        });
    }
}
