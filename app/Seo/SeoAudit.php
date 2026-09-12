<?php

namespace App\Seo;

use App\Models\Page;
use App\Models\Redirect;
use App\Models\Website;
use App\Tenancy\TenantScope;

class SeoAudit
{
    public function __construct(private readonly SeoManager $manager) {}

    /** @return array{score: int, issues: list<array{severity: string, label: string, detail: string}>} */
    public function run(Website $website): array
    {
        $issues = [];
        $site = $this->manager->site($website);
        if (blank($site->default_meta_description)) $issues[] = $this->issue('warning', 'Missing default description', 'Add a concise site-wide fallback description.');
        if (blank($site->default_social_image)) $issues[] = $this->issue('notice', 'Missing social image', 'Add a default Open Graph image for shared links.');
        if (! $site->search_engine_visibility) $issues[] = $this->issue('critical', 'Search visibility disabled', 'The published robots rules will block all crawlers.');
        $pages = Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->where('is_enabled', true)->with('seoSetting')->get();
        $titles = [];
        foreach ($pages as $page) {
            $seo = $page->seoSetting ?? $this->manager->page($page);
            $effectiveTitle = trim((string) ($seo->seo_title ?: $page->title));
            if (mb_strlen($effectiveTitle) > 60) $issues[] = $this->issue('warning', 'Long page title', $page->title.' exceeds the recommended preview length.');
            if (blank($seo->meta_description) && blank($site->default_meta_description)) $issues[] = $this->issue('warning', 'Missing page description', $page->title.' has no page or site description.');
            if (! $seo->is_indexed) $issues[] = $this->issue('notice', 'Page excluded from search', $page->title.' is configured as noindex.');
            $titles[mb_strtolower($effectiveTitle)][] = $page->title;
        }
        foreach ($titles as $matchingPages) {
            if (count($matchingPages) > 1) $issues[] = $this->issue('warning', 'Duplicate SEO titles', implode(', ', $matchingPages).' share the same title.');
        }
        $redirects = Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('is_active', true)->get();
        foreach ($redirects as $redirect) {
            if ($redirects->contains(fn (Redirect $candidate): bool => $candidate->source_path === parse_url($redirect->target_path, PHP_URL_PATH))) {
                $issues[] = $this->issue('warning', 'Redirect chain', $redirect->source_path.' points to another redirect.');
            }
        }
        $deduction = collect($issues)->sum(fn (array $issue): int => match ($issue['severity']) { 'critical' => 25, 'warning' => 10, default => 4 });

        return ['score' => max(0, 100 - $deduction), 'issues' => $issues];
    }

    /** @return array{severity: string, label: string, detail: string} */
    private function issue(string $severity, string $label, string $detail): array
    {
        return compact('severity', 'label', 'detail');
    }
}
