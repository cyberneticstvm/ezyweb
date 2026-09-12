<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdatePage
{
    public function __construct(private readonly DraftState $drafts) {}

    /** @param array{title: string, slug: string, navigation_label?: string|null, is_enabled: bool} $data */
    public function execute(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data): Page {
            $slug = Str::slug($data['slug']) ?: 'page';
            if (in_array($slug, config('public_site.reserved_page_slugs'), true)) {
                throw ValidationException::withMessages(['slug' => 'This page URL is reserved by Ezyweb.']);
            }
            if (Page::query()->where('website_id', $page->website_id)->where('slug', $slug)->whereKeyNot($page->getKey())->exists()) {
                throw ValidationException::withMessages(['slug' => 'This page URL is already in use.']);
            }

            $page->update([
                'title' => trim($data['title']),
                'slug' => $slug,
                'navigation_label' => trim(($data['navigation_label'] ?? null) ?: $data['title']),
                'is_enabled' => $page->is_home ? true : $data['is_enabled'],
            ]);
            $this->drafts->touch($page->website);

            return $page;
        });
    }
}
