<?php

use App\Enums\TenantStatus;
use App\Http\Middleware\ResolveTenantFromDomain;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('automatically scopes tenant-owned queries to the current tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $websiteA = Website::factory()->create(['tenant_id' => $tenantA->getKey()]);
    Website::factory()->create(['tenant_id' => $tenantB->getKey()]);

    app(CurrentTenant::class)->set($tenantA, $websiteA);

    expect(Website::query()->pluck('tenant_id')->all())->toBe([$tenantA->getKey()]);

    app(CurrentTenant::class)->clear();
});

it('prevents members from viewing another tenants website', function () {
    $user = User::factory()->create();
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenantA->getKey(), 'user_id' => $user->getKey()]);
    $websiteB = Website::factory()->create(['tenant_id' => $tenantB->getKey()]);

    expect($user->can('view', $websiteB))->toBeFalse();
});

it('resolves a verified tenant and website from the request hostname', function () {
    $tenant = Tenant::factory()->create();
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey()]);
    WebsiteDomain::factory()->forWebsite($website)->create([
        'hostname' => 'resolved.ezyweb.in',
        'subdomain' => 'resolved',
    ]);
    $request = Request::create('https://resolved.ezyweb.in/example');

    $response = app(ResolveTenantFromDomain::class)->handle(
        $request,
        fn () => response()->json(['tenant' => app(CurrentTenant::class)->tenant()->getKey()]),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain((string) $tenant->getKey())
        ->and(Cache::get('tenant-domain-id:resolved.ezyweb.in'))->toBe($website->domains()->value('id'));

    app(CurrentTenant::class)->clear();

    $repeatResponse = app(ResolveTenantFromDomain::class)->handle(
        Request::create('https://resolved.ezyweb.in/again'),
        fn () => response()->json(['website' => app(CurrentTenant::class)->website()->getKey()]),
    );

    expect($repeatResponse->getStatusCode())->toBe(200)
        ->and($repeatResponse->getContent())->toContain((string) $website->getKey());
});

it('blocks suspended tenants during hostname resolution', function () {
    $tenant = Tenant::factory()->create(['status' => TenantStatus::Suspended]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey()]);
    WebsiteDomain::factory()->forWebsite($website)->create([
        'hostname' => 'suspended.ezyweb.in',
        'subdomain' => 'suspended',
    ]);
    $request = Request::create('https://suspended.ezyweb.in');

    try {
        app(ResolveTenantFromDomain::class)->handle($request, fn () => response('ok'));
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);

        return;
    }

    $this->fail('Suspended tenant resolution did not return HTTP 403.');
});
