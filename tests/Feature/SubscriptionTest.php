<?php

use App\Actions\CreatePage;
use App\Actions\PublishWebsite;
use App\Enums\PlatformRole;
use App\Enums\SubscriptionStatus;
use App\Enums\WebsiteStatus;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionReminder;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Notifications\SubscriptionExpiryReminder;
use App\Subscriptions\FeatureGate;
use App\Subscriptions\SubscriptionLifecycle;
use App\Subscriptions\SubscriptionManager;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed([TemplateSeeder::class, PlanSeeder::class]);
    Queue::fake();
});

it('seeds configurable annual plans and assigns an idempotent starter trial', function () {
    [$user, $tenant] = phaseTenWorkspace(false);
    $subscription = app(SubscriptionManager::class)->startTrial($tenant, $user);

    expect(Plan::query()->count())->toBe(2)
        ->and(Plan::query()->where('code', 'starter')->firstOrFail()->features)->not->toBeEmpty()
        ->and($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->trial_ends_at)->not->toBeNull()
        ->and(app(SubscriptionManager::class)->startTrial($tenant, $user))->toBeNull()
        ->and($tenant->subscriptions()->count())->toBe(1);
});

it('enforces database backed plan limits and preserves legacy fallbacks without a subscription', function () {
    [$user, $tenant, $website] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'starter');
    $subscription->plan->features()->where('key', 'pages_limit')->update(['value' => 1]);
    $subscription->plan->features()->where('key', 'services_limit')->update(['value' => 0]);
    $features = app(FeatureGate::class);

    expect($features->limit($website, 'pages_limit'))->toBe(1)
        ->and($features->allows($website, 'custom_domain_enabled'))->toBeFalse();
    expect(fn () => app(CreatePage::class)->execute($website, ['title' => 'Blocked page']))
        ->toThrow(ValidationException::class, 'up to 1 pages');
    $this->actingAs($user)->post('/dashboard/content/services', ['title' => 'Blocked service', 'slug' => 'blocked-service', 'is_active' => true])
        ->assertSessionHasErrors('limit');

    [$legacyUser, , $legacyWebsite] = phaseTenWorkspace();
    config()->set('content-modules.limits.services', 2);
    $this->actingAs($legacyUser)->post('/dashboard/content/services', ['title' => 'Allowed service', 'slug' => 'allowed-service', 'is_active' => true])->assertRedirect();
    expect(app(FeatureGate::class)->subscription($legacyWebsite))->toBeNull();
});

it('controls events independently from blog news and careers', function () {
    [$user, $tenant] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'starter');

    $this->actingAs($user)->post('/dashboard/content/events', [
        'title' => 'Blocked event', 'slug' => 'blocked-event', 'status' => 'draft', 'event_start_date' => now()->addWeek()->toDateString(),
    ])->assertSessionHasErrors('limit');

    $subscription->plan->features()->where('key', 'events_limit')->update(['value' => 2]);
    $this->actingAs($user)->post('/dashboard/content/events', [
        'title' => 'Available event', 'slug' => 'available-event', 'status' => 'draft', 'event_start_date' => now()->addWeek()->toDateString(),
    ])->assertRedirect()->assertSessionHasNoErrors();
});

it('shows only the current tenant subscription and plan catalogue', function () {
    [$user, $tenant] = phaseTenWorkspace();
    phaseTenSubscription($tenant, 'starter');
    [, $foreignTenant] = phaseTenWorkspace();
    phaseTenSubscription($foreignTenant, 'business');

    $this->actingAs($user)->get('/dashboard/subscription')->assertOk()->assertInertia(fn ($page) => $page
        ->component('subscription/index')->where('subscription.plan.code', 'starter')->has('plans', 2));
});

it('sends scheduled expiry reminders once at every configured checkpoint', function () {
    Notification::fake();
    [$user, $tenant] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'business', ['current_period_ends_at' => now()->addDays(7)]);

    expect(app(SubscriptionLifecycle::class)->reminders())->toBe(1)
        ->and(app(SubscriptionLifecycle::class)->reminders())->toBe(0)
        ->and(SubscriptionReminder::query()->where('subscription_id', $subscription->getKey())->count())->toBe(1);
    Notification::assertSentTo($user, SubscriptionExpiryReminder::class, fn ($notification): bool => $notification->days === 7);
});

