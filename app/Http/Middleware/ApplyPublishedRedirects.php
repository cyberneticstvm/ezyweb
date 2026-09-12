<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Models\Website;
use App\Publishing\PublishedWebsiteCache;
use App\Tenancy\CurrentTenant;
use App\Tenancy\TenantScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApplyPublishedRedirects
{
    public function __construct(private readonly CurrentTenant $context, private readonly PublishedWebsiteCache $cache) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $website = $this->context->website();
        if (! $website instanceof Website) return $next($request);
        $snapshot = $this->cache->snapshot($website);
        $seo = is_array($snapshot['seo'] ?? null) ? $snapshot['seo'] : [];
        $redirects = is_array($seo['redirects'] ?? null) ? $seo['redirects'] : [];
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '/') $path = '/';
        $match = collect($redirects)->first(fn (mixed $redirect): bool => is_array($redirect) && ($redirect['source_path'] ?? null) === $path);
        if (! is_array($match) || ! is_string($match['target_path'] ?? null)) return $next($request);
        if (is_string($match['public_id'] ?? null)) {
            Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('public_id', $match['public_id'])
                ->update(['hit_count' => DB::raw('hit_count + 1'), 'last_hit_at' => now()]);
        }

        return redirect()->to($request->getSchemeAndHttpHost().$match['target_path'], (int) ($match['status_code'] ?? 301));
    }
}
