<?php

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Enums\PlatformRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromMembership
{
    public function __construct(private readonly CurrentTenant $context) {}

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $this->context->clear();

        $membership = TenantMembership::query()
            ->withoutGlobalScopes()
            ->with(['tenant.websites.domains'])
            ->where('user_id', $request->user()?->getKey())
            ->where('status', MembershipStatus::Active->value)
            ->oldest('id')
            ->first();

        if (! $membership) {
            if ($request->user()?->getAttribute('platform_role') === PlatformRole::SuperAdmin) {
                return redirect()->route('admin.index');
            }

            return redirect()->route('onboarding.create');
        }

        $tenant = $membership->tenant;
        abort_unless($tenant->getAttribute('status') === TenantStatus::Active, 403);

        $website = $tenant->websites->first();
        $this->context->set($tenant, $website);
        app()->instance(Tenant::class, $tenant);
        if ($website instanceof Website) {
            app()->instance(Website::class, $website);
        }

        return $next($request);
    }
}
