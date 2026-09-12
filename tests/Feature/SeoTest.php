<?php

use App\Actions\PublishWebsite;
use App\Actions\RestoreWebsiteVersion;
use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Models\ContentPost;
use App\Models\Faq;
use App\Models\JobOpening;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Redirect;
use App\Models\Product;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Seo\SeoManager;
use App\Tenancy\TenantScope;
use Database\Seeders\TemplateSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
    Queue::fake();
});

it('manages tenant scoped site and page SEO settings and validates unsafe inputs', function () {
    [$user, $website, , $home] = phaseNineWorkspace();

    $this->actingAs($user)->get('/dashboard/seo')->assertOk()->assertInertia(fn ($page) => $page->component('seo/index')->has('siteSettings')->has('pages', 1)->has('audit.score'));
    $this->actingAs($user)->put('/dashboard/seo/site', phaseNineSitePayload([
        'site_title' => 'Ezyweb Search Studio',
        'social_profiles_text' => "https://www.linkedin.com/company/ezyweb\nhttps://x.com/ezyweb",
    ]))->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($user)->put('/dashboard/seo/pages/'.$home->getKey(), phaseNinePagePayload([
        'seo_title' => 'Build your business website with Ezyweb', 'schema_type' => 'AboutPage',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    expect($website->seoSetting()->withoutGlobalScopes()->firstOrFail()->social_profiles)->toHaveCount(2)
        ->and($home->seoSetting()->withoutGlobalScopes()->firstOrFail()->seo_title)->toBe('Build your business website with Ezyweb')
        ->and($website->fresh()->draft_revision)->toBeGreaterThan(0);
    $this->actingAs($user)->put('/dashboard/seo/site', phaseNineSitePayload(['social_profiles_text' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('social_profiles_text');
    $this->actingAs($user)->put('/dashboard/seo/pages/'.$home->getKey(), phaseNinePagePayload(['canonical_url' => 'http://unsafe.example.test']))
        ->assertSessionHasErrors('canonical_url');

    [$foreignUser, , , $foreignHome] = phaseNineWorkspace();
    $this->actingAs($user)->put('/dashboard/seo/pages/'.$foreignHome->getKey(), phaseNinePagePayload())->assertNotFound();
    $this->actingAs($foreignUser)->get('/dashboard/seo')->assertOk();
});

it('publishes immutable canonical social verification analytics and structured metadata', function () {
    [$user, $website, $hostname, $home] = phaseNineWorkspace();
    app(SeoManager::class)->updateSite($website, phaseNineSitePayload([
        'site_title' => 'Ezyweb', 'default_meta_description' => 'Fast business websites for ambitious Indian companies.',
        'default_social_image' => 'https://cdn.example.test/ezyweb-social.jpg', 'business_name' => 'Ezyweb Technologies',
        'business_type' => 'Organization', 'google_site_verification' => 'verify_Ezyweb-123', 'google_analytics_id' => 'G-EZYWEB123',
        'social_profiles_text' => 'https://www.linkedin.com/company/ezyweb',
    ]));
    app(SeoManager::class)->updatePage($website, $home, phaseNinePagePayload([
        'seo_title' => 'Ezyweb Website Builder', 'meta_description' => 'Create and publish a professional business website.',
        'canonical_url' => 'https://www.ezyweb.in/', 'open_graph_title' => 'Ezyweb — Websites made easy',
    ]));
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $response = $this->get('http://'.$hostname.'/')->assertOk();
    $response->assertSee('<title>Ezyweb Website Builder</title>', false)
        ->assertSee('<link rel="canonical" href="https://www.ezyweb.in/">', false)
        ->assertSee('property="og:title" content="Ezyweb — Websites made easy"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('name="google-site-verification" content="verify_Ezyweb-123"', false)
        ->assertSee('googletagmanager.com/gtag/js?id=G-EZYWEB123', false)
        ->assertSee('"@type":"Organization"', false)->assertSee('"@type":"WebSite"', false);

    app(SeoManager::class)->updatePage($website, $home, phaseNinePagePayload(['seo_title' => 'Unpublished title']));
    $this->get('http://'.$hostname.'/')->assertSee('Ezyweb Website Builder')->assertDontSee('Unpublished title');
});

it('serves published sitemaps robots content schema and pagination metadata', function () {
    [$user, $website, $hostname, $home] = phaseNineWorkspace();
    $about = Page::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'About', 'slug' => 'about', 'is_home' => false]);
    PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $about->getKey(), 'type' => 'hero', 'content' => ['heading' => 'About Ezyweb']]);
    app(SeoManager::class)->updateSite($website, phaseNineSitePayload());
    app(SeoManager::class)->updatePage($website, $home, phaseNinePagePayload(['canonical_url' => 'https://www.ezyweb.in/']));
    app(SeoManager::class)->updatePage($website, $about, phaseNinePagePayload(['is_indexed' => false]));
    ContentPost::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog,
        'status' => ContentPostStatus::Published, 'published_at' => now()->subMinute(), 'title' => 'SEO Guide', 'slug' => 'seo-guide',
        'excerpt' => 'A practical guide.', 'author_name' => 'Ezyweb Team', 'open_graph_image' => 'https://cdn.example.test/guide.jpg',
    ]);
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->get('http://'.$hostname.'/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8')
        ->assertSee('https://www.ezyweb.in/', false)->assertSee('http://'.$hostname.'/blog', false)
        ->assertSee('http://'.$hostname.'/blog/seo-guide', false)->assertDontSee('/about');
    $this->get('http://'.$hostname.'/robots.txt')->assertOk()->assertSee('Allow: /')->assertSee('http://'.$hostname.'/sitemap.xml');
    $this->get('http://'.$hostname.'/blog/seo-guide')->assertOk()->assertSee('property="og:type" content="article"', false)
        ->assertSee('"@type":"Article"', false)->assertSee('"name":"Ezyweb Team"', false)
        ->assertSee('<link rel="canonical" href="http://'.$hostname.'/blog/seo-guide">', false);

    app(SeoManager::class)->updateSite($website, phaseNineSitePayload(['search_engine_visibility' => false]));
    $this->get('http://'.$hostname.'/robots.txt')->assertSee('Allow: /');
    app(PublishWebsite::class)->execute($website->fresh(), $user);
    $this->get('http://'.$hostname.'/robots.txt')->assertSee('Disallow: /');
    $this->get('http://'.$hostname.'/sitemap.xml')->assertDontSee('<url>');
});

it('publishes safe internal redirects without leaking draft changes and rejects loops', function () {
    [$user, $website, $hostname] = phaseNineWorkspace();
    $manager = app(SeoManager::class);
    $redirect = $manager->saveRedirect($website, ['source_path' => '/legacy', 'target_path' => '/new-home?ref=legacy', 'status_code' => 301, 'is_active' => true]);

    $this->get('http://'.$hostname.'/legacy')->assertNotFound();
    app(PublishWebsite::class)->execute($website->fresh(), $user);
    $this->get('http://'.$hostname.'/legacy')->assertRedirect('http://'.$hostname.'/new-home?ref=legacy')->assertStatus(301);
    expect($redirect->fresh()->hit_count)->toBe(1);

    $manager->saveRedirect($website, ['source_path' => '/legacy', 'target_path' => '/draft-target', 'status_code' => 302, 'is_active' => true], $redirect);
    $this->get('http://'.$hostname.'/legacy')->assertRedirect('http://'.$hostname.'/new-home?ref=legacy')->assertStatus(301);
    $this->actingAs($user)->post('/dashboard/seo/redirects', ['source_path' => '/external', 'target_path' => 'https://evil.example', 'status_code' => 301, 'is_active' => true])->assertSessionHasErrors('target_path');
    $this->actingAs($user)->post('/dashboard/seo/redirects', ['source_path' => '/same', 'target_path' => '/same', 'status_code' => 301, 'is_active' => true])->assertSessionHasErrors('target_path');
});

it('emits only applicable visible FAQ news event and job structured data', function () {
    [$user, $website, $hostname, $home] = phaseNineWorkspace();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'faq', 'anchor' => 'questions', 'content' => ['heading' => 'Common questions'], 'is_enabled' => true,
    ]);
    foreach (['services', 'products'] as $type) {
        PageSection::factory()->create([
            'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
            'type' => $type, 'anchor' => $type, 'content' => ['heading' => ucfirst($type)], 'is_enabled' => true,
        ]);
    }
    Service::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'SEO consulting', 'is_active' => true]);
    Product::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Business website package', 'is_active' => true]);
    Faq::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'question' => 'Can Ezyweb publish my site?', 'answer' => 'Yes, after you approve the draft.', 'is_active' => true]);
    Faq::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'question' => 'Hidden question?', 'answer' => 'Hidden answer.', 'is_active' => false]);
    foreach ([
        [ContentPostType::News, 'Ezyweb launch update', 'launch-update', ['author_name' => 'News Desk']],
        [ContentPostType::Event, 'Ezyweb workshop', 'ezyweb-workshop', ['event_start_date' => now()->addWeek()->toDateString(), 'venue' => 'Kolkata Studio']],
    ] as [$type, $title, $slug, $extra]) {
        ContentPost::factory()->create([
            'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => $type,
            'status' => ContentPostStatus::Published, 'published_at' => now()->subMinute(), 'title' => $title, 'slug' => $slug,
            ...$extra,
        ]);
    }
    JobOpening::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'SEO Specialist', 'slug' => 'seo-specialist',
        'location' => 'Kolkata', 'description' => 'Improve organic visibility for Ezyweb customers.', 'closing_date' => now()->addMonth(),
    ]);
    app(SeoManager::class)->updateSite($website, phaseNineSitePayload(['business_name' => 'Ezyweb Technologies']));
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->get('http://'.$hostname.'/')->assertOk()->assertSee('"@type":"FAQPage"', false)
        ->assertSee('Can Ezyweb publish my site?', false)->assertDontSee('Hidden question?', false)
        ->assertSee('"@type":"Service"', false)->assertSee('SEO consulting', false)
        ->assertSee('"@type":"Product"', false)->assertSee('Business website package', false);
    $this->get('http://'.$hostname.'/news/launch-update')->assertOk()->assertSee('"@type":"NewsArticle"', false);
    $this->get('http://'.$hostname.'/events/ezyweb-workshop')->assertOk()->assertSee('"@type":"Event"', false)->assertSee('Kolkata Studio', false);
    $this->get('http://'.$hostname.'/careers/seo-specialist')->assertOk()->assertSee('"@type":"JobPosting"', false)
        ->assertSee('"datePosted":', false)->assertSee('"hiringOrganization":{"@type":"Organization","name":"Ezyweb Technologies"}', false);
});

