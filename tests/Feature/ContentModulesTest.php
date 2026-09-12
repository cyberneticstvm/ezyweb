<?php

use App\Actions\PublishWebsite;
use App\Actions\RestoreWebsiteVersion;
use App\Content\ContentModuleRegistry;
use App\Content\RichTextSanitizer;
use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Enums\WebsiteStatus;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteVersion;
use App\Tenancy\TenantScope;
use Database\Seeders\TemplateSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('sanitizes controlled rich text and unsafe links at storage and render boundaries', function () {
    $clean = app(RichTextSanitizer::class)->sanitize('<div><script>bad()</script><p onclick="bad()"><strong>Safe</strong> <a href="javascript:bad()">link</a></p></div>');

    expect($clean)->toBe('<p><strong>Safe</strong> <a>link</a></p>');
});

it('keeps safe responsive blog images and removes executable image markup', function () {
    $clean = app(RichTextSanitizer::class)->sanitize('<figure data-width="50" data-align="right" onclick="bad()"><img src="/media/01KZZZZZZZZZZZZZZZZZZZZZZZ/large" alt="Team &amp; office" onerror="bad()"><figcaption>Inside Ezyweb</figcaption></figure><figure data-width="999" data-align="unsafe"><img src="/media/01KYYYYYYYYYYYYYYYYYYYYYYY/large"></figure><img src="javascript:bad()"><hr>');

    expect($clean)->toContain('<figure data-width="50" data-align="right"><img src="/media/01KZZZZZZZZZZZZZZZZZZZZZZZ/large" alt="Team &amp; office" loading="lazy"><figcaption>Inside Ezyweb</figcaption></figure>')
        ->and($clean)->toContain('data-width="100" data-align="center"')
        ->and($clean)->toContain('<hr>')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->not->toContain('onerror');
});

it('registers every phase seven module and serves its tenant dashboard', function () {
    [$user] = phaseSevenWorkspace();

    expect(app(ContentModuleRegistry::class)->keys())->toBe([
        'services', 'products', 'brands', 'testimonials', 'portfolio', 'gallery',
        'team', 'faq', 'blog', 'news', 'events', 'careers',
    ]);

    foreach (app(ContentModuleRegistry::class)->keys() as $module) {
        $this->actingAs($user)->get('/dashboard/content/'.$module)
            ->assertOk()->assertInertia(fn ($page) => $page->component('content-modules/index')->where('module.key', $module));
    }
});

it('creates updates and removes sanitized service content while touching the draft', function () {
    [$user, $website] = phaseSevenWorkspace();

    $this->actingAs($user)->post('/dashboard/content/services', [
        'title' => 'Business Strategy',
        'slug' => '',
        'short_description' => '<script>alert(1)</script>Clear direction',
        'full_description' => '<b>Trusted</b> advice',
        'is_active' => true,
        'is_featured' => true,
        'display_order' => 2,
    ])->assertRedirect();

    $service = Service::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail();
    expect($service->slug)->toBe('business-strategy')
        ->and($service->short_description)->toBe('alert(1)Clear direction')
        ->and($service->full_description)->toBe('<b>Trusted</b> advice')
        ->and($website->fresh()->draft_revision)->toBeGreaterThan(0);

    $this->actingAs($user)->put('/dashboard/content/services/'.$service->getKey(), [
        'title' => 'Business Planning', 'slug' => 'business-planning', 'is_active' => false,
    ])->assertRedirect();
    expect($service->fresh()->title)->toBe('Business Planning')->and($service->fresh()->is_active)->toBeFalse();

    $this->actingAs($user)->delete('/dashboard/content/services/'.$service->getKey())->assertRedirect();
    expect(Service::query()->withoutGlobalScope(TenantScope::class)->whereKey($service->getKey())->exists())->toBeFalse();
});

it('keeps module records isolated to their website and tenant', function () {
    [$user] = phaseSevenWorkspace();
    [, $foreignWebsite] = phaseSevenWorkspace();
    $foreign = Service::factory()->create(['tenant_id' => $foreignWebsite->tenant_id, 'website_id' => $foreignWebsite->getKey()]);

    $this->actingAs($user)->put('/dashboard/content/services/'.$foreign->getKey(), [
        'title' => 'Cross tenant edit', 'slug' => 'cross-tenant-edit', 'is_active' => true,
    ])->assertNotFound();
    $this->actingAs($user)->delete('/dashboard/content/services/'.$foreign->getKey())->assertNotFound();
});

