<?php

namespace App\Subscriptions;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Validation\ValidationException;

class FeatureGate
{
    public function subscription(Tenant|Website $owner): ?Subscription
    {
        $tenantId = $owner instanceof Website ? $owner->tenant_id : $owner->getKey();

        return Subscription::query()->with('plan.features')->where('tenant_id', $tenantId)->latest('id')->first();
    }

    public function value(Tenant|Website $owner, string $key, mixed $fallback = null): mixed
    {
        $subscription = $this->subscription($owner);
        $plan = $subscription?->plan;
        if (! $plan instanceof Plan) return $fallback;
        $feature = $plan->features->firstWhere('key', $key);

        return $feature instanceof PlanFeature ? $feature->value : $fallback;
    }

    public function limit(Tenant|Website $owner, string $key, int $fallback = 0): int
    {
        return max(0, (int) $this->value($owner, $key, $fallback));
    }

    public function allows(Tenant|Website $owner, string $key, bool $fallback = false): bool
    {
        return filter_var($this->value($owner, $key, $fallback), FILTER_VALIDATE_BOOL);
    }

    public function ensureWithinLimit(Tenant|Website $owner, string $key, int $current, string $label, int $fallback = 0): void
    {
        $limit = $this->limit($owner, $key, $fallback);
        if ($limit > 0 && $current >= $limit) {
            throw ValidationException::withMessages(['limit' => "Your current plan allows up to {$limit} {$label}."]);
        }
        if ($limit === 0 && $this->subscription($owner) !== null) {
            throw ValidationException::withMessages(['limit' => "{$label} are not included in your current plan."]);
        }
    }
}
