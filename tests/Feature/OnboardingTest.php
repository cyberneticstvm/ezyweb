<?php

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\Subscription;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(PlanSeeder::class);
});

it('requires authentication for onboarding', function () {
    $this->get('/onboarding')->assertRedirect('/login');
});

it('shows the onboarding wizard to users without a tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/index')
            ->has('options.templates', 3));
});

it('creates the tenant foundation in one onboarding transaction', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', onboardingPayload())
        ->assertRedirect('/dashboard');

    expect(Tenant::query()->count())->toBe(1)
        ->and(TenantMembership::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(Website::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(WebsiteDomain::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(Subscription::query()->count())->toBe(1)
        ->and(Page::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(PageSection::query()->withoutGlobalScopes()->count())->toBe(4);

    $tenant = Tenant::query()->firstOrFail();
    $website = Website::query()->withoutGlobalScopes()->firstOrFail();
    $domain = WebsiteDomain::query()->withoutGlobalScopes()->firstOrFail();

    expect($website->tenant_id)->toBe($tenant->getKey())
        ->and($domain->tenant_id)->toBe($tenant->getKey())
        ->and($domain->website_id)->toBe($website->getKey())
        ->and($domain->hostname)->toBe('acme-studio.ezyweb.in')
        ->and($website->initial_sections)->toBe(['hero', 'about', 'services', 'contact']);

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

it('rejects reserved subdomains without creating partial records', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/onboarding')
        ->post('/onboarding', onboardingPayload(['subdomain' => 'admin']))
        ->assertRedirect('/onboarding')
        ->assertSessionHasErrors('subdomain');

    expect(Tenant::query()->count())->toBe(0)
        ->and(TenantMembership::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(Website::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('reports subdomain availability in real time', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/onboarding/subdomain-availability?subdomain=Fresh-Business')
        ->assertOk()
        ->assertJson([
            'subdomain' => 'fresh-business',
            'hostname' => 'fresh-business.ezyweb.in',
            'available' => true,
        ]);
});

it('rejects an already claimed subdomain', function () {
    $website = Website::factory()->create();
    WebsiteDomain::factory()->forWebsite($website)->create([
        'subdomain' => 'acme-studio',
        'hostname' => 'acme-studio.ezyweb.in',
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding', onboardingPayload())
        ->assertSessionHasErrors('subdomain');
});

/** @param array<string, mixed> $overrides */
function onboardingPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'business_name' => 'Acme Studio',
        'business_category' => 'agency',
        'country_code' => 'IN',
        'timezone' => 'Asia/Kolkata',
        'default_language' => 'en',
        'contact_email' => 'hello@acme.test',
        'contact_phone' => '+91 98765 43210',
        'subdomain' => 'acme-studio',
        'website_type' => 'multipage',
        'template_key' => 'horizon',
        'theme' => [
            'palette' => 'ocean',
            'primary' => '#0f766e',
            'secondary' => '#334155',
            'accent' => '#f59e0b',
            'heading_font' => 'Inter',
            'body_font' => 'Inter',
            'button_radius' => '0.5rem',
            'corner_radius' => '0.75rem',
        ],
        'sections' => ['hero', 'about', 'services', 'contact'],
        'confirmed' => true,
    ], $overrides);
}
