<?php

namespace App\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Enums\WebsiteStatus;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionManager
{
    public function startTrial(Tenant $tenant, ?User $actor = null): ?Subscription
    {
        $plan = Plan::query()->where('is_active', true)
            ->orderByRaw("case when code = 'starter' then 0 else 1 end")
            ->orderBy('sort_order')
            ->first();
        if (! $plan || $tenant->subscriptions()->exists()) return null;
        $now = now();

        return Subscription::query()->create([
            'public_id' => (string) Str::ulid(), 'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Trialing, 'starts_at' => $now, 'trial_ends_at' => $now->copy()->addDays($plan->trial_days),
            'current_period_starts_at' => $now, 'current_period_ends_at' => $now->copy()->addDays($plan->trial_days), 'created_by' => $actor?->getKey(),
        ]);
    }

    public function changePlan(Subscription $subscription, Plan $plan, User $actor, Request $request): Subscription
    {
        abort_unless($plan->is_active, 422);

        return $this->mutate($subscription, $actor, $request, 'subscription.plan_changed', function (Subscription $locked) use ($plan): void {
            $locked->update(['plan_id' => $plan->getKey()]);
        });
    }

    public function extend(Subscription $subscription, int $days, User $actor, Request $request): Subscription
    {
        if ($days < 1 || $days > 730) throw ValidationException::withMessages(['days' => 'Extension must be between 1 and 730 days.']);

        return $this->mutate($subscription, $actor, $request, 'subscription.extended', function (Subscription $locked) use ($days): void {
            $base = $locked->current_period_ends_at && $locked->current_period_ends_at->isFuture() ? $locked->current_period_ends_at : now();
            $locked->update([
                'status' => SubscriptionStatus::Active, 'current_period_starts_at' => $locked->current_period_starts_at ?? now(),
                'current_period_ends_at' => $base->copy()->addDays($days), 'trial_ends_at' => null, 'grace_ends_at' => null,
                'ended_at' => null, 'suspended_at' => null, 'cancelled_at' => null,
            ]);
            $this->activateWebsites($locked->tenant_id);
        });
    }

    public function setStatus(Subscription $subscription, SubscriptionStatus $status, User $actor, Request $request): Subscription
    {
        return $this->mutate($subscription, $actor, $request, 'subscription.status_changed', function (Subscription $locked) use ($status): void {
            $updates = ['status' => $status];
            if (in_array($status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
                $updates = [...$updates, 'suspended_at' => null, 'ended_at' => null, 'cancelled_at' => null, 'grace_ends_at' => null];
            }
            if ($status === SubscriptionStatus::Suspended) $updates['suspended_at'] = now();
            if ($status === SubscriptionStatus::Cancelled) $updates['cancelled_at'] = now();
            if ($status === SubscriptionStatus::Expired) $updates['ended_at'] = now();
            $locked->update($updates);
            if ($status->allowsPublicAccess()) $this->activateWebsites($locked->tenant_id);
            else $this->suspendWebsites($locked->tenant_id);
        });
    }

    public function suspendWebsites(int $tenantId): void
    {
        Website::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->update(['status' => WebsiteStatus::Suspended]);
    }

    public function activateWebsites(int $tenantId): void
    {
        Website::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', WebsiteStatus::Suspended)
            ->get()->each(fn (Website $website) => $website->update(['status' => $website->published_version_id ? WebsiteStatus::Published : WebsiteStatus::Draft]));
    }

    /** @param callable(Subscription): void $callback */
    private function mutate(Subscription $subscription, User $actor, Request $request, string $action, callable $callback): Subscription
    {
        return DB::transaction(function () use ($subscription, $actor, $request, $action, $callback): Subscription {
            $locked = Subscription::query()->with('tenant')->lockForUpdate()->findOrFail($subscription->getKey());
            $before = $locked->only(['plan_id', 'status', 'current_period_ends_at', 'grace_ends_at']);
            $callback($locked);
            $locked->refresh();
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(), 'tenant_id' => $locked->tenant_id, 'action' => $action,
                'auditable_type' => Subscription::class, 'auditable_id' => $locked->getKey(), 'before' => $before,
                'after' => $locked->only(['plan_id', 'status', 'current_period_ends_at', 'grace_ends_at']),
                'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);

            return $locked;
        }, 3);
    }
}
