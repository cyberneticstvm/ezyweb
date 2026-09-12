<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Subscriptions\FeatureGate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(BuilderWorkspace $workspace, FeatureGate $features): Response
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $subscription = $features->subscription($website);
        $currentPlan = $subscription?->plan;

        return Inertia::render('subscription/index', [
            'subscription' => $subscription && $currentPlan instanceof Plan ? [
                ...$subscription->only('public_id', 'status', 'starts_at', 'trial_ends_at', 'current_period_starts_at', 'current_period_ends_at', 'grace_ends_at', 'auto_renew'),
                'plan' => $this->plan($currentPlan),
                'payments' => $subscription->payments()->limit(20)->get()->map->only('public_id', 'amount', 'currency', 'status', 'transaction_reference', 'paid_at', 'created_at'),
            ] : null,
            'plans' => Plan::query()->with('features')->where('is_active', true)->orderBy('sort_order')->get()->map(fn (Plan $plan): array => $this->plan($plan)),
        ]);
    }

    /** @return array<string, mixed> */
    private function plan(Plan $plan): array
    {
        return [...$plan->only('public_id', 'code', 'name', 'description', 'annual_price', 'currency', 'trial_days', 'grace_days', 'is_featured'), 'features' => $plan->features->map(fn (PlanFeature $feature): array => ['key' => $feature->key, 'value' => $feature->value, 'label' => $feature->label])];
    }
}
