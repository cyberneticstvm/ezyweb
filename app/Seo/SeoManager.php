<?php

namespace App\Seo;

use App\Builder\DraftState;
use App\Models\Page;
use App\Models\PageSeoSetting;
use App\Models\Redirect;
use App\Models\Website;
use App\Models\WebsiteSeoSetting;
use App\Tenancy\TenantScope;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SeoManager
{
    public function __construct(private readonly DraftState $drafts) {}

    public function site(Website $website): WebsiteSeoSetting
    {
        return WebsiteSeoSetting::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['website_id' => $website->getKey()],
            ['tenant_id' => $website->tenant_id, 'site_title' => $website->name, 'business_name' => $website->name, 'not_found_message' => 'The page you requested could not be found.'],
        );
    }

    public function page(Page $page): PageSeoSetting
    {
        return PageSeoSetting::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['page_id' => $page->getKey()],
            ['tenant_id' => $page->tenant_id, 'website_id' => $page->website_id, 'schema_type' => 'WebPage'],
        );
    }

    /** @param array<string, mixed> $data */
    public function updateSite(Website $website, array $data): WebsiteSeoSetting
    {
        $data['social_profiles'] = $this->lines($data['social_profiles_text'] ?? null);
        unset($data['social_profiles_text']);
        $setting = $this->site($website);
        $setting->update($this->plainValues($data));
        $this->drafts->touch($website);

        return $setting;
    }

    /** @param array<string, mixed> $data */
    public function updatePage(Website $website, Page $page, array $data): PageSeoSetting
    {
        abort_unless((int) $page->website_id === (int) $website->getKey(), 404);
        $setting = $this->page($page);
        $setting->update($this->plainValues($data));
        $this->drafts->touch($website);

        return $setting;
    }

    /** @param array<string, mixed> $data */
    public function saveRedirect(Website $website, array $data, ?Redirect $redirect = null): Redirect
    {
        if ($redirect instanceof Redirect) {
            abort_unless((int) $redirect->website_id === (int) $website->getKey(), 404);
        }
        $source = $this->path((string) $data['source_path']);
        $target = $this->path((string) $data['target_path'], true);
        if ($source === $target) {
            throw ValidationException::withMessages(['target_path' => 'A redirect cannot point to itself.']);
        }
        $duplicate = Redirect::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->where('source_path', $source)
            ->when($redirect, fn ($query) => $query->whereKeyNot($redirect->getKey()))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['source_path' => 'A redirect already exists for this source path.']);
        }
        $chainTarget = parse_url($target, PHP_URL_PATH);
        $chainTarget = is_string($chainTarget) ? $chainTarget : $target;
        $loop = Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())
            ->when($redirect, fn ($query) => $query->whereKeyNot($redirect->getKey()))
            ->where('source_path', $chainTarget)->where('target_path', $source)->exists();
        if ($loop) {
            throw ValidationException::withMessages(['target_path' => 'This redirect would create a loop.']);
        }
        $values = [
            'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'source_path' => $source,
            'target_path' => $target, 'status_code' => (int) $data['status_code'], 'is_active' => (bool) $data['is_active'],
        ];
        if ($redirect instanceof Redirect) {
            $redirect->update($values);
        } else {
            $redirect = Redirect::query()->withoutGlobalScope(TenantScope::class)->create($values);
        }
        $this->drafts->touch($website);

        return $redirect;
    }

    public function findRedirect(Website $website, string $publicId): Redirect
    {
        return Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('public_id', $publicId)->firstOrFail();
    }

    public function deleteRedirect(Website $website, Redirect $redirect): void
    {
        abort_unless((int) $redirect->website_id === (int) $website->getKey(), 404);
        $redirect->delete();
        $this->drafts->touch($website);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function plainValues(array $values): array
    {
        return collect($values)->map(fn (mixed $value) => is_string($value) ? (trim(strip_tags($value)) ?: null) : $value)->all();
    }

    /** @return list<string> */
    private function lines(mixed $value): array
    {
        return collect(preg_split('/\R/', is_string($value) ? $value : '') ?: [])->map(fn (string $line): string => trim($line))->filter()->unique()->values()->all();
    }

    private function path(string $path, bool $queryAllowed = false): string
    {
        $path = '/'.ltrim(trim($path), '/');
        if (! $queryAllowed) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?: '/');
        }

        return Str::limit($path, 500, '');
    }
}
