<?php

use App\Actions\PublishWebsite;
use App\Contact\ContactSettings;
use App\Contact\WhatsappUrl;
use App\Jobs\SendContactEnquiryEmail;
use App\Models\ContactMessage;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Tenancy\TenantScope;
use Database\Seeders\TemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('updates tenant scoped contact settings and marks the website draft', function () {
    [$user, $website] = phaseEightWorkspace();

    $this->actingAs($user)->get('/dashboard/contact')->assertOk()->assertInertia(fn ($page) => $page->component('contact/index'));
    $this->actingAs($user)->put('/dashboard/contact/settings', contactSettingsPayload([
        'heading' => 'Talk with Ezyweb', 'recipient_email' => 'leads@example.test',
    ]))->assertRedirect();

    $setting = $website->contactSetting()->withoutGlobalScopes()->firstOrFail();
    expect($setting->heading)->toBe('Talk with Ezyweb')
        ->and($setting->recipient_email)->toBe('leads@example.test')
        ->and($website->fresh()->draft_revision)->toBeGreaterThan(0);
});

it('accepts protected public enquiries and dispatches dashboard and email notifications', function () {
    Queue::fake();
    config()->set('services.turnstile.secret_key', 'turnstile-secret');
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(ContactSettings::class)->updateContact($website, contactSettingsPayload(['recipient_email' => 'leads@example.test']));
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->withHeader('Referer', 'http://'.$hostname.'/?utm_source=google&utm_campaign=launch')
        ->post('http://'.$hostname.'/contact/enquiries', [
            'name' => 'Contact Visitor', 'email' => 'visitor@example.test', 'phone' => '',
            'subject' => 'Project enquiry', 'message' => '<b>Build my site</b>',
            'company_website' => '', 'cf-turnstile-response' => 'valid-token',
        ])->assertRedirect()->assertSessionHas('contact_success');

    $message = ContactMessage::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail();
    expect($message->message)->toBe('Build my site')
        ->and($message->source_page)->toBe('/')
        ->and($message->utm_parameters)->toMatchArray(['utm_source' => 'google', 'utm_campaign' => 'launch'])
        ->and($message->ip_hash)->not->toBeNull()
        ->and($user->notifications()->count())->toBe(1);
    Queue::assertPushed(SendContactEnquiryEmail::class, fn (SendContactEnquiryEmail $job): bool => $job->recipient === 'leads@example.test');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
});

it('blocks honeypot submissions and invalid turnstile tokens', function () {
    Queue::fake();
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(ContactSettings::class)->updateContact($website, contactSettingsPayload());
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->from('http://'.$hostname.'/')->post('http://'.$hostname.'/contact/enquiries', [
        'name' => 'Bot', 'email' => 'bot@example.test', 'message' => 'Spam', 'company_website' => 'spam.example',
    ])->assertSessionHasErrors('company_website');

    config()->set('services.turnstile.secret_key', 'turnstile-secret');
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);
    $this->from('http://'.$hostname.'/')->post('http://'.$hostname.'/contact/enquiries', [
        'name' => 'Bot', 'email' => 'bot@example.test', 'message' => 'Spam', 'company_website' => '', 'cf-turnstile-response' => 'bad-token',
    ])->assertSessionHasErrors('cf-turnstile-response');
    expect(ContactMessage::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(0);
});

it('accepts an enquiry when optional email and phone fields are disabled', function () {
    Queue::fake();
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(ContactSettings::class)->updateContact($website, contactSettingsPayload([
        'form_fields' => ['name' => true, 'email' => false, 'phone' => false, 'subject' => false, 'message' => true],
    ]));
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->post('http://'.$hostname.'/contact/enquiries', [
        'name' => 'Privacy-first visitor', 'message' => 'Please contact me later.', 'company_website' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect(ContactMessage::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->count())->toBe(1);
});

it('manages enquiries securely and exports spreadsheet safe csv', function () {
    [$user, $website] = phaseEightWorkspace();
    $message = ContactMessage::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => '=FORMULA', 'subject' => 'CSV safety',
    ]);
    [, $foreignWebsite] = phaseEightWorkspace();
    $foreign = ContactMessage::factory()->create(['tenant_id' => $foreignWebsite->tenant_id, 'website_id' => $foreignWebsite->getKey()]);

    $this->actingAs($user)->post('/dashboard/contact/messages/'.$message->public_id.'/read')->assertRedirect();
    expect($message->fresh()->is_read)->toBeTrue();
    $this->actingAs($user)->post('/dashboard/contact/messages/'.$message->public_id.'/archive', ['archived' => true])->assertRedirect();
    expect($message->fresh()->is_archived)->toBeTrue();
    $this->actingAs($user)->post('/dashboard/contact/messages/'.$foreign->public_id.'/read')->assertNotFound();
    $export = $this->actingAs($user)->get('/dashboard/contact/export');
    $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($export->streamedContent())->toContain("'=FORMULA");
});

