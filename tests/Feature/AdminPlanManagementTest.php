<?php

use App\Enums\PlatformRole;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Subscriptions\SubscriptionManager;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(PlanSeeder::class);
});

it('protects plan management from non super administrators', function () {
    $user = User::factory()->create();
    $starter = Plan::query()->where('code', 'starter')->firstOrFail();

    $this->actingAs($user)->get('/admin/plans')->assertForbidden();
    $this->actingAs($user)->put('/admin/plans/'.$starter->public_id, adminPlanPayload($starter))->assertForbidden();
});

it('shows every plan and controlled feature definition to super administrators', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);

    $this->actingAs($admin)->get('/admin/plans')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/plans')
        ->has('plans', 2)
        ->has('featureDefinitions', 14)
        ->where('plans.0.code', 'starter'));
});

it('updates pricing lifecycle limits entitlements and featured status with an audit log', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    $starter = Plan::query()->where('code', 'starter')->firstOrFail();
    $payload = adminPlanPayload($starter, [
        'name' => 'Starter Plus',
        'annual_price' => '6499.00',
        'currency' => 'usd',
        'trial_days' => 21,
        'grace_days' => 10,
        'retention_days' => 120,
        'is_featured' => true,
        'features' => ['pages_limit' => 12, 'events_limit' => 25, 'custom_domain_enabled' => true],
    ]);

    $this->actingAs($admin)->put('/admin/plans/'.$starter->public_id, $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $starter->refresh()->load('features');
    expect($starter->name)->toBe('Starter Plus')
        ->and($starter->annual_price)->toBe('6499.00')
        ->and($starter->currency)->toBe('USD')
        ->and($starter->trial_days)->toBe(21)
        ->and($starter->features->firstWhere('key', 'pages_limit')?->value)->toBe(12)
        ->and($starter->features->firstWhere('key', 'events_limit')?->value)->toBe(25)
        ->and($starter->features->firstWhere('key', 'custom_domain_enabled')?->value)->toBeTrue()
        ->and(Plan::query()->where('code', 'business')->value('is_featured'))->toBeFalse();

    $audit = AuditLog::query()->where('action', 'plan.updated')->firstOrFail();
    expect($audit->actor_id)->toBe($admin->getKey())
        ->and($audit->auditable_id)->toBe($starter->getKey())
        ->and($audit->before['features']['pages_limit'])->toBe(5)
        ->and($audit->after['features']['pages_limit'])->toBe(12);
});

it('validates controlled feature keys and values', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    $starter = Plan::query()->where('code', 'starter')->firstOrFail();
    $payload = adminPlanPayload($starter, ['features' => ['pages_limit' => -1, 'unknown_feature' => true]]);

    $this->actingAs($admin)->put('/admin/plans/'.$starter->public_id, $payload)
        ->assertSessionHasErrors(['features', 'features.pages_limit']);
});

it('keeps at least one plan available and uses the next active plan for onboarding trials', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    $starter = Plan::query()->where('code', 'starter')->firstOrFail();
    $business = Plan::query()->where('code', 'business')->firstOrFail();

    $this->actingAs($admin)->put('/admin/plans/'.$starter->public_id, adminPlanPayload($starter, ['is_active' => false]))
        ->assertSessionHasNoErrors();

    $tenant = Tenant::factory()->create();
    $subscription = app(SubscriptionManager::class)->startTrial($tenant, $admin);
    expect($subscription?->plan_id)->toBe($business->getKey());

    $this->actingAs($admin)->put('/admin/plans/'.$business->public_id, adminPlanPayload($business, ['is_active' => false]))
        ->assertSessionHasErrors('is_active');
    expect($business->fresh()->is_active)->toBeTrue();
});

/** @param array<string, mixed> $overrides */
function adminPlanPayload(Plan $plan, array $overrides = []): array
{
    $plan->load('features');
    $features = collect(config('plan-features'))->mapWithKeys(fn (array $definition, string $key): array => [
        $key => $plan->features->firstWhere('key', $key)?->value ?? ($definition['type'] === 'boolean' ? false : 0),
    ])->all();

    return [
        'name' => $overrides['name'] ?? $plan->name,
        'description' => $overrides['description'] ?? $plan->description,
        'annual_price' => $overrides['annual_price'] ?? $plan->annual_price,
        'currency' => $overrides['currency'] ?? $plan->currency,
        'trial_days' => $overrides['trial_days'] ?? $plan->trial_days,
        'grace_days' => $overrides['grace_days'] ?? $plan->grace_days,
        'retention_days' => $overrides['retention_days'] ?? $plan->retention_days,
        'sort_order' => $overrides['sort_order'] ?? $plan->sort_order,
        'is_active' => $overrides['is_active'] ?? $plan->is_active,
        'is_featured' => $overrides['is_featured'] ?? $plan->is_featured,
        'features' => [...$features, ...(is_array($overrides['features'] ?? null) ? $overrides['features'] : [])],
    ];
}
