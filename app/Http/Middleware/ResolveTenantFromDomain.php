<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Enums\WebsiteStatus;
use App\Models\Tenant;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    public function __construct(private readonly CurrentTenant $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();
        $hostname = strtolower($request->getHost());

        abort_if($this->isCentralDomain($hostname), 404);

        $domainId = Cache::remember(
            'tenant-domain-id:'.$hostname,
            config('tenancy.domain_cache_seconds', 3600),
            fn () => WebsiteDomain::query()
                ->withoutGlobalScopes()
                ->where('hostname', $hostname)
                ->where('is_verified', true)
                ->value('id'),
        );

        $domain = is_int($domainId)
            ? WebsiteDomain::query()
                ->withoutGlobalScopes()
                ->with(['tenant', 'website'])
                ->find($domainId)
            : null;

        abort_unless($domain instanceof WebsiteDomain, 404);

        $tenant = $domain->tenant;
        $website = $domain->website;

        abort_unless($tenant instanceof Tenant && $website instanceof Website, 404);
        abort_unless($tenant->getAttribute('status') === TenantStatus::Active, 403);
        $this->context->set($tenant, $website);
        app()->instance(Tenant::class, $tenant);
        app()->instance(Website::class, $website);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('website', $website);

        return $next($request);
    }

    private function isCentralDomain(string $hostname): bool
    {
        return in_array($hostname, array_values(config('domains')), true);
    }
}