it('captures and manages tenant scoped newsletter subscribers with consent and safe export', function () {
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->withHeader('Referer', 'http://'.$hostname.'/?utm_source=launch')
        ->post('http://'.$hostname.'/newsletter/subscriptions', [
            'name' => '<b>=Newsletter Reader</b>',
            'email' => 'READER@EXAMPLE.TEST',
            'consent' => '1',
            'company_website' => '',
        ])->assertRedirect()->assertSessionHas('newsletter_success');

    $subscriber = NewsletterSubscriber::query()->withoutGlobalScope(TenantScope::class)
        ->where('website_id', $website->getKey())->firstOrFail();
    expect($subscriber->name)->toBe('=Newsletter Reader')
        ->and($subscriber->email)->toBe('reader@example.test')
        ->and($subscriber->source_page)->toBe('/')
        ->and($subscriber->consented_at)->not->toBeNull()
        ->and($subscriber->ip_hash)->not->toBeNull();

    $subscriber->update(['unsubscribed_at' => now()]);
    $this->post('http://'.$hostname.'/newsletter/subscriptions', [
        'name' => '=Updated reader', 'email' => 'reader@example.test', 'consent' => true,
        'company_website' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect(NewsletterSubscriber::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(1)
        ->and($subscriber->fresh()->name)->toBe('=Updated reader')
        ->and($subscriber->fresh()->unsubscribed_at)->toBeNull();

    $this->actingAs($user)->get('/dashboard/contact')->assertOk()->assertInertia(fn ($page) => $page
        ->component('contact/index')->has('subscribers.data', 1)->where('subscribers.data.0.email', 'reader@example.test'));
    $this->actingAs($user)->post('/dashboard/contact/subscribers/'.$subscriber->public_id.'/status', [
        'subscribed' => false,
    ])->assertRedirect();
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();

    $export = $this->actingAs($user)->get('/dashboard/contact/subscribers-export');
    $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($export->streamedContent())->toContain("'=Updated reader")->toContain('reader@example.test');
});

it('rejects newsletter signups without consent and blocks honeypot submissions', function () {
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $this->from('http://'.$hostname.'/')->post('http://'.$hostname.'/newsletter/subscriptions', [
        'email' => 'reader@example.test', 'company_website' => '',
    ])->assertSessionHasErrors('consent', null, 'newsletter');
    $this->from('http://'.$hostname.'/')->post('http://'.$hostname.'/newsletter/subscriptions', [
        'email' => 'bot@example.test', 'consent' => true, 'company_website' => 'spam.example',
    ])->assertSessionHasErrors('company_website', null, 'newsletter');
    expect(NewsletterSubscriber::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(0);
});

it('publishes page-aware whatsapp settings and an optional mobile contact bar', function () {
    Queue::fake();
    [$user, $website, $hostname] = phaseEightWorkspace();
    app(ContactSettings::class)->updateWhatsapp($website, whatsappSettingsPayload());
    app(PublishWebsite::class)->execute($website->fresh(), $user);

    $url = app(WhatsappUrl::class)->make('+91 98765 43210', 'Hello from Ezyweb');
    expect($url)->toBe('https://wa.me/919876543210?text=Hello%20from%20Ezyweb');
    $this->get('http://'.$hostname.'/')->assertOk()
        ->assertSee('https://wa.me/919876543210?text=Hello%2C%20I%20visited%20your%20website%20and%20would%20like%20more%20information.', false)
        ->assertSee('aria-label="Quick contact"', false)
        ->assertDontSee('leads@example.test');
});

it('uploads tenant images and creates optimized webp variants on the configured disk', function () {
    Storage::fake('gcs');
    config()->set('media.disk', 'gcs');
    [$user, $website] = phaseEightWorkspace();

    $this->actingAs($user)->post('/dashboard/media', [
        'image' => UploadedFile::fake()->image('Hero Image.JPG', 1600, 900)->size(900),
        'folder' => 'Homepage', 'alt_text' => 'Ezyweb dashboard on a laptop',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $asset = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail();
    expect($asset->mime_type)->toBe('image/webp')->and($asset->variants)->toHaveKeys(['thumbnail', 'small', 'medium', 'large', 'original'])
        ->and($asset->folder)->toBe('Homepage')->and($asset->total_size)->toBeGreaterThan(0);
    foreach ($asset->variants as $variant) {
        Storage::disk('gcs')->assertExists($variant['path']);
    }
    $this->get('/media/'.$asset->public_id.'/thumbnail')->assertOk()->assertHeader('content-type', 'image/webp')->assertHeader('x-content-type-options', 'nosniff');

    $this->actingAs($user)->withHeader('Accept', 'application/json')->post('/dashboard/media', [
        'image' => UploadedFile::fake()->image('Blog inline.png', 900, 600), 'folder' => 'Blog', 'alt_text' => 'Inline article image',
    ])->assertCreated()->assertJsonPath('asset.alt_text', 'Inline article image')->assertJsonPath('asset.title', 'Blog inline');
});

it('blocks unsafe media and enforces website storage limits', function () {
    Storage::fake('local');
    [$user] = phaseEightWorkspace();
    $this->actingAs($user)->post('/dashboard/media', [
        'image' => UploadedFile::fake()->create('payload.svg', 2, 'image/svg+xml'),
    ])->assertSessionHasErrors('image');

    config()->set('media.storage_limit_mb', 0);
    $this->actingAs($user)->post('/dashboard/media', [
        'image' => UploadedFile::fake()->image('valid.png', 800, 600),
    ])->assertSessionHasErrors('image');
    expect(MediaAsset::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(0);
});

it('searches and edits media per tenant and guards images that are in use', function () {
    Storage::fake('local');
    [$user, $website] = phaseEightWorkspace();
    $this->actingAs($user)->post('/dashboard/media', ['image' => UploadedFile::fake()->image('office.jpg', 800, 500), 'title' => 'Office team'])->assertRedirect();
    $asset = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->firstOrFail();
    MediaUsage::query()->create(['media_asset_id' => $asset->getKey(), 'usable_type' => Page::class, 'usable_id' => $website->pages()->firstOrFail()->getKey(), 'field' => 'hero_image']);

    $this->actingAs($user)->get('/dashboard/media?search=Office')->assertOk()->assertInertia(fn ($page) => $page->component('media/index')->has('assets.data', 1));
    $this->actingAs($user)->put('/dashboard/media/'.$asset->public_id, ['folder' => 'Team', 'title' => 'Our team', 'alt_text' => 'Team portrait', 'caption' => ''])->assertRedirect();
    expect($asset->fresh()->folder)->toBe('Team');
    $this->actingAs($user)->delete('/dashboard/media/'.$asset->public_id)->assertSessionHasErrors('media');
    $this->actingAs($user)->delete('/dashboard/media/'.$asset->public_id, ['confirmed_usage' => true])->assertRedirect();
    expect($asset->fresh()->trashed())->toBeTrue();

    [$foreignUser, $foreignWebsite] = phaseEightWorkspace();
    $this->actingAs($foreignUser)->post('/dashboard/media', ['image' => UploadedFile::fake()->image('foreign.jpg', 640, 480)])->assertRedirect();
    $foreign = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $foreignWebsite->getKey())->firstOrFail();
    $this->actingAs($user)->put('/dashboard/media/'.$foreign->public_id, ['folder' => 'Nope'])->assertNotFound();
});

/** @return array{User, Website, string} */
function phaseEightWorkspace(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['contact_email' => 'owner@example.test']);
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey()]);
    $hostname = 'phase8-'.$website->getKey().'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create(['hostname' => $hostname, 'subdomain' => 'phase8-'.$website->getKey(), 'is_primary' => true]);
    $page = Page::factory()->create(['tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'is_home' => true, 'slug' => 'home']);
    PageSection::factory()->create([
        'tenant_id' => $tenant->getKey(), 'website_id' => $website->getKey(), 'page_id' => $page->getKey(),
        'type' => 'contact', 'anchor' => 'contact', 'content' => ['heading' => 'Contact us', 'description' => 'Send an enquiry.'],
    ]);

    return [$user, $website, $hostname];
}

/** @param array<string, mixed> $overrides @return array<string, mixed> */
function contactSettingsPayload(array $overrides = []): array
{
    return [...[
        'heading' => 'Contact us', 'description' => 'Tell us about your project.', 'address' => 'Kolkata, India',
        'phone_numbers_text' => '+91 98765 43210', 'email_addresses_text' => 'private@example.test', 'working_hours' => 'Mon–Fri, 9–5',
        'maps_embed_url' => '', 'success_message' => 'Thanks for contacting us.', 'recipient_email' => 'leads@example.test',
        'whatsapp_number' => '+91 98765 43210', 'is_form_enabled' => true,
        'form_fields' => ['name' => true, 'email' => true, 'phone' => true, 'subject' => true, 'message' => true],
    ], ...$overrides];
}

/** @return array<string, mixed> */
function whatsappSettingsPayload(): array
{
    return [
        'is_enabled' => true, 'phone_number' => '+91 98765 43210',
        'default_message' => 'Hello, I visited your website and would like more information.',
        'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true, 'position' => 'right',
        'bottom_offset' => 24, 'display_delay' => 0, 'pulse_animation' => true, 'show_label' => true,
        'included_pages_text' => '/', 'mobile_sticky_bar' => true, 'call_number' => '+91 98765 43210',
        'directions_url' => 'https://maps.google.com/?q=Kolkata',
    ];
}
