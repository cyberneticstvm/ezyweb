<?php

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use Inertia\Testing\AssertableInertia as Assert;

it('shows real platform metrics and customers to super administrators', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    $owner = User::factory()->create(['name' => 'Customer Owner', 'email' => 'owner@example.com']);
    $tenant = Tenant::factory()->create(['name' => 'Example Customer']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant,
        'user_id' => $owner,
        'role' => MembershipRole::Owner,
    ]);
    Website::factory()->create(['tenant_id' => $tenant]);

    $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/index')
        ->where('metrics.customers', 1)
        ->where('metrics.websites', 1));

    $this->actingAs($admin)->get('/admin/customers?search=owner@example.com')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/customers')
        ->has('customers.data', 1)
        ->where('customers.data.0.name', 'Example Customer')
        ->where('customers.data.0.owner.email', 'owner@example.com'));
});

it('allows a super administrator to suspend and reactivate a customer with an audit log', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);
    $tenant = Tenant::factory()->create();

    $this->actingAs($admin)->put("/admin/customers/{$tenant->id}/status", ['status' => 'suspended'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended)
        ->and(AuditLog::query()->where('action', 'tenant.status_updated')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('stores platform settings and applies the registration switch', function () {
    $admin = User::factory()->create(['platform_role' => PlatformRole::SuperAdmin]);

    $this->actingAs($admin)->put('/admin/settings', [
        'platform_name' => 'Ezyweb',
        'support_email' => 'support@ezyweb.in',
        'registrations_enabled' => false,
        'default_country_code' => 'IN',
        'default_timezone' => 'Asia/Kolkata',
        'default_locale' => 'en',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(PlatformSetting::current()->registrations_enabled)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'platform.settings_updated')->exists())->toBeTrue();

    auth()->logout();
    $this->get('/register')->assertRedirect('/login');
});

it('protects customer and platform settings from regular users', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->actingAs($user)->get('/admin/customers')->assertForbidden();
    $this->actingAs($user)->put("/admin/customers/{$tenant->id}/status", ['status' => 'suspended'])->assertForbidden();
    $this->actingAs($user)->get('/admin/settings')->assertForbidden();
});
