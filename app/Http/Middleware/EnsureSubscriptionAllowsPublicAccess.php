<?php

namespace App\Http\Middleware;

use App\Enums\WebsiteStatus;
use App\Models\Website;
use App\Subscriptions\FeatureGate;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAllowsPublicAccess
{
    public function __construct(private readonly CurrentTenant $context, private readonly FeatureGate $features) {}

    public function handle(Request $request, Closure $next): Response
    {
        $website = $this->context->website();
        if (! $website instanceof Website) return $next($request);
        $subscription = $this->features->subscription($website);
        if ($subscription && ! $subscription->status->allowsPublicAccess()) {
            return response()->view('tenant.errors.subscription', ['website' => $website], 402);
        }
        abort_if($website->getAttribute('status') === WebsiteStatus::Suspended, 403);

        return $next($request);
    }
}