it('renders the published tenant custom 404 for missing pages content and nested paths', function () {
    [$user, $website, $hostname] = phaseNineWorkspace();
    app(SeoManager::class)->updateSite($website, phaseNineSitePayload([
        'not_found_title' => 'This Ezyweb page moved', 'not_found_message' => 'We could not find that resource.', 'not_found_button_label' => 'Visit Ezyweb home',
    ]));
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    foreach (['/missing', '/blog/missing-post', '/nested/path/missing'] as $path) {
        $this->get('http://'.$hostname.$path)->assertNotFound()->assertSee('This Ezyweb page moved')
            ->assertSee('We could not find that resource.')->assertSee('Visit Ezyweb home')
            ->assertSee('name="robots" content="noindex, follow"', false);
    }
});

it('restores SEO metadata and redirects from a historical website version', function () {
    [$user, $website, , $home] = phaseNineWorkspace();
    $manager = app(SeoManager::class);
    $manager->updateSite($website, phaseNineSitePayload(['site_title' => 'Original SEO']));
    $manager->updatePage($website, $home, phaseNinePagePayload(['seo_title' => 'Original home title']));
    $manager->saveRedirect($website, ['source_path' => '/old', 'target_path' => '/original', 'status_code' => 301, 'is_active' => true]);
    $first = app(PublishWebsite::class)->execute($website->fresh(), $user);

    $manager->updateSite($website, phaseNineSitePayload(['site_title' => 'New SEO']));
    $manager->updatePage($website, $home, phaseNinePagePayload(['seo_title' => 'New home title']));
    $redirect = Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail();
    $manager->saveRedirect($website, ['source_path' => '/old', 'target_path' => '/new', 'status_code' => 302, 'is_active' => true], $redirect);
    app(RestoreWebsiteVersion::class)->execute($website->fresh(), $first, $user);

    $restoredHome = Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->where('is_home', true)->firstOrFail();
    expect($website->seoSetting()->withoutGlobalScopes()->firstOrFail()->site_title)->toBe('Original SEO')
        ->and($restoredHome->seoSetting()->withoutGlobalScopes()->firstOrFail()->seo_title)->toBe('Original home title')
        ->and(Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail()->target_path)->toBe('/original');
});

