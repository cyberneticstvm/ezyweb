<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Subscriptions\FeatureGate;
use App\Models\Plan;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $context = app(CurrentTenant::class);
        $tenant = $context->hasTenant() ? $context->tenant() : null;
        $website = $context->website();
        $subscription = $website ? app(FeatureGate::class)->subscription($website) : null;
        $subscriptionPlan = $subscription?->plan;

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'domain' => config('domains.root'),
            ],
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email', 'phone', 'platform_role'),
            ],
            'tenant' => $tenant?->only('id', 'name', 'slug', 'status'),
            'website' => $website ? [
                ...$website->only('id', 'name', 'type', 'status', 'publication_status', 'template_key', 'draft_revision', 'published_revision', 'published_at'),
                'hostname' => $website->domains->firstWhere('is_primary', true)?->hostname,
                'has_unpublished_changes' => $website->draft_revision !== $website->published_revision,
            ] : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status->value,
                'plan' => $subscriptionPlan instanceof Plan ? $subscriptionPlan->name : null,
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