it('moves expired subscriptions through grace then suspension without deleting websites', function () {
    [$user, $tenant, $website, $hostname] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'starter', ['status' => SubscriptionStatus::Active, 'current_period_ends_at' => now()->subMinute()]);
    app(PublishWebsite::class)->execute($website, $user);

    $result = app(SubscriptionLifecycle::class)->process();
    expect($result['transitioned'])->toBe(1)->and($subscription->fresh()->status)->toBe(SubscriptionStatus::GracePeriod)
        ->and($website->fresh()->status)->toBe(WebsiteStatus::Published);
    $this->get('http://'.$hostname.'/')->assertOk();

    $subscription->refresh()->update(['grace_ends_at' => now()->subMinute()]);
    app(SubscriptionLifecycle::class)->process();
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($website->fresh()->status)->toBe(WebsiteStatus::Suspended)
        ->and(Website::query()->withoutGlobalScopes()->whereKey($website->getKey())->exists())->toBeTrue();
    $this->get('http://'.$hostname.'/')->assertStatus(402)->assertSee('Website temporarily unavailable')->assertDontSee('Phase 10 website');
});

it('lets super admins change plans extend suspend and reactivate with complete audit logs', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    [$owner, $tenant, $website] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'starter');
    $business = Plan::query()->where('code', 'business')->firstOrFail();

    $this->actingAs($owner)->get('/admin/subscriptions')->assertForbidden();
    $this->actingAs($admin)->get('/admin/subscriptions')->assertOk()->assertInertia(fn ($page) => $page->component('admin/subscriptions')->has('subscriptions.data', 1));
    $this->actingAs($admin)->put('/admin/subscriptions/'.$subscription->public_id.'/plan', ['plan_id' => $business->getKey()])->assertRedirect();
    $this->actingAs($admin)->post('/admin/subscriptions/'.$subscription->public_id.'/extend', ['days' => 365])->assertRedirect();
    $this->actingAs($admin)->put('/admin/subscriptions/'.$subscription->public_id.'/status', ['status' => 'suspended'])->assertRedirect();
    expect($subscription->fresh()->plan_id)->toBe($business->getKey())->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended)
        ->and($website->fresh()->status)->toBe(WebsiteStatus::Suspended);
    $this->actingAs($admin)->put('/admin/subscriptions/'.$subscription->public_id.'/status', ['status' => 'active'])->assertRedirect();
    expect($website->fresh()->status)->toBe(WebsiteStatus::Draft)
        ->and(AuditLog::query()->where('tenant_id', $tenant->getKey())->count())->toBe(4);
});

it('records annual payments atomically and renews the subscription', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    [, $tenant] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'business', ['status' => SubscriptionStatus::PastDue, 'current_period_ends_at' => now()->subDay()]);

    $this->actingAs($admin)->post('/admin/subscriptions/'.$subscription->public_id.'/payments', [
        'amount' => '11999.00', 'currency' => 'inr', 'transaction_reference' => 'EZY-PAY-1001', 'paid_at' => now()->toDateString(), 'extend_days' => 365,
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect(SubscriptionPayment::query()->where('subscription_id', $subscription->getKey())->firstOrFail()->status->value)->toBe('paid')
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->current_period_ends_at->isFuture())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'subscription.payment_recorded')->exists())->toBeTrue();
});

it('prunes version history to the current plan limit while preserving the live version', function () {
    [$user, $tenant, $website] = phaseTenWorkspace();
    $subscription = phaseTenSubscription($tenant, 'starter');
    $subscription->plan->features()->where('key', 'version_history_limit')->update(['value' => 2]);
    $section = PageSection::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->firstOrFail();
    foreach (range(1, 3) as $number) {
        $section->update(['content' => ['heading' => 'Version '.$number]]);
        $website->increment('draft_revision');
        app(PublishWebsite::class)->execute($website->fresh(), $user);
    }
    expect($website->versions()->count())->toBe(2)
        ->and($website->versions()->whereKey($website->fresh()->published_version_id)->exists())->toBeTrue();
});

/** @return array{User, Tenant, Website, string} */
function phaseTenWorkspace(bool $withWebsite = true): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey(), 'name' => 'Phase 10 website']);
    $hostname = 'subscription-'.$website->getKey().'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create(['hostname' => $hostname, 'subdomain' => 'subscription-'.$website->getKey(), 'is_primary' => true]);
    $page = Page::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'is_home' => true, 'slug' => 'home']);
    PageSection::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'page_id' => $page->getKey(), 'type' => 'hero', 'content' => ['heading' => 'Phase 10 website']]);

    return [$user, $tenant, $website, $hostname];
}

/** @param array<string, mixed> $overrides */
function phaseTenSubscription(Tenant $tenant, string $plan, array $overrides = []): Subscription
{
    $now = now();
    return Subscription::query()->create([...[
        'public_id' => (string) Str::ulid(), 'tenant_id' => $tenant->getKey(), 'plan_id' => Plan::query()->where('code', $plan)->value('id'),
        'status' => SubscriptionStatus::Active, 'starts_at' => $now, 'current_period_starts_at' => $now, 'current_period_ends_at' => $now->copy()->addYear(),
    ], ...$overrides]);
}
