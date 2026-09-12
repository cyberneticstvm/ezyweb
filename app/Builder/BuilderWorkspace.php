<?php

namespace App\Builder;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use App\Tenancy\CurrentTenant;

class BuilderWorkspace
{
    public function __construct(private readonly CurrentTenant $context) {}

    public function website(): Website
    {
        $website = $this->context->website();
        abort_unless($website instanceof Website, 404);

        return $website;
    }

    public function page(int|string $id): Page
    {
        $website = $this->website();

        return Page::query()->where('website_id', $website->getKey())->findOrFail($id);
    }

    public function section(int|string $id): PageSection
    {
        $website = $this->website();

        return PageSection::query()->where('website_id', $website->getKey())->findOrFail($id);
    }
}
