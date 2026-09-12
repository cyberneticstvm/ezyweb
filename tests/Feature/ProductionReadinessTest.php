<?php

use App\Monitoring\ProductionConfiguration;
use Illuminate\Support\Facades\Cache;

it('adds correlation and browser security headers without leaking an invalid upstream request id', function () {
    config()->set('security.csp_report_only', false);

    $response = $this->withHeader('X-Request-ID', "unsafe\r\nvalue")->get('/login');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

    expect($response->headers->get('X-Request-ID'))
        ->toMatch('/^[0-9a-f-]{36}$/')
        ->and($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toMatch("/script-src 'self' 'nonce-[a-f0-9]{32}'/")
        ->and($response->getContent())
        ->toMatch('/<script nonce="[a-f0-9]{32}" data-page="app" type="application\/json">/');
});

it('preserves a valid upstream correlation id', function () {
    $this->withHeader('X-Request-ID', 'edge-request-12345')
        ->get('/login')
        ->assertHeader('X-Request-ID', 'edge-request-12345');
});

it('reports database and cache readiness without exposing connection details', function () {
    Cache::put('existing-application-value', 'preserved', 60);

    $this->getJson('/up/ready')
        ->assertOk()
        ->assertJsonPath('status', 'ready')
        ->assertJsonPath('checks.database.ok', true)
        ->assertJsonPath('checks.cache.ok', true)
        ->assertJsonMissing(['password'])
        ->assertJsonMissing(['host']);

    expect(Cache::get('existing-application-value'))->toBe('preserved');
});

it('fails the production validator closed when secure services are not configured', function () {
    expect(app(ProductionConfiguration::class)->isReady())->toBeFalse();

    $this->artisan('app:production-check')->assertFailed();
});

it('passes production validation when every required control is configured', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set([
        'app.debug' => false,
        'app.url' => 'https://ezyweb.in',
        'app.key' => 'base64:test-production-key',
        'cache.default' => 'redis',
        'queue.default' => 'redis',
        'session.driver' => 'redis',
        'session.secure' => true,
        'session.encrypt' => true,
        'mail.default' => 'smtp',
        'services.turnstile.secret_key' => 'configured',
        'media.disk' => 'gcs',
        'filesystems.disks.gcs.project_id' => 'ezyweb-production',
        'filesystems.disks.gcs.bucket' => 'ezyweb-media',
        'security.csp_report_only' => false,
    ]);

    expect(app(ProductionConfiguration::class)->isReady())->toBeTrue();
    $this->artisan('app:production-check')->assertSuccessful();
});

it('renders skip links and semantic main targets on the marketing interface', function () {
    $layout = file_get_contents(resource_path('js/layouts/public-layout.tsx'));

    expect($layout)
        ->toContain('Skip to main content')
        ->toContain('id="marketing-main"');
});