/** @return array{User, Website, string, Page} */
function phaseNineWorkspace(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey(), 'name' => 'Ezyweb']);
    $hostname = 'seo-'.$website->getKey().'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create(['hostname' => $hostname, 'subdomain' => 'seo-'.$website->getKey(), 'is_primary' => true]);
    $page = Page::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'title' => 'Home', 'is_home' => true, 'slug' => 'home']);
    PageSection::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'page_id' => $page->getKey(), 'type' => 'hero', 'content' => ['heading' => 'Ezyweb makes websites easy']]);

    return [$user, $website, $hostname, $page];
}

/** @param array<string, mixed> $overrides @return array<string, mixed> */
function phaseNineSitePayload(array $overrides = []): array
{
    return [...[
        'site_title' => 'Ezyweb', 'default_meta_description' => 'Professional websites made easy.',
        'default_social_image' => 'https://cdn.example.test/ezyweb.jpg', 'business_name' => 'Ezyweb',
        'business_type' => 'Organization', 'primary_language' => 'en-IN', 'search_engine_visibility' => true,
        'google_site_verification' => '', 'google_analytics_id' => '', 'social_profiles_text' => '',
        'not_found_title' => 'Page not found', 'not_found_message' => 'The page you requested could not be found.',
        'not_found_button_label' => 'Return home',
    ], ...$overrides];
}

/** @param array<string, mixed> $overrides @return array<string, mixed> */
function phaseNinePagePayload(array $overrides = []): array
{
    return [...[
        'seo_title' => '', 'meta_description' => '', 'canonical_url' => '', 'open_graph_title' => '',
        'open_graph_description' => '', 'open_graph_image' => '', 'is_indexed' => true, 'is_followed' => true,
        'schema_type' => 'WebPage',
    ], ...$overrides];
}
