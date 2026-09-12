<?php

use App\Models\Template;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteTemplateSnapshot;
use App\Themes\ContrastService;
use Database\Seeders\TemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('seeds the three active template catalog records', function () {
    expect(Template::query()->orderBy('sort_order')->pluck('key')->all())
        ->toBe(['horizon', 'localpro', 'canvas'])
        ->and(file_exists(public_path('images/templates/horizon-desktop.svg')))->toBeTrue()
        ->and(file_exists(public_path('images/templates/localpro-mobile.svg')))->toBeTrue()
        ->and(file_exists(public_path('images/templates/canvas-desktop.svg')))->toBeTrue();
});

it('shows the scoped website design editor', function () {
    [$user] = designWorkspace();

    $this->actingAs($user)
        ->get('/dashboard/design')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('design/index')
            ->has('templates', 3)
            ->where('website.template_key', 'horizon')
            ->has('theme.primary')
            ->has('contrast', 4));
});

it('updates validated theme tokens for the current website', function () {
    [$user, $website] = designWorkspace();
    $theme = config('templates.catalog.horizon.default_theme');
    $theme['primary'] = '#123456';
    $theme['heading_font'] = 'Manrope';

    $this->actingAs($user)
        ->put('/dashboard/design/theme', ['theme' => $theme])
        ->assertRedirect();

    expect($website->fresh()?->theme_settings['primary'])->toBe('#123456')
        ->and($website->fresh()?->theme_settings['heading_font'])->toBe('Manrope');
});

it('rejects unsafe or malformed theme values', function () {
    [$user, $website] = designWorkspace();
    $theme = config('templates.catalog.horizon.default_theme');
    $theme['primary'] = 'url(javascript:alert(1))';
    $theme['raw_css'] = 'body { display: none }';

    $this->actingAs($user)
        ->put('/dashboard/design/theme', ['theme' => $theme])
        ->assertSessionHasErrors('theme');

    expect($website->fresh()?->theme_settings['primary'])->not->toBe('url(javascript:alert(1))');
});

it('resets the website theme to its selected template defaults', function () {
    [$user, $website] = designWorkspace(['theme_settings' => ['primary' => '#123456']]);

    $this->actingAs($user)->post('/dashboard/design/theme/reset')->assertRedirect();

    expect($website->fresh()?->theme_settings)->toBe(config('templates.catalog.horizon.default_theme'));
});

it('snapshots presentation settings and preserves content when switching templates', function () {
    [$user, $website] = designWorkspace();
    $beforeTheme = $website->theme_settings;
    $beforeSections = $website->initial_sections;

    $this->actingAs($user)->post('/dashboard/design/template', [
        'template_key' => 'canvas',
        'confirmed' => true,
    ])->assertRedirect();

    $fresh = $website->fresh();
    $snapshot = WebsiteTemplateSnapshot::query()->withoutGlobalScopes()->firstOrFail();

    expect($fresh?->template_key)->toBe('canvas')
        ->and($fresh?->initial_sections)->toBe($beforeSections)
        ->and($fresh?->theme_settings['primary'])->toBe($beforeTheme['primary'])
        ->and($snapshot->from_template_key)->toBe('horizon')
        ->and($snapshot->to_template_key)->toBe('canvas')
        ->and($snapshot->theme_settings)->toBe($beforeTheme)
        ->and($snapshot->initial_sections)->toBe($beforeSections);
});

it('requires confirmation before switching templates', function () {
    [$user, $website] = designWorkspace();

    $this->actingAs($user)->post('/dashboard/design/template', [
        'template_key' => 'canvas',
        'confirmed' => false,
    ])->assertSessionHasErrors('confirmed');

    expect($website->fresh()?->template_key)->toBe('horizon')
        ->and(WebsiteTemplateSnapshot::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('serves active public template previews', function () {
    $this->get('/templates/localpro/preview')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('design/template-preview')
            ->where('template.key', 'localpro'));
});

it('calculates WCAG contrast ratios and failures', function () {
    $contrast = app(ContrastService::class);
    $theme = config('templates.catalog.horizon.default_theme');
    $theme['text'] = '#eeeeee';
    $theme['background'] = '#ffffff';

    expect($contrast->ratio('#000000', '#ffffff'))->toBe(21.0)
        ->and($contrast->analyze($theme)[0]['passes'])->toBeFalse();
});

/**
 * @param  array<string, mixed>  $websiteOverrides
 * @return array{User, Website}
 */
function designWorkspace(array $websiteOverrides = []): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'template_key' => 'horizon',
        ...$websiteOverrides,
    ]);

    return [$user, $website];
}