it('uses one controlled publishing model for blog news and scheduled events with taxonomy', function () {
    [$user, $website] = phaseSevenWorkspace();

    $this->actingAs($user)->post('/dashboard/content/blog', [
        'title' => 'Growth Guide', 'slug' => 'growth-guide', 'body' => '<script>bad()</script>Useful guide',
        'category' => 'Guides', 'tags' => 'growth, strategy, growth', 'status' => 'published',
    ])->assertRedirect();
    $this->actingAs($user)->post('/dashboard/content/news', [
        'title' => 'Growth Guide', 'slug' => 'growth-guide', 'status' => 'draft',
    ])->assertRedirect();
    $this->actingAs($user)->post('/dashboard/content/events', [
        'title' => 'Future Workshop', 'slug' => 'future-workshop', 'status' => 'scheduled',
        'published_at' => now()->addDay()->toDateTimeString(), 'event_start_date' => now()->addWeek()->toDateString(),
        'event_end_date' => now()->addWeek()->addDay()->toDateString(),
    ])->assertRedirect();

    $blog = ContentPost::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('content_type', ContentPostType::Blog)->firstOrFail();
    expect($blog->status)->toBe(ContentPostStatus::Published)
        ->and($blog->published_at)->not->toBeNull()
        ->and($blog->body)->toBe('Useful guide')
        ->and($blog->category?->name)->toBe('Guides')
        ->and($blog->tags)->toHaveCount(2)
        ->and(ContentPost::query()->withoutGlobalScope(TenantScope::class)->where('content_type', ContentPostType::News)->where('slug', 'growth-guide')->exists())->toBeTrue();
});

it('enforces configurable module limits', function () {
    config()->set('content-modules.limits.services', 1);
    [$user] = phaseSevenWorkspace();

    $this->actingAs($user)->post('/dashboard/content/services', ['title' => 'First', 'slug' => 'first', 'is_active' => true])->assertRedirect();
    $this->actingAs($user)->post('/dashboard/content/services', ['title' => 'Second', 'slug' => 'second', 'is_active' => true])
        ->assertSessionHasErrors('limit');
});

it('publishes content modules immutably and restores them as a new draft', function () {
    Queue::fake();
    [$user, $website, $hostname] = phaseSevenWorkspace(true);
    $service = Service::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Original advisory', 'slug' => 'original-advisory', 'is_active' => true,
    ]);

    $first = app(PublishWebsite::class)->execute($website, $user);
    $this->get('http://'.$hostname.'/')->assertOk()->assertSee('Original advisory');

    $service->update(['title' => 'New advisory']);
    $website->increment('draft_revision');
    $this->get('http://'.$hostname.'/')->assertOk()->assertSee('Original advisory')->assertDontSee('New advisory');
    $second = app(PublishWebsite::class)->execute($website->fresh(), $user);
    $this->get('http://'.$hostname.'/')->assertOk()->assertSee('New advisory')->assertDontSee('Original advisory');

    app(RestoreWebsiteVersion::class)->execute($website->fresh(), $first, $user);
    expect(Service::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail()->title)->toBe('Original advisory')
        ->and($website->fresh()->published_version_id)->toBe($second->getKey())
        ->and(WebsiteVersion::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->count())->toBe(3);
    $this->get('http://'.$hostname.'/')->assertOk()->assertSee('New advisory')->assertDontSee('Original advisory');
});

it('serves searchable public archives details and due scheduled content from the published snapshot', function () {
    Queue::fake();
    [$user, $website, $hostname] = phaseSevenWorkspace();
    foreach ([
        ['title' => 'Published guide', 'slug' => 'published-guide', 'status' => ContentPostStatus::Published, 'published_at' => now()->subHour(), 'category' => 'Guides'],
        ['title' => 'Due scheduled guide', 'slug' => 'due-scheduled-guide', 'status' => ContentPostStatus::Scheduled, 'published_at' => now()->subMinute(), 'category' => 'Guides'],
        ['title' => 'Future scheduled guide', 'slug' => 'future-scheduled-guide', 'status' => ContentPostStatus::Scheduled, 'published_at' => now()->addDay(), 'category' => 'Updates'],
    ] as $record) {
        $post = ContentPost::factory()->create([
            'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog,
            'title' => $record['title'], 'slug' => $record['slug'], 'status' => $record['status'], 'published_at' => $record['published_at'],
            'body' => '<script>never executable</script>Plain body',
        ]);
        $category = ContentCategory::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate([
            'website_id' => $website->getKey(), 'slug' => strtolower($record['category']),
        ], ['tenant_id' => $website->tenant_id, 'name' => $record['category']]);
        $post->update(['content_category_id' => $category->getKey()]);
    }
    app(PublishWebsite::class)->execute($website, $user);

    $this->get('http://'.$hostname.'/blog')->assertOk()
        ->assertSee('Published guide')->assertSee('Due scheduled guide')->assertDontSee('Future scheduled guide');
    $this->get('http://'.$hostname.'/blog/published-guide')->assertOk()
        ->assertSee('Plain body')->assertDontSee('never executable');
    $this->get('http://'.$hostname.'/blog?category=Updates')->assertOk()
        ->assertDontSee('Published guide')->assertDontSee('Future scheduled guide');
});

/** @return array{User, Website, string} */
function phaseSevenWorkspace(bool $withServicesSection = false): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey(), 'status' => WebsiteStatus::Draft]);
    $hostname = 'content-'.$website->getKey().'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create(['hostname' => $hostname, 'subdomain' => 'content-'.$website->getKey(), 'is_primary' => true]);
    $page = Page::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'is_home' => true, 'slug' => 'home']);
    PageSection::factory()->create([
        'tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'page_id' => $page->getKey(),
        'type' => $withServicesSection ? 'services' : 'hero', 'content' => ['heading' => $withServicesSection ? 'Our services' : 'Welcome'],
    ]);

    return [$user, $website, $hostname];
}
