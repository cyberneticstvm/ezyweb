<?php

use App\Actions\PublishWebsite;
use App\Actions\RestoreWebsiteVersion;
use App\Enums\PublicationStatus;
use App\Enums\WebsiteStatus;
use App\Jobs\WarmPublishedWebsiteCache;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteVersion;
use App\Publishing\PreviewUrlFactory;
use Database\Seeders\TemplateSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('serves only authenticated signed draft previews with a noindex banner', function () {
    [$user, $website, , $section] = publishingWorkspace();
    $section->update(['content' => ['heading' => 'Private draft heading']]);
    $url = app(PreviewUrlFactory::class)->draft($website);
    expect($url)->toStartWith('/preview/');

    $this->get($url)->assertRedirect('/login');
    $this->actingAs($user)->get($url)
        ->assertOk()
        ->assertSee('Private draft heading')
        ->assertSee('Draft preview')
        ->assertSee('noindex, nofollow, noarchive', false);
    $this->actingAs($user)->get('https://alternate-preview.test'.$url)->assertOk();
    $this->actingAs($user)->get('/preview/'.$website->getKey())->assertForbidden();

    $expired = URL::temporarySignedRoute('website.preview.draft', now()->subMinute(), ['website' => $website->getKey()], absolute: false);
    $this->actingAs($user)->get($expired)->assertForbidden();
});

it('publishes an immutable snapshot and keeps later drafts off the live site', function () {
    Queue::fake();
    [$user, $website, $hostname, $section] = publishingWorkspace();
    $section->update(['content' => ['heading' => 'Version one heading']]);

    $version = app(PublishWebsite::class)->execute($website, $user);
    $website->refresh();

    expect($version->version_number)->toBe(1)
        ->and($website->status)->toBe(WebsiteStatus::Published)
        ->and($website->publication_status)->toBe(PublicationStatus::Published)
        ->and($website->published_version_id)->toBe($version->getKey())
        ->and($website->published_revision)->toBe($website->draft_revision);
    Queue::assertPushed(WarmPublishedWebsiteCache::class);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Version one heading');

    $section->update(['content' => ['heading' => 'Unpublished version two']]);
    $website->increment('draft_revision');
    $this->get("http://{$hostname}/")
        ->assertOk()->assertSee('Version one heading')->assertDontSee('Unpublished version two');
});

it('publishes idempotently and invalidates only the current website cache', function () {
    Queue::fake();
    [$user, $website] = publishingWorkspace();
    Cache::put('unrelated-platform-key', 'keep-me');
    Cache::put("website:{$website->getKey()}:navigation", 'stale');

    $first = app(PublishWebsite::class)->execute($website, $user);
    $second = app(PublishWebsite::class)->execute($website->fresh(), $user);

    expect($second->getKey())->toBe($first->getKey())
        ->and(WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->count())->toBe(1)
        ->and(Cache::get('unrelated-platform-key'))->toBe('keep-me')
        ->and(Cache::has("website:{$website->getKey()}:navigation"))->toBeFalse();
});

it('excludes soft deleted sections and repairs an affected live snapshot on republish', function () {
    Queue::fake();
    [$user, $website, $hostname, $hero] = publishingWorkspace();
    $about = PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $hero->page_id,
        'type' => 'about',
        'content' => ['heading' => 'Removed about section'],
        'sort_order' => 1,
    ]);

    $affected = app(PublishWebsite::class)->execute($website, $user);
    expect(collect($affected->snapshot()['pages'][0]['sections'])->pluck('type')->all())
        ->toBe(['hero', 'about']);

    $about->delete();
    $repaired = app(PublishWebsite::class)->execute($website->fresh(), $user);

    expect($repaired->getKey())->not->toBe($affected->getKey())
        ->and($repaired->version_number)->toBe(2)
        ->and(collect($repaired->snapshot()['pages'][0]['sections'])->pluck('type')->all())
        ->toBe(['hero']);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertDontSee('Removed about section');
});

it('fails incomplete publishing without replacing the current live version and can be retried', function () {
    Queue::fake();
    [$user, $website, , $section] = publishingWorkspace();
    $live = app(PublishWebsite::class)->execute($website, $user);
    $section->update(['is_enabled' => false]);
    $website->increment('draft_revision');

    try {
        app(PublishWebsite::class)->execute($website->fresh(), $user);
        $this->fail('Incomplete website publishing should fail validation.');
    } catch (ValidationException) {
        $website->refresh();
        expect($website->publication_status)->toBe(PublicationStatus::Failed)
            ->and($website->published_version_id)->toBe($live->getKey())
            ->and(WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->count())->toBe(1);
    }

    $section->update(['is_enabled' => true]);
    $retry = app(PublishWebsite::class)->execute($website->fresh(), $user);
    expect($retry->version_number)->toBe(2);
});

it('restores a historical snapshot as a new draft while the live version remains unchanged', function () {
    Queue::fake();
    [$user, $website, $hostname, $section] = publishingWorkspace();
    $section->update(['content' => ['heading' => 'Original published copy']]);
    $first = app(PublishWebsite::class)->execute($website, $user);
    $section->update(['content' => ['heading' => 'Second published copy']]);
    $website->increment('draft_revision');
    $second = app(PublishWebsite::class)->execute($website->fresh(), $user);

    $restored = app(RestoreWebsiteVersion::class)->execute($website->fresh(), $first, $user);
    $website->refresh();
    $restoredSection = PageSection::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->firstOrFail();

    expect($restored->version_number)->toBe(3)
        ->and($restored->published_at)->toBeNull()
        ->and($website->published_version_id)->toBe($second->getKey())
        ->and($website->publication_status)->toBe(PublicationStatus::Saved)
        ->and($restoredSection->content['heading'])->toBe('Original published copy');
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Second published copy')->assertDontSee('Original published copy');

    app(PublishWebsite::class)->execute($website->fresh(), $user);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Original published copy')->assertDontSee('Second published copy');
});

it('keeps version history and restore operations tenant scoped', function () {
    Queue::fake();
    [$user, $website] = publishingWorkspace();
    app(PublishWebsite::class)->execute($website, $user);
    [, $otherWebsite] = publishingWorkspace();
    $foreign = WebsiteVersion::factory()->create([
        'tenant_id' => $otherWebsite->tenant_id,
        'website_id' => $otherWebsite->getKey(),
    ]);

    $this->actingAs($user)->get('/dashboard/publishing')
        ->assertOk()->assertInertia(fn ($page) => $page->component('publishing/index')->has('versions', 1));
    $this->actingAs($user)->post('/dashboard/publishing/versions/'.$foreign->getKey().'/restore')->assertNotFound();
});

/** @return array{User, Website, string, PageSection} */
function publishingWorkspace(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey(), 'status' => WebsiteStatus::Draft]);
    $hostname = 'publish-'.$website->getKey().'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create([
        'hostname' => $hostname,
        'subdomain' => 'publish-'.$website->getKey(),
        'is_primary' => true,
    ]);
    $home = Page::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'website_id' => $website->getKey(),
        'title' => 'Home',
        'slug' => 'home',
        'navigation_label' => 'Home',
        'is_home' => true,
    ]);
    $section = PageSection::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'content' => ['heading' => 'Ready to publish'],
    ]);

    return [$user, $website, $hostname, $section];
}
