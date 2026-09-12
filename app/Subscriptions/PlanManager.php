<?php

namespace App\Subscriptions;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanManager
{
    /** @param array<string, mixed> $input */
    public function update(Plan $plan, array $input, User $actor, Request $request): Plan
    {
        return DB::transaction(function () use ($plan, $input, $actor, $request): Plan {
            $locked = Plan::query()->with('features')->lockForUpdate()->findOrFail($plan->getKey());
            $isActive = (bool) $input['is_active'];
            if ($locked->is_active && ! $isActive && ! Plan::query()->whereKeyNot($locked->getKey())->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'At least one subscription plan must remain available.']);
            }

            $before = $this->snapshot($locked);
            $locked->update([
                'name' => $input['name'],
                'description' => $input['description'] ?: null,
                'annual_price' => $input['annual_price'],
                'currency' => Str::upper((string) $input['currency']),
                'trial_days' => $input['trial_days'],
                'grace_days' => $input['grace_days'],
                'retention_days' => $input['retention_days'],
                'sort_order' => $input['sort_order'],
                'is_active' => $isActive,
                'is_featured' => (bool) $input['is_featured'],
            ]);

            if ($locked->is_featured) {
                Plan::query()->whereKeyNot($locked->getKey())->update(['is_featured' => false]);
            }

            $definitions = config('plan-features', []);
            $definitions = is_array($definitions) ? $definitions : [];
            foreach ($input['features'] as $key => $value) {
                $definition = is_array($definitions[$key] ?? null) ? $definitions[$key] : [];
                $locked->features()->updateOrCreate(['key' => $key], [
                    'value' => ($definition['type'] ?? null) === 'boolean' ? (bool) $value : (int) $value,
                    'label' => $definition['label'] ?? Str::headline($key),
                ]);
            }

            $locked->load('features');
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'tenant_id' => null,
                'action' => 'plan.updated',
                'auditable_type' => Plan::class,
                'auditable_id' => $locked->getKey(),
                'before' => $before,
                'after' => $this->snapshot($locked),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);

            return $locked;
        }, 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(Plan $plan): array
    {
        return [
            ...$plan->only(['name', 'description', 'annual_price', 'currency', 'trial_days', 'grace_days', 'retention_days', 'sort_order', 'is_active', 'is_featured']),
            'features' => $plan->features->mapWithKeys(fn (PlanFeature $feature): array => [$feature->key => $feature->value])->all(),
        ];
    }
}
