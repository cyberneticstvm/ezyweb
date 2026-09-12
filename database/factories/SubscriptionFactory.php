<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(), 'tenant_id' => Tenant::factory(), 'plan_id' => Plan::query()->value('id'),
            'status' => SubscriptionStatus::Active, 'starts_at' => now(), 'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addYear(),
        ];
    }
}
