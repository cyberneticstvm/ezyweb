<?php

namespace App\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionReminder;
use App\Models\TenantMembership;
use App\Notifications\SubscriptionExpiryReminder;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycle
{
    public function __construct(private readonly SubscriptionManager $manager) {}

    /** @return array{transitioned: int, reminders: int} */
    public function process(): array
    {
        $transitioned = 0;
        Subscription::query()->with('plan')->whereIn('status', [SubscriptionStatus::Trialing, SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->where('current_period_ends_at', '<=', now())->each(function (Subscription $subscription) use (&$transitioned): void {
                DB::transaction(function () use ($subscription): void {
                    $locked = Subscription::query()->with('plan')->lockForUpdate()->findOrFail($subscription->getKey());
                    if (! in_array($locked->status, [SubscriptionStatus::Trialing, SubscriptionStatus::Active, SubscriptionStatus::PastDue], true) || $locked->current_period_ends_at?->isFuture()) return;
                    $days = max(0, (int) $locked->plan->grace_days);
                    $locked->update($days > 0 ? ['status' => SubscriptionStatus::GracePeriod, 'grace_ends_at' => now()->addDays($days)] : ['status' => SubscriptionStatus::Expired, 'ended_at' => now()]);
                    if ($days === 0) $this->manager->suspendWebsites($locked->tenant_id);
                });
                $transitioned++;
            });
        Subscription::query()->where('status', SubscriptionStatus::GracePeriod)->where('grace_ends_at', '<=', now())->each(function (Subscription $subscription) use (&$transitioned): void {
            $subscription->update(['status' => SubscriptionStatus::Expired, 'ended_at' => now()]);
            $this->manager->suspendWebsites($subscription->tenant_id);
            $transitioned++;
        });

        return ['transitioned' => $transitioned, 'reminders' => $this->reminders()];
    }

    public function reminders(): int
    {
        $sent = 0;
        Subscription::query()->with(['tenant', 'plan'])->whereNotNull('current_period_ends_at')->whereIn('status', [SubscriptionStatus::Trialing, SubscriptionStatus::Active, SubscriptionStatus::GracePeriod, SubscriptionStatus::PastDue])
            ->each(function (Subscription $subscription) use (&$sent): void {
                $days = (int) now()->startOfDay()->diffInDays($subscription->current_period_ends_at->copy()->startOfDay(), false);
                $offset = in_array($days, [30, 15, 7, 1, 0, -7], true) ? $days : null;
                if ($offset === null) return;
                $key = ($offset >= 0 ? 'before_' : 'after_').abs($offset).':'.$subscription->current_period_ends_at->toDateString();
                if (SubscriptionReminder::query()->where('subscription_id', $subscription->getKey())->where('reminder_key', $key)->exists()) return;
                $users = TenantMembership::query()->withoutGlobalScopes()->with('user')->where('tenant_id', $subscription->tenant_id)->where('role', 'tenant_owner')->where('status', 'active')->get()->pluck('user')->filter();
                foreach ($users as $user) $user->notify(new SubscriptionExpiryReminder($subscription, $offset));
                SubscriptionReminder::query()->create(['subscription_id' => $subscription->getKey(), 'reminder_key' => $key, 'sent_at' => now()]);
                $sent++;
            });

        return $sent;
    }
}
