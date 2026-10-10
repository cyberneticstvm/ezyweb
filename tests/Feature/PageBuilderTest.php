<?php

use App\Builder\SectionRegistry;
use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Enums\WebsiteType;
use App\Models\ContentPost;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\JobOpening;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\TemplateSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('registers every required page section with multiple controlled variants', function () {
    $registry = app(SectionRegistry::class)->all();

    expect($registry)->toHaveCount(30);
    foreach ($registry as $definition) {
        expect(count($definition['variants']))->toBeGreaterThanOrEqual(2)->and($definition['fields'])->not->toBeEmpty();
    }
});

it('shows the tenant scoped responsive builder workspace', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $page->getKey(),
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->component('builder/index')
        ->where('builderWebsite.id', $website->getKey())
        ->has('registry', 30)
        ->has('pages', 1)
        ->has('pages.0.sections', 1)
        ->has('theme.primary'));
});

it('creates unique pages for multipage websites and blocks extra landing pages', function () {
    [$user, $website] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/pages', ['title' => 'Contact us'])->assertRedirect();
    expect(Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->where('slug', 'contact-us')->exists())->toBeTrue();

    $this->actingAs($user)->post('/dashboard/builder/pages', ['title' => 'Another', 'slug' => 'contact-us'])->assertSessionHasErrors('slug');
    $this->actingAs($user)->post('/dashboard/builder/pages', ['title' => 'Admin', 'slug' => 'admin'])->assertSessionHasErrors('slug');

    [$landingUser] = pageBuilderWorkspace(['type' => WebsiteType::LandingPage]);
    $this->actingAs($landingUser)->post('/dashboard/builder/pages', ['title' => 'Not allowed'])->assertSessionHasErrors('title');
});

it('adds and updates a draft section with safe normalized content and visibility', function () {
    [$user, $website, $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'hero'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->firstOrFail();

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), [
        'variant' => 'split',
        'anchor' => 'welcome',
        'content' => [
            'eyebrow' => 'Hello',
            'heading' => '<script>alert(1)</script>Safe heading',
            'description' => '<b>Plain text only</b>',
            'primary_label' => 'Learn more',
            'primary_url' => 'javascript:alert(1)',
            'raw_css' => 'body { display:none }',
        ],
        'design_settings' => ['surface' => 'primary', 'spacing' => 'spacious', 'alignment' => 'center'],
        'is_enabled' => false,
        'show_on_desktop' => true,
        'show_on_tablet' => false,
        'show_on_mobile' => false,
    ])->assertRedirect();

    $fresh = $section->fresh();
    expect($fresh?->variant)->toBe('split')
        ->and($fresh?->is_enabled)->toBeFalse()
        ->and($fresh?->show_on_mobile)->toBeFalse()
        ->and($fresh?->content['heading'])->not->toContain('<script>')
        ->and($fresh?->content['primary_url'])->toBe('')
        ->and($fresh?->content)->not->toHaveKey('raw_css')
        ->and($website->fresh()?->draft_revision)->toBe(2)
        ->and(PageSection::query()->withoutGlobalScopes()->whereKey($section->getKey())->exists())->toBeTrue();
});

it('manages hero copy calls to action and responsive tenant scoped images', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $desktop = sliderMedia($website, 'Hero desktop');
    $mobile = sliderMedia($website, 'Hero mobile');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign hero');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'hero'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'hero')->firstOrFail();
    $payload = [
        'variant' => 'editorial', 'anchor' => 'welcome',
        'content' => [
            'eyebrow' => '<b>Built for growth</b>', 'heading' => '<script>bad()</script>A managed hero',
            'description' => '<i>Clear value proposition</i>',
            'primary_label' => 'Get started', 'primary_url' => '/contact',
            'secondary_label' => 'Learn more', 'secondary_url' => 'https://ezyweb.in/about',
            'image_id' => $desktop->public_id, 'mobile_image_id' => $mobile->public_id,
            'image_alt' => '<b>Business owner working</b>', 'image_position' => 'left', 'image_fit' => 'contain',
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'spacious', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()A managed hero')
        ->and($fresh?->content['description'])->toBe('Clear value proposition')
        ->and($fresh?->content['secondary_url'])->toBe('https://ezyweb.in/about')
        ->and($fresh?->content['image_position'])->toBe('left')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'hero_images')->count())->toBe(2);

    $payload['content']['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');
});

it('manages conversion call to action copy links reassurance and responsive tenant images', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $desktop = sliderMedia($website, 'CTA desktop');
    $mobile = sliderMedia($website, 'CTA mobile');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign CTA');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'cta'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'cta')->firstOrFail();
    $payload = [
        'variant' => 'split', 'anchor' => 'book-now',
        'content' => [
            'eyebrow' => '<b>Start today</b>', 'heading' => '<script>bad()</script>Ready to grow?',
            'description' => '<i>Tell visitors exactly what happens next.</i>',
            'primary_label' => 'Book a call', 'primary_url' => '/contact', 'primary_new_tab' => false,
            'secondary_label' => 'See pricing', 'secondary_url' => 'https://ezyweb.in/pricing', 'secondary_new_tab' => true,
            'supporting_text' => '<b>No obligation.</b>',
            'image_id' => $desktop->public_id, 'mobile_image_id' => $mobile->public_id,
            'image_alt' => '<i>Customer speaking with our team</i>', 'media_layout' => 'background',
            'image_fit' => 'contain', 'overlay_strength' => 70,
        ],
        'design_settings' => ['surface' => 'dark', 'spacing' => 'spacious', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Ready to grow?')
        ->and($fresh?->content['description'])->toBe('Tell visitors exactly what happens next.')
        ->and($fresh?->content['secondary_new_tab'])->toBe('1')
        ->and($fresh?->content['supporting_text'])->toBe('No obligation.')
        ->and($fresh?->content['media_layout'])->toBe('background')
        ->and($fresh?->content['overlay_strength'])->toBe('70')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'cta_images')->count())->toBe(2);

    $payload['content']['primary_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertRedirect();
    expect($section->fresh()?->content['primary_url'])->toBe('');

    $payload['content']['primary_url'] = '/contact';
    $payload['content']['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');
});

it('manages feature cards icons images links highlights and presentation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $image = sliderMedia($website, 'Feature artwork');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign feature artwork');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'features'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'features')->firstOrFail();
    $payload = [
        'variant' => 'icon-grid', 'anchor' => 'capabilities',
        'content' => [
            'eyebrow' => '<b>Built for business</b>', 'heading' => '<script>bad()</script>Useful features',
            'description' => '<i>Benefits customers can understand.</i>',
            'feature_items' => [
                ['icon' => '⚡', 'image_id' => $image->public_id, 'image_alt' => '<b>Fast workflow</b>', 'title' => '<i>Fast setup</i>', 'description' => '<script>bad()</script>Launch quickly.', 'link_label' => 'Learn more', 'link_url' => '/features/fast', 'is_featured' => true],
                ['icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Reliable support', 'description' => 'Get useful help.', 'link_label' => 'Contact us', 'link_url' => 'mailto:hello@ezyweb.in', 'is_featured' => false],
            ],
            'columns' => '4', 'show_icons' => true, 'show_images' => true, 'show_descriptions' => true,
            'show_links' => true, 'open_new_tab' => true, 'image_fit' => 'contain',
            'card_alignment' => 'center', 'featured_label' => '<b>Recommended</b>',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Useful features')
        ->and($fresh?->content['feature_items'])->toHaveCount(2)
        ->and($fresh?->content['feature_items'][0]['title'])->toBe('Fast setup')
        ->and($fresh?->content['feature_items'][0]['description'])->toBe('bad()Launch quickly.')
        ->and($fresh?->content['feature_items'][0]['is_featured'])->toBe('1')
        ->and($fresh?->content['columns'])->toBe('4')
        ->and($fresh?->content['card_alignment'])->toBe('center')
        ->and($fresh?->content['featured_label'])->toBe('Recommended')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'feature_images')->count())->toBe(1);

    $payload['content']['feature_items'][0]['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.feature_items');
});

it('manages ordered process steps media notes links and presentation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $image = sliderMedia($website, 'Discovery step');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign process image');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'process'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'process')->firstOrFail();
    $payload = [
        'variant' => 'timeline', 'anchor' => 'how-it-works',
        'content' => [
            'eyebrow' => '<b>Our approach</b>', 'heading' => '<script>bad()</script>How we deliver',
            'description' => '<i>A predictable journey.</i>',
            'process_items' => [
                ['number' => '01', 'icon' => '✦', 'image_id' => $image->public_id, 'image_alt' => '<b>Discovery workshop</b>', 'title' => '<i>Discover</i>', 'description' => '<script>bad()</script>Understand the goal.', 'meta' => '1–2 days', 'link_label' => 'Our method', 'link_url' => '/method'],
                ['number' => '', 'icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Deliver', 'description' => 'Launch the outcome.', 'meta' => 'Final step', 'link_label' => '', 'link_url' => ''],
            ],
            'process_columns' => '4', 'show_step_numbers' => true, 'show_step_icons' => true,
            'show_step_images' => true, 'show_step_descriptions' => true, 'show_step_meta' => true,
            'show_step_links' => true, 'open_new_tab' => true, 'image_fit' => 'contain',
            'process_alignment' => 'center', 'process_connector' => 'dashed',
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()How we deliver')
        ->and($fresh?->content['process_items'])->toHaveCount(2)
        ->and($fresh?->content['process_items'][0]['title'])->toBe('Discover')
        ->and($fresh?->content['process_items'][0]['description'])->toBe('bad()Understand the goal.')
        ->and($fresh?->content['process_items'][1]['number'])->toBe('02')
        ->and($fresh?->content['process_columns'])->toBe('4')
        ->and($fresh?->content['process_connector'])->toBe('dashed')
        ->and($fresh?->content['process_alignment'])->toBe('center')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'process_images')->count())->toBe(1);

    $payload['content']['process_items'][0]['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.process_items');
});

it('manages pricing plans benefits highlights calls to action and presentation controls', function () {
    [$user, , $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'pricing'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'pricing')->firstOrFail();
    $payload = [
        'variant' => 'comparison', 'anchor' => 'pricing',
        'content' => [
            'eyebrow' => '<b>Simple pricing</b>', 'heading' => '<script>bad()</script>Choose your plan',
            'description' => '<i>Transparent packages for every stage.</i>',
            'pricing_items' => [
                ['name' => '<b>Starter</b>', 'badge' => '', 'description' => '<i>For new businesses.</i>', 'currency' => '₹', 'price' => '999', 'original_price' => '1,299', 'period' => '/ month', 'features' => "+ One website\n- Priority support\n<script>bad()</script>", 'button_label' => 'Start today', 'button_url' => '/contact', 'is_featured' => false],
                ['name' => 'Growth', 'badge' => '<b>Most popular</b>', 'description' => 'For growing teams.', 'currency' => '₹', 'price' => '2,499', 'original_price' => '', 'period' => '/ month', 'features' => "+ Five websites\n+ Priority support", 'button_label' => 'Choose Growth', 'button_url' => 'https://ezyweb.in/contact', 'is_featured' => true],
            ],
            'pricing_columns' => '4', 'currency_position' => 'after', 'show_plan_descriptions' => true,
            'show_plan_features' => true, 'show_plan_buttons' => true, 'show_plan_badges' => true,
            'show_original_prices' => false, 'open_new_tab' => true,
            'pricing_note' => '<b>Taxes may apply.</b>',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Choose your plan')
        ->and($fresh?->content['pricing_items'])->toHaveCount(2)
        ->and($fresh?->content['pricing_items'][0]['name'])->toBe('Starter')
        ->and($fresh?->content['pricing_items'][0]['description'])->toBe('For new businesses.')
        ->and($fresh?->content['pricing_items'][0]['features'])->toContain('bad()')
        ->and($fresh?->content['pricing_items'][0]['features'])->not->toContain('<script>')
        ->and($fresh?->content['pricing_items'][1]['badge'])->toBe('Most popular')
        ->and($fresh?->content['pricing_items'][1]['is_featured'])->toBe('1')
        ->and($fresh?->content['pricing_columns'])->toBe('4')
        ->and($fresh?->content['currency_position'])->toBe('after')
        ->and($fresh?->content['show_original_prices'])->toBe('0')
        ->and($fresh?->content['pricing_note'])->toBe('Taxes may apply.');

    $payload['content']['pricing_items'][0]['button_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.pricing_items.0.button_url');
});

it('manages contact copy detail visibility form labels placeholders and presentation controls', function () {
    [$user, , $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'contact'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'contact')->firstOrFail();
    $payload = [
        'variant' => 'map-hours', 'anchor' => 'contact',
        'content' => [
            'eyebrow' => '<b>Reach us</b>', 'heading' => '<script>bad()</script>Let’s talk',
            'description' => '<i>We would love to hear from you.</i>', 'details_heading' => '<b>Visit or call</b>',
            'address_label' => 'Studio', 'phone_label' => 'Call', 'email_label' => 'Write', 'hours_label' => 'Open',
            'map_title' => '<i>Map to our studio</i>', 'form_heading' => '<b>Start your enquiry</b>',
            'form_description' => '<script>bad()</script>Share a few details.',
            'name_label' => 'Your name', 'name_placeholder' => 'Jane Doe',
            'email_field_label' => 'Work email', 'email_placeholder' => 'jane@example.test',
            'phone_field_label' => 'Mobile', 'phone_placeholder' => '+91…',
            'subject_label' => 'Topic', 'subject_placeholder' => 'Website redesign',
            'message_label' => 'Project details', 'message_placeholder' => 'What would you like to build?',
            'submit_label' => '<b>Send request</b>', 'privacy_note' => '<i>We only use this to reply.</i>',
            'show_details_heading' => false, 'show_address' => true, 'show_phone_numbers' => false,
            'show_email_addresses' => true, 'show_working_hours' => true, 'show_map' => true,
            'show_form' => true, 'show_form_heading' => true, 'show_form_description' => false,
            'form_position' => 'left', 'map_height' => 'large',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['eyebrow'])->toBe('Reach us')
        ->and($fresh?->content['heading'])->toBe('bad()Let’s talk')
        ->and($fresh?->content['details_heading'])->toBe('Visit or call')
        ->and($fresh?->content['form_description'])->toBe('bad()Share a few details.')
        ->and($fresh?->content['submit_label'])->toBe('Send request')
        ->and($fresh?->content['privacy_note'])->toBe('We only use this to reply.')
        ->and($fresh?->content['show_details_heading'])->toBe('0')
        ->and($fresh?->content['show_phone_numbers'])->toBe('0')
        ->and($fresh?->content['show_form_description'])->toBe('0')
        ->and($fresh?->content['form_position'])->toBe('left')
        ->and($fresh?->content['map_height'])->toBe('large');

    $payload['content']['map_height'] = 'viewport';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.map_height');
});

it('manages map copy location details actions and responsive presentation controls', function () {
    [$user, , $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'map'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'map')->firstOrFail();
    $payload = [
        'variant' => 'split', 'anchor' => 'find-us',
        'content' => [
            'eyebrow' => '<b>Find us</b>', 'heading' => '<script>bad()</script>Visit our studio',
            'description' => '<i>Plan your visit.</i>', 'location_heading' => '<b>Ezyweb Studio</b>',
            'address_label' => 'Location', 'hours_label' => 'Open',
            'location_note' => '<script>bad()</script>Accessible parking is available.',
            'map_title' => '<i>Directions to Ezyweb Studio</i>', 'directions_label' => '<b>Open directions</b>',
            'directions_url' => 'https://maps.google.com/?q=Ezyweb', 'contact_label' => '<b>Call ahead</b>',
            'contact_url' => 'tel:+919876543210', 'show_location_details' => true, 'show_address' => true,
            'show_working_hours' => false, 'show_map' => true, 'show_directions_button' => true,
            'show_contact_button' => true, 'open_new_tab' => false, 'map_position' => 'left',
            'map_height' => 'large',
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Visit our studio')
        ->and($fresh?->content['location_heading'])->toBe('Ezyweb Studio')
        ->and($fresh?->content['location_note'])->toBe('bad()Accessible parking is available.')
        ->and($fresh?->content['map_title'])->toBe('Directions to Ezyweb Studio')
        ->and($fresh?->content['directions_label'])->toBe('Open directions')
        ->and($fresh?->content['contact_label'])->toBe('Call ahead')
        ->and($fresh?->content['show_working_hours'])->toBe('0')
        ->and($fresh?->content['show_contact_button'])->toBe('1')
        ->and($fresh?->content['open_new_tab'])->toBe('0')
        ->and($fresh?->content['map_position'])->toBe('left')
        ->and($fresh?->content['map_height'])->toBe('large');

    $payload['content']['directions_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.directions_url');
});

it('manages formatted about copy responsive images captions and placement', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $desktop = sliderMedia($website, 'About desktop');
    $mobile = sliderMedia($website, 'About mobile');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign about');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'about'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'about')->firstOrFail();
    $payload = [
        'variant' => 'image-left', 'anchor' => 'about-us',
        'content' => [
            'eyebrow' => '<b>About Ezyweb</b>', 'heading' => '<script>bad()</script>Our company story',
            'body' => '<p>Built with <strong>purpose</strong>.</p><script>alert(1)</script>',
            'primary_label' => 'Meet our team', 'primary_url' => '/team',
            'image_id' => $desktop->public_id, 'mobile_image_id' => $mobile->public_id,
            'image_alt' => '<i>Ezyweb team</i>', 'image_caption' => '<b>Together since 2024</b>',
            'image_position' => 'right', 'image_fit' => 'contain',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Our company story')
        ->and($fresh?->content['body'])->toContain('<strong>purpose</strong>')
        ->and($fresh?->content['body'])->not->toContain('<script>')
        ->and($fresh?->content['image_caption'])->toBe('Together since 2024')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'about_images')->count())->toBe(2);

    $payload['content']['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');
});

it('manages story mission and vision copy with tenant scoped responsive images', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $story = sliderMedia($website, 'Story desktop');
    $storyMobile = sliderMedia($website, 'Story mobile');
    $mission = sliderMedia($website, 'Mission image');
    $vision = sliderMedia($website, 'Vision image');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign purpose image');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'mission-vision'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'mission-vision')->firstOrFail();
    $payload = [
        'variant' => 'cards', 'anchor' => 'our-purpose',
        'content' => [
            'eyebrow' => '<b>Our purpose</b>', 'heading' => '<script>bad()</script>What guides Ezyweb',
            'story_heading' => 'Our story', 'story_body' => '<p>A <strong>bold beginning</strong>.</p><script>bad()</script>',
            'story_image_id' => $story->public_id, 'story_mobile_image_id' => $storyMobile->public_id,
            'story_image_alt' => '<i>Our founders</i>', 'story_image_caption' => '<b>Where it began</b>',
            'mission_heading' => 'Our mission', 'mission_body' => '<p>Make the web <em>accessible</em>.</p>',
            'mission_image_id' => $mission->public_id, 'mission_mobile_image_id' => '',
            'mission_image_alt' => 'Team delivering', 'mission_image_caption' => '',
            'vision_heading' => 'Our vision', 'vision_body' => '<p>A better digital future.</p>',
            'vision_image_id' => $vision->public_id, 'vision_mobile_image_id' => '',
            'vision_image_alt' => 'Looking forward', 'vision_image_caption' => 'The future',
            'image_position' => 'right', 'image_fit' => 'contain',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['eyebrow'])->toBe('Our purpose')
        ->and($fresh?->content['heading'])->toBe('bad()What guides Ezyweb')
        ->and($fresh?->content['story_body'])->toContain('<strong>bold beginning</strong>')
        ->and($fresh?->content['story_body'])->not->toContain('<script>')
        ->and($fresh?->content['mission_body'])->toContain('<em>accessible</em>')
        ->and($fresh?->content['story_image_alt'])->toBe('Our founders')
        ->and($fresh?->content['story_image_caption'])->toBe('Where it began')
        ->and($fresh?->content['image_position'])->toBe('right')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'mission_vision_images')->count())->toBe(4);

    $payload['content']['story_image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.story_image_id');
});

it('manages client and project counters with formatting and animation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'counters'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'counters')->firstOrFail();
    $payload = [
        'variant' => 'band', 'anchor' => 'our-impact',
        'content' => [
            'eyebrow' => '<b>Our impact</b>',
            'heading' => '<script>bad()</script>Results that build trust',
            'description' => '<i>Measured outcomes for our clients.</i>',
            'items' => [
                ['value' => 1250, 'prefix' => '', 'suffix' => '+', 'label' => '<b>Happy clients</b>', 'description' => '<i>Across growing businesses</i>'],
                ['value' => 3480, 'prefix' => '', 'suffix' => '+', 'label' => 'Projects delivered', 'description' => 'Websites and campaigns'],
                ['value' => 12, 'prefix' => '', 'suffix' => ' yrs', 'label' => 'Experience', 'description' => ''],
            ],
            'animate' => false, 'duration' => 4, 'use_grouping' => true,
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'center'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Results that build trust')
        ->and($fresh?->content['description'])->toBe('Measured outcomes for our clients.')
        ->and($fresh?->content['items'])->toHaveCount(3)
        ->and($fresh?->content['items'][0])->toMatchArray([
            'value' => '1250', 'suffix' => '+', 'label' => 'Happy clients', 'description' => 'Across growing businesses',
        ])
        ->and($fresh?->content['animate'])->toBe('0')
        ->and($fresh?->content['duration'])->toBe('4')
        ->and($fresh?->content['use_grouping'])->toBe('1');

    $payload['content']['items'][0]['value'] = -1;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.items.0.value');
});

it('manages spacer and divider spacing labels and line appearance safely', function () {
    [$user, , $page] = pageBuilderWorkspace();
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'spacer-divider'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'spacer-divider')->firstOrFail();
    expect($section->content)->toMatchArray(['space_before' => '48', 'space_after' => '48', 'line_width' => 'full']);

    $payload = [
        'variant' => 'line', 'anchor' => 'next-chapter',
        'content' => [
            'heading' => '<b>Section break</b>', 'space_before' => '16', 'space_after' => '96',
            'line_label' => '<script>bad()</script>Next chapter', 'line_width' => 'medium',
            'line_weight' => '3', 'line_style' => 'dashed', 'line_color' => 'accent',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'right'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'heading' => 'Section break', 'space_before' => '16', 'space_after' => '96',
        'line_label' => 'bad()Next chapter', 'line_width' => 'medium', 'line_weight' => '3',
        'line_style' => 'dashed', 'line_color' => 'accent',
    ]);

    $payload['content']['space_before'] = '999';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.space_before');
    $payload['content']['space_before'] = '16';
    $payload['content']['line_color'] = 'url(evil)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.line_color');
});

it('manages decimal statistics source notes comparisons and display controls', function () {
    [$user, , $page] = pageBuilderWorkspace();

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'statistics'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'statistics')->firstOrFail();
    expect($section->content['stats'])->toBe([]);

    $payload = [
        'variant' => 'split', 'anchor' => 'our-results',
        'content' => [
            'eyebrow' => '<b>Evidence</b>', 'heading' => '<i>Results in numbers</i>',
            'description' => '<script>bad()</script>Verified outcomes.',
            'stats' => [
                ['value' => '12345.50', 'prefix' => '₹', 'suffix' => '+', 'label' => '<b>Revenue</b>', 'description' => '<i>Annual client impact</i>', 'trend_text' => '<b>12% year over year</b>', 'trend_direction' => 'up', 'source_label' => '<i>Annual report</i>', 'source_url' => '/reports/2025'],
                ['value' => '98.7', 'prefix' => '', 'suffix' => '%', 'label' => 'Satisfaction', 'description' => 'Customer survey', 'trend_text' => 'Stable', 'trend_direction' => 'neutral', 'source_label' => 'Client survey', 'source_url' => 'https://example.com/survey'],
            ],
            'stats_columns' => '3', 'highlight_first' => true, 'show_descriptions' => true,
            'show_trends' => true, 'show_sources' => true, 'animate' => false,
            'duration' => 4, 'use_grouping' => true,
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'eyebrow' => 'Evidence', 'heading' => 'Results in numbers', 'description' => 'bad()Verified outcomes.',
        'stats_columns' => '3', 'highlight_first' => '1', 'animate' => '0', 'duration' => '4',
    ]);
    expect($section->fresh()?->content['stats'])->toHaveCount(2)
        ->and($section->fresh()?->content['stats'][0])->toMatchArray([
            'value' => '12345.50', 'prefix' => '₹', 'label' => 'Revenue', 'description' => 'Annual client impact',
            'trend_text' => '12% year over year', 'trend_direction' => 'up', 'source_label' => 'Annual report', 'source_url' => '/reports/2025',
        ]);

    $payload['content']['stats'][0]['value'] = '12.345';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.stats.0.value');
    $payload['content']['stats'][0]['value'] = '12345.50';
    $payload['content']['stats'][0]['trend_direction'] = 'sideways';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.stats.0.trend_direction');
});

it('manages tenant scoped brand and partner logos with carousel controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $first = sliderMedia($website, 'First partner');
    $second = sliderMedia($website, 'Second partner');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign partner');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'brand-carousel'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'brand-carousel')->firstOrFail();
    $payload = [
        'variant' => 'carousel', 'anchor' => 'our-partners',
        'content' => [
            'eyebrow' => '<b>Trusted by</b>', 'heading' => '<script>bad()</script>Our partners',
            'description' => '<i>Organisations working with Ezyweb.</i>',
            'logos' => [
                ['image_id' => $first->public_id, 'name' => '<b>Alpha Co</b>', 'alt' => '<i>Alpha logo</i>', 'url' => 'https://alpha.example'],
                ['image_id' => $second->public_id, 'name' => 'Beta Co', 'alt' => 'Beta logo', 'url' => '/partners/beta'],
            ],
            'autoplay' => true, 'interval' => 6, 'show_arrows' => false, 'pause_on_hover' => true,
            'show_names' => true, 'grayscale' => true, 'open_new_tab' => true,
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'center'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Our partners')
        ->and($fresh?->content['description'])->toBe('Organisations working with Ezyweb.')
        ->and($fresh?->content['logos'])->toHaveCount(2)
        ->and($fresh?->content['logos'][0])->toMatchArray([
            'image_id' => $first->public_id, 'name' => 'Alpha Co', 'alt' => 'Alpha logo', 'url' => 'https://alpha.example',
        ])
        ->and($fresh?->content['interval'])->toBe('6')
        ->and($fresh?->content['show_arrows'])->toBe('0')
        ->and($fresh?->content['show_names'])->toBe('1')
        ->and($fresh?->content['grayscale'])->toBe('1')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'brand_carousel_logos')->count())->toBe(2);

    $payload['content']['logos'][0]['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.logos');
});

it('manages a tenant scoped logo cloud with layout and display controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $first = sliderMedia($website, 'First client logo');
    $second = sliderMedia($website, 'Second client logo');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign client logo');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'logo-cloud'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'logo-cloud')->firstOrFail();
    $payload = [
        'variant' => 'grid', 'anchor' => 'clients',
        'content' => [
            'eyebrow' => '<b>Trusted by</b>', 'heading' => '<i>Our clients</i>',
            'description' => '<script>bad()</script>Companies we have helped.',
            'logos' => [
                ['image_id' => $first->public_id, 'name' => '<b>Alpha</b>', 'alt' => '<i>Alpha logo</i>', 'url' => 'https://alpha.example'],
                ['image_id' => $second->public_id, 'name' => 'Beta', 'alt' => 'Beta logo', 'url' => '/partners/beta'],
            ],
            'logo_columns' => '5', 'logo_size' => 'large', 'logo_spacing' => 'loose',
            'show_cards' => true, 'show_names' => true, 'grayscale' => true, 'open_new_tab' => true,
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'center'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'eyebrow' => 'Trusted by', 'heading' => 'Our clients', 'description' => 'bad()Companies we have helped.',
        'logo_columns' => '5', 'logo_size' => 'large', 'logo_spacing' => 'loose',
        'show_cards' => '1', 'show_names' => '1', 'grayscale' => '1', 'open_new_tab' => '1',
    ]);
    expect($section->fresh()?->content['logos'])->toHaveCount(2)
        ->and($section->fresh()?->content['logos'][0])->toMatchArray(['image_id' => $first->public_id, 'name' => 'Alpha', 'alt' => 'Alpha logo', 'url' => 'https://alpha.example'])
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'logo_cloud_logos')->count())->toBe(2);

    $payload['content']['logos'][0]['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.logos');

    $payload['content']['logos'][0]['image_id'] = $first->public_id;
    $payload['content']['logo_columns'] = '7';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertSessionHasErrors('content.logo_columns');
});

it('manages product catalogue selection sorting and presentation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $first = Product::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Starter Kit', 'slug' => 'starter-kit', 'is_featured' => true, 'display_order' => 1]);
    Product::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Growth Kit', 'slug' => 'growth-kit', 'display_order' => 2]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('productOptions', 2)
        ->where('productOptions.0.slug', $first->slug));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'products'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'products')->firstOrFail();
    $payload = [
        'variant' => 'featured', 'anchor' => 'catalogue',
        'content' => [
            'eyebrow' => '<b>Our products</b>', 'heading' => '<script>bad()</script>Explore the catalogue',
            'description' => '<i>Choose the right package.</i>',
            'selection_mode' => 'selected', 'product_slugs' => 'starter-kit, <b>growth-kit</b>,starter-kit',
            'featured_only' => false, 'limit' => 8, 'sort' => 'price-high', 'columns' => '4',
            'show_images' => true, 'show_descriptions' => false, 'show_prices' => true,
            'price_prefix' => '₹', 'show_featured_badge' => true, 'featured_label' => '<b>Popular</b>',
            'show_button' => true, 'button_label' => 'Request details', 'button_url' => '/contact', 'image_fit' => 'contain',
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Explore the catalogue')
        ->and($fresh?->content['description'])->toBe('Choose the right package.')
        ->and($fresh?->content['selection_mode'])->toBe('selected')
        ->and($fresh?->content['product_slugs'])->toBe('starter-kit,growth-kit')
        ->and($fresh?->content['sort'])->toBe('price-high')
        ->and($fresh?->content['columns'])->toBe('4')
        ->and($fresh?->content['show_descriptions'])->toBe('0')
        ->and($fresh?->content['featured_label'])->toBe('Popular')
        ->and($fresh?->content['button_url'])->toBe('/contact')
        ->and($fresh?->content['image_fit'])->toBe('contain');
});

it('manages testimonial presentation and carousel controls from tenant content', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    Testimonial::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'client_name' => 'Anita Rao', 'is_featured' => true,
    ]);
    Testimonial::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'client_name' => 'Rahul Shah', 'is_active' => false,
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('testimonialOptions', 2)
        ->where('testimonialOptions.0.client_name', 'Anita Rao'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'testimonials'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'testimonials')->firstOrFail();
    $payload = [
        'variant' => 'carousel', 'anchor' => 'testimonials',
        'content' => [
            'eyebrow' => '<b>Client stories</b>', 'heading' => '<script>bad()</script>What clients say',
            'description' => '<i>Feedback from real partnerships.</i>',
            'testimonial_featured_only' => true, 'testimonial_limit' => 8, 'testimonial_sort' => 'rating-high',
            'show_photos' => true, 'show_roles' => false, 'show_companies' => true, 'show_ratings' => true,
            'autoplay' => true, 'interval' => 8, 'show_arrows' => true, 'show_dots' => false,
            'pause_on_hover' => true, 'slides_per_view' => '2',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()What clients say')
        ->and($fresh?->content['description'])->toBe('Feedback from real partnerships.')
        ->and($fresh?->content['testimonial_featured_only'])->toBe('1')
        ->and($fresh?->content['testimonial_limit'])->toBe('8')
        ->and($fresh?->content['testimonial_sort'])->toBe('rating-high')
        ->and($fresh?->content['show_roles'])->toBe('0')
        ->and($fresh?->content['show_dots'])->toBe('0')
        ->and($fresh?->content['interval'])->toBe('8')
        ->and($fresh?->content['slides_per_view'])->toBe('2');
});

it('manages portfolio project selection filters sorting and presentation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $category = PortfolioCategory::query()->withoutGlobalScopes()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Web design', 'slug' => 'web-design',
    ]);
    PortfolioProject::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'portfolio_category_id' => $category->getKey(), 'title' => 'Ezy Store', 'slug' => 'ezy-store', 'is_featured' => true,
    ]);
    PortfolioProject::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Inactive work', 'slug' => 'inactive-work', 'is_active' => false,
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('portfolioOptions', 2)
        ->where('portfolioOptions.0.slug', 'ezy-store')
        ->where('portfolioOptions.0.category', 'Web design'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'portfolio'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'portfolio')->firstOrFail();
    $payload = [
        'variant' => 'masonry', 'anchor' => 'our-work',
        'content' => [
            'eyebrow' => '<b>Our work</b>', 'heading' => '<script>bad()</script>Selected projects',
            'description' => '<i>Results for ambitious clients.</i>',
            'portfolio_selection_mode' => 'selected', 'portfolio_slugs' => 'ezy-store, <b>inactive-work</b>,ezy-store',
            'portfolio_category' => 'Web design', 'portfolio_featured_only' => true,
            'portfolio_limit' => 8, 'portfolio_sort' => 'newest', 'portfolio_columns' => '4',
            'show_images' => true, 'show_descriptions' => false, 'show_categories' => true,
            'show_clients' => false, 'show_dates' => true, 'show_locations' => true,
            'show_button' => true, 'button_label' => '<b>See case study</b>', 'image_fit' => 'contain', 'open_new_tab' => true,
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Selected projects')
        ->and($fresh?->content['description'])->toBe('Results for ambitious clients.')
        ->and($fresh?->content['portfolio_slugs'])->toBe('ezy-store,inactive-work')
        ->and($fresh?->content['portfolio_category'])->toBe('Web design')
        ->and($fresh?->content['portfolio_featured_only'])->toBe('1')
        ->and($fresh?->content['portfolio_sort'])->toBe('newest')
        ->and($fresh?->content['portfolio_columns'])->toBe('4')
        ->and($fresh?->content['show_descriptions'])->toBe('0')
        ->and($fresh?->content['show_clients'])->toBe('0')
        ->and($fresh?->content['button_label'])->toBe('See case study')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and($fresh?->content['open_new_tab'])->toBe('1');
});

it('manages gallery selection filtering layout details and lightbox controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    GalleryItem::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Launch day', 'image' => 'https://cdn.example/launch.jpg', 'category' => 'Events', 'aspect_ratio' => 'landscape',
    ]);
    GalleryItem::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Studio detail', 'image' => 'https://cdn.example/studio.jpg', 'category' => 'Studio', 'aspect_ratio' => 'portrait',
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('galleryOptions', 2)
        ->where('galleryOptions.0.title', 'Launch day')
        ->where('galleryOptions.0.aspect_ratio', 'landscape'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'gallery'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'gallery')->firstOrFail();
    $payload = [
        'variant' => 'masonry', 'anchor' => 'gallery',
        'content' => [
            'eyebrow' => '<b>Inside our work</b>', 'heading' => '<script>bad()</script>Gallery highlights',
            'description' => '<i>Selected moments and details.</i>',
            'gallery_selection_mode' => 'selected',
            'gallery_images' => "https://cdn.example/launch.jpg\n<b>https://cdn.example/studio.jpg</b>\nhttps://cdn.example/launch.jpg",
            'gallery_category' => 'Events', 'gallery_limit' => 24, 'gallery_sort' => 'title', 'gallery_columns' => '5',
            'show_titles' => true, 'show_captions' => false, 'show_categories' => true,
            'respect_aspect_ratios' => true, 'image_fit' => 'contain', 'gallery_gap' => 'compact',
            'enable_lightbox' => true, 'lightbox_captions' => false,
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Gallery highlights')
        ->and($fresh?->content['description'])->toBe('Selected moments and details.')
        ->and($fresh?->content['gallery_images'])->toBe("https://cdn.example/launch.jpg\nhttps://cdn.example/studio.jpg")
        ->and($fresh?->content['gallery_category'])->toBe('Events')
        ->and($fresh?->content['gallery_limit'])->toBe('24')
        ->and($fresh?->content['gallery_columns'])->toBe('5')
        ->and($fresh?->content['show_captions'])->toBe('0')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and($fresh?->content['gallery_gap'])->toBe('compact')
        ->and($fresh?->content['enable_lightbox'])->toBe('1')
        ->and($fresh?->content['lightbox_captions'])->toBe('0');
});

it('manages team selection role filters profiles contact details and presentation controls', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    TeamMember::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Anita Rao', 'role' => 'Director', 'email' => 'anita@example.com', 'is_featured' => true,
    ]);
    TeamMember::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Rahul Shah', 'role' => 'Designer', 'email' => 'rahul@example.com',
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('teamOptions', 2)
        ->where('teamOptions.0.key', 'anita@example.com')
        ->where('teamOptions.0.is_featured', true));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'team'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'team')->firstOrFail();
    $payload = [
        'variant' => 'compact', 'anchor' => 'our-team',
        'content' => [
            'eyebrow' => '<b>Our people</b>', 'heading' => '<script>bad()</script>Meet the team',
            'description' => '<i>People who make the work possible.</i>',
            'team_selection_mode' => 'selected', 'team_keys' => "anita@example.com\n<b>rahul@example.com</b>\nanita@example.com",
            'team_role' => 'Director', 'team_featured_only' => true, 'team_limit' => 12,
            'team_sort' => 'featured-first', 'team_columns' => '3',
            'show_photos' => true, 'show_roles' => true, 'show_bios' => false,
            'show_email' => true, 'show_phone' => true, 'show_linkedin' => false,
            'image_shape' => 'circle', 'image_fit' => 'contain', 'card_alignment' => 'center',
            'show_featured_badge' => true, 'featured_label' => '<b>Leadership</b>',
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'center'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Meet the team')
        ->and($fresh?->content['description'])->toBe('People who make the work possible.')
        ->and($fresh?->content['team_keys'])->toBe("anita@example.com\nrahul@example.com")
        ->and($fresh?->content['team_role'])->toBe('Director')
        ->and($fresh?->content['team_featured_only'])->toBe('1')
        ->and($fresh?->content['team_sort'])->toBe('featured-first')
        ->and($fresh?->content['team_columns'])->toBe('3')
        ->and($fresh?->content['show_bios'])->toBe('0')
        ->and($fresh?->content['show_email'])->toBe('1')
        ->and($fresh?->content['show_linkedin'])->toBe('0')
        ->and($fresh?->content['image_shape'])->toBe('circle')
        ->and($fresh?->content['card_alignment'])->toBe('center')
        ->and($fresh?->content['featured_label'])->toBe('Leadership');
});

it('manages faq selection categories accordion search and help call to action', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'How quickly can we launch?', 'answer' => 'Most projects launch in a few weeks.', 'category' => 'Delivery',
    ]);
    Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'Do you offer support?', 'answer' => 'Yes.', 'category' => 'Support',
    ]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('faqOptions', 2)
        ->where('faqOptions.0.key', 'How quickly can we launch?')
        ->where('faqOptions.0.category', 'Delivery'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'faq'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'faq')->firstOrFail();
    $payload = [
        'variant' => 'accordion', 'anchor' => 'questions',
        'content' => [
            'eyebrow' => '<b>FAQ</b>', 'heading' => '<script>bad()</script>Helpful answers',
            'description' => '<i>Everything you need to know.</i>',
            'faq_selection_mode' => 'selected',
            'faq_keys' => "How quickly can we launch?\n<b>Do you offer support?</b>\nHow quickly can we launch?",
            'faq_category' => 'Delivery', 'faq_limit' => 20, 'faq_sort' => 'question',
            'show_categories' => true, 'show_numbers' => true, 'default_open' => 'all',
            'allow_multiple' => false, 'enable_search' => true, 'search_placeholder' => '<b>Search help</b>',
            'show_expand_controls' => true, 'show_cta' => true,
            'cta_text' => '<i>Still need help?</i>', 'cta_label' => '<b>Talk to us</b>', 'cta_url' => '/contact',
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['heading'])->toBe('bad()Helpful answers')
        ->and($fresh?->content['description'])->toBe('Everything you need to know.')
        ->and($fresh?->content['faq_keys'])->toBe("How quickly can we launch?\nDo you offer support?")
        ->and($fresh?->content['faq_category'])->toBe('Delivery')
        ->and($fresh?->content['faq_limit'])->toBe('20')
        ->and($fresh?->content['show_numbers'])->toBe('1')
        ->and($fresh?->content['default_open'])->toBe('all')
        ->and($fresh?->content['allow_multiple'])->toBe('0')
        ->and($fresh?->content['enable_search'])->toBe('1')
        ->and($fresh?->content['search_placeholder'])->toBe('Search help')
        ->and($fresh?->content['show_expand_controls'])->toBe('1')
        ->and($fresh?->content['cta_text'])->toBe('Still need help?')
        ->and($fresh?->content['cta_label'])->toBe('Talk to us')
        ->and($fresh?->content['cta_url'])->toBe('/contact');
});

it('manages blog selection filtering layout and article metadata with a builder hint', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog, 'title' => 'Professional article', 'slug' => 'professional-article', 'status' => ContentPostStatus::Published, 'published_at' => now()->subHour(), 'author_name' => 'Ezyweb Team']);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('blogOptions', 1)->where('blogOptions.0.slug', 'professional-article')->where('blogOptions.0.author_name', 'Ezyweb Team'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'blog-posts'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'blog-posts')->firstOrFail();
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), [
        'variant' => 'featured', 'anchor' => 'insights', 'content' => ['heading' => '<b>Insights</b>', 'blog_selection_mode' => 'selected', 'blog_slugs' => "professional-article\n<script>bad</script>\nprofessional-article", 'blog_category' => 'Guides', 'blog_limit' => 99, 'blog_sort' => 'title', 'blog_columns' => 4, 'show_images' => false, 'show_authors' => true, 'show_reading_time' => true, 'read_more_label' => '<b>Continue</b>', 'show_archive_link' => true, 'archive_label' => 'All insights', 'image_fit' => 'contain'],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'], 'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ])->assertRedirect();

    expect($section->fresh()?->content)->toMatchArray(['heading' => 'Insights', 'blog_slugs' => "professional-article\nbad", 'blog_limit' => '24', 'blog_sort' => 'title', 'blog_columns' => '4', 'show_images' => '0', 'show_authors' => '1', 'read_more_label' => 'Continue', 'archive_label' => 'All insights', 'image_fit' => 'contain']);
});

it('manages news selection categories sorting layout and article metadata with a builder hint', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::News, 'title' => 'Company expansion', 'slug' => 'company-expansion', 'status' => ContentPostStatus::Published, 'published_at' => now()->subHour(), 'author_name' => 'News Desk']);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog, 'title' => 'Blog only', 'slug' => 'blog-only', 'status' => ContentPostStatus::Published, 'published_at' => now()->subHour()]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('newsOptions', 1)->where('newsOptions.0.slug', 'company-expansion')->where('newsOptions.0.author_name', 'News Desk'));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'news'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'news')->firstOrFail();
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), [
        'variant' => 'list', 'anchor' => 'newsroom', 'content' => ['eyebrow' => '<b>Newsroom</b>', 'heading' => '<script>bad()</script>Latest updates', 'description' => '<i>Company announcements.</i>', 'news_selection_mode' => 'selected', 'news_slugs' => "company-expansion\n<b>second-update</b>\ncompany-expansion", 'news_category' => 'Announcements', 'news_limit' => 99, 'news_sort' => 'oldest', 'news_columns' => 2, 'show_images' => true, 'show_excerpts' => false, 'show_authors' => true, 'show_dates' => true, 'show_categories' => false, 'show_tags' => true, 'show_reading_time' => true, 'read_more_label' => '<b>Full story</b>', 'show_archive_link' => true, 'archive_label' => '<i>All news</i>', 'image_fit' => 'contain'],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'], 'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ])->assertRedirect();

    expect($section->fresh()?->content)->toMatchArray(['eyebrow' => 'Newsroom', 'heading' => 'bad()Latest updates', 'description' => 'Company announcements.', 'news_slugs' => "company-expansion\nsecond-update", 'news_limit' => '24', 'news_sort' => 'oldest', 'news_columns' => '2', 'show_excerpts' => '0', 'show_categories' => '0', 'show_tags' => '1', 'show_reading_time' => '1', 'read_more_label' => 'Full story', 'archive_label' => 'All news', 'image_fit' => 'contain']);
});

it('manages event selection timeframe scheduling venue and registration presentation with a builder hint', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Event, 'title' => 'Ezyweb workshop', 'slug' => 'ezyweb-workshop', 'status' => ContentPostStatus::Published, 'published_at' => now()->subHour(), 'event_start_date' => now()->addWeek()->toDateString(), 'event_start_time' => '10:00', 'event_end_time' => '13:00', 'venue' => 'Ezyweb Studio', 'registration_url' => 'https://events.example.test/register']);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('eventOptions', 1)->where('eventOptions.0.slug', 'ezyweb-workshop')->where('eventOptions.0.venue', 'Ezyweb Studio')->where('eventOptions.0.is_upcoming', true));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'events'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'events')->firstOrFail();
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), [
        'variant' => 'list', 'anchor' => 'events', 'content' => ['eyebrow' => '<b>Events</b>', 'heading' => '<script>bad()</script>Meet us', 'description' => '<i>Upcoming sessions.</i>', 'event_selection_mode' => 'selected', 'event_slugs' => "ezyweb-workshop\n<b>second-event</b>\nezyweb-workshop", 'event_category' => 'Workshops', 'event_timeframe' => 'past', 'event_limit' => 99, 'event_sort' => 'latest', 'event_columns' => 2, 'show_images' => true, 'show_excerpts' => false, 'show_dates' => true, 'show_times' => false, 'show_venues' => true, 'show_addresses' => true, 'show_categories' => false, 'show_registration' => true, 'registration_label' => '<b>Book a seat</b>', 'show_archive_link' => true, 'archive_label' => '<i>All events</i>', 'image_fit' => 'contain'],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'], 'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ])->assertRedirect();

    expect($section->fresh()?->content)->toMatchArray(['eyebrow' => 'Events', 'heading' => 'bad()Meet us', 'description' => 'Upcoming sessions.', 'event_slugs' => "ezyweb-workshop\nsecond-event", 'event_timeframe' => 'past', 'event_limit' => '24', 'event_sort' => 'latest', 'event_columns' => '2', 'show_excerpts' => '0', 'show_times' => '0', 'show_addresses' => '1', 'show_categories' => '0', 'registration_label' => 'Book a seat', 'archive_label' => 'All events', 'image_fit' => 'contain']);
});

it('manages career selection job filters metadata and application presentation with a builder hint', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    JobOpening::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'Senior Designer', 'slug' => 'senior-designer', 'department' => 'Design', 'location' => 'Bengaluru', 'employment_type' => 'full-time', 'workplace_type' => 'hybrid', 'closing_date' => now()->addMonth()->toDateString(), 'application_email' => 'careers@example.test', 'is_active' => true]);

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('careerOptions', 1)->where('careerOptions.0.slug', 'senior-designer')->where('careerOptions.0.department', 'Design')->where('careerOptions.0.is_open', true));
    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'careers'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'careers')->firstOrFail();
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), [
        'variant' => 'featured', 'anchor' => 'careers', 'content' => ['eyebrow' => '<b>Careers</b>', 'heading' => '<script>bad()</script>Join us', 'description' => '<i>Open roles.</i>', 'career_selection_mode' => 'selected', 'career_slugs' => "senior-designer\n<b>product-manager</b>\nsenior-designer", 'career_department' => 'Design', 'career_location' => 'Bengaluru', 'career_employment_type' => 'full-time', 'career_workplace_type' => 'hybrid', 'career_limit' => 99, 'career_sort' => 'closing-soonest', 'career_columns' => 3, 'show_summaries' => false, 'show_departments' => true, 'show_locations' => true, 'show_employment_types' => false, 'show_workplace_types' => true, 'show_salaries' => false, 'show_closing_dates' => true, 'show_apply_button' => true, 'apply_label' => '<b>Explore role</b>', 'show_archive_link' => true, 'archive_label' => '<i>All openings</i>'],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'], 'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ])->assertRedirect();

    expect($section->fresh()?->content)->toMatchArray(['eyebrow' => 'Careers', 'heading' => 'bad()Join us', 'description' => 'Open roles.', 'career_slugs' => "senior-designer\nproduct-manager", 'career_department' => 'Design', 'career_location' => 'Bengaluru', 'career_employment_type' => 'full-time', 'career_workplace_type' => 'hybrid', 'career_limit' => '50', 'career_sort' => 'closing-soonest', 'career_columns' => '3', 'show_summaries' => '0', 'show_employment_types' => '0', 'show_salaries' => '0', 'apply_label' => 'Explore role', 'archive_label' => 'All openings']);
});

it('manages a functional newsletter signup section with consent and privacy controls', function () {
    [$user, , $page] = pageBuilderWorkspace();
    $this->actingAs($user)->post('/dashboard/builder/sections', [
        'page_id' => $page->getKey(), 'type' => 'newsletter',
    ])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'newsletter')->firstOrFail();
    $payload = [
        'variant' => 'card', 'anchor' => 'updates',
        'content' => [
            'eyebrow' => '<b>Stay informed</b>',
            'heading' => '<script>bad()</script>Ezyweb updates',
            'description' => '<i>Useful news, occasionally.</i>',
            'form_heading' => '<b>Join the list</b>', 'form_description' => '<i>One useful email at a time.</i>',
            'name_label' => '<b>Your name</b>', 'name_placeholder' => '<i>Jane</i>', 'show_name_field' => true,
            'email_field_label' => '<b>Work email</b>', 'email_placeholder' => 'jane@example.test',
            'button_label' => '<b>Keep me posted</b>',
            'consent_label' => '<i>I consent to receive marketing updates.</i>',
            'privacy_label' => '<b>Privacy details</b>', 'privacy_url' => '/privacy',
            'privacy_note' => '<i>You can leave at any time.</i>',
            'success_message' => '<b>You are subscribed.</b>',
            'show_form_heading' => true, 'show_form_description' => true, 'show_privacy_note' => false,
            'form_width' => 'wide', 'form_alignment' => 'center', 'privacy_new_tab' => true,
        ],
        'design_settings' => ['surface' => 'primary', 'spacing' => 'spacious', 'alignment' => 'center'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'eyebrow' => 'Stay informed', 'heading' => 'bad()Ezyweb updates',
        'description' => 'Useful news, occasionally.', 'form_heading' => 'Join the list',
        'form_description' => 'One useful email at a time.', 'name_label' => 'Your name',
        'show_name_field' => '1', 'email_field_label' => 'Work email', 'button_label' => 'Keep me posted',
        'consent_label' => 'I consent to receive marketing updates.', 'privacy_label' => 'Privacy details',
        'privacy_url' => '/privacy', 'privacy_note' => 'You can leave at any time.',
        'success_message' => 'You are subscribed.', 'show_form_heading' => '1',
        'show_form_description' => '1', 'show_privacy_note' => '0', 'form_width' => 'wide',
        'form_alignment' => 'center', 'privacy_new_tab' => '1',
    ]);

    $payload['content']['privacy_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.privacy_url');
});

it('manages tenant scoped hero slider images and normalizes every slide', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $first = sliderMedia($website, 'First image');
    $second = sliderMedia($website, 'Second image');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign image');

    $this->actingAs($user)->get('/dashboard/builder')->assertOk()->assertInertia(fn (Assert $view) => $view
        ->has('mediaAssets', 2)
        ->where('mediaAssets', fn ($assets): bool => collect($assets)->pluck('public_id')->sort()->values()->all() === collect([$first->public_id, $second->public_id])->sort()->values()->all()));

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'hero-slider'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'hero-slider')->firstOrFail();
    $payload = [
        'variant' => 'full-bleed', 'anchor' => 'featured',
        'content' => [
            'slides' => [
                ['image_id' => $first->public_id, 'mobile_image_id' => '', 'alt' => '<b>Opening image</b>', 'eyebrow' => 'Welcome', 'heading' => '<script>bad()</script>First slide', 'description' => 'First description', 'primary_label' => 'Explore', 'primary_url' => '/events'],
                ['image_id' => $second->public_id, 'mobile_image_id' => $first->public_id, 'alt' => 'Second image', 'eyebrow' => '', 'heading' => 'Second slide', 'description' => '', 'primary_label' => '', 'primary_url' => ''],
            ],
            'autoplay' => false, 'interval' => 8, 'show_arrows' => true, 'show_dots' => true,
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['slides'])->toHaveCount(2)
        ->and($fresh?->content['slides'][0]['heading'])->toBe('bad()First slide')
        ->and($fresh?->content['slides'][0]['alt'])->toBe('Opening image')
        ->and($fresh?->content['autoplay'])->toBe('0')
        ->and($fresh?->content['interval'])->toBe('8')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->count())->toBe(2);

    $payload['content']['slides'][0]['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.slides');
});

it('manages video links posters captions and tenant isolation', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $poster = sliderMedia($website, 'Video poster');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign video poster');

    $this->actingAs($user)->post('/dashboard/builder/sections', [
        'page_id' => $page->getKey(), 'type' => 'video',
    ])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'video')->firstOrFail();
    $payload = [
        'variant' => 'contained', 'anchor' => 'film',
        'content' => [
            'eyebrow' => '<b>Watch</b>', 'heading' => 'Our film', 'description' => 'A short introduction.',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ?t=10', 'video_title' => 'Company film',
            'video_caption' => 'How we work.', 'show_caption' => true,
            'muted' => true, 'loop' => true, 'show_controls' => false,
            'poster_image_id' => $poster->public_id, 'poster_alt' => 'Team in the studio',
            'aspect_ratio' => 'wide', 'transcript_url' => '/transcript', 'transcript_label' => 'Read transcript',
            'primary_label' => 'Contact us', 'primary_url' => '#contact', 'primary_new_tab' => false,
        ],
        'design_settings' => ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'video_provider' => 'youtube', 'video_id' => 'dQw4w9WgXcQ',
        'poster_image_id' => $poster->public_id, 'poster_alt' => 'Team in the studio',
        'video_caption' => 'How we work.', 'transcript_url' => '/transcript',
        'muted' => '1', 'loop' => '1', 'show_controls' => '0',
    ]);
    expect(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'video_poster')->count())->toBe(1);

    $payload['content']['poster_image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.poster_image_id');

    $payload['content']['poster_image_id'] = $poster->public_id;
    $payload['content']['video_url'] = 'https://evil.example/watch?v=dQw4w9WgXcQ';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content['video_url'])->toBe('');

    $payload['content']['video_url'] = 'https://vimeo.com/12345678';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content['video_provider'])->toBe('vimeo');

    $payload['content']['video_url'] = 'https://storage.googleapis.com/public-bucket/film.mp4';
    $payload['content']['autoplay'] = true;
    $payload['content']['muted'] = false;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray(['video_provider' => 'file', 'autoplay' => '1', 'muted' => '1', 'show_controls' => '1']);
});

it('manages image and text copy responsive media layout links and tenant isolation', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $desktop = sliderMedia($website, 'Editorial desktop');
    $mobile = sliderMedia($website, 'Editorial mobile');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign editorial image');

    $this->actingAs($user)->post('/dashboard/builder/sections', [
        'page_id' => $page->getKey(), 'type' => 'image-text',
    ])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'image-text')->firstOrFail();
    $payload = [
        'variant' => 'image-left', 'anchor' => 'approach',
        'content' => [
            'eyebrow' => '<b>Our approach</b>', 'heading' => '<script>bad()</script>Designed for people',
            'description' => '<i>Plain introduction.</i>',
            'body' => '<p>Useful <strong>formatted copy</strong>.</p><script>alert(1)</script>',
            'primary_label' => '<b>Explore</b>', 'primary_url' => '/work', 'primary_new_tab' => true,
            'secondary_label' => '<i>Contact</i>', 'secondary_url' => '#contact', 'secondary_new_tab' => false,
            'supporting_text' => '<b>No obligation.</b>',
            'image_id' => $desktop->public_id, 'mobile_image_id' => $mobile->public_id,
            'image_alt' => '<i>Team planning</i>', 'image_caption' => '<b>Working together</b>',
            'image_position' => 'left', 'image_fit' => 'contain', 'image_ratio' => 'square',
            'image_size' => 'large', 'show_image_caption' => false,
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'spacious', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content)->toMatchArray([
        'eyebrow' => 'Our approach', 'heading' => 'bad()Designed for people',
        'description' => 'Plain introduction.', 'primary_label' => 'Explore',
        'primary_url' => '/work', 'primary_new_tab' => '1',
        'secondary_label' => 'Contact', 'secondary_url' => '#contact',
        'supporting_text' => 'No obligation.',
        'image_id' => $desktop->public_id, 'mobile_image_id' => $mobile->public_id,
        'image_alt' => 'Team planning', 'image_caption' => 'Working together',
        'image_position' => 'left', 'image_fit' => 'contain', 'image_ratio' => 'square',
        'image_size' => 'large', 'show_image_caption' => '0',
    ]);
    expect($section->fresh()?->content['body'])->toContain('<strong>formatted copy</strong>')->not->toContain('<script>');
    expect(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'image_text_images')->count())->toBe(2);

    $payload['content']['mobile_image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');

    $payload['content']['mobile_image_id'] = $mobile->public_id;
    $payload['content']['primary_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    expect($section->fresh()?->content['primary_url'])->toBe('');
});

it('manages formatted rich text with an optional tenant scoped image', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $image = sliderMedia($website, 'Rich text image');
    $mobileImage = sliderMedia($website, 'Rich text mobile image');
    [, $foreignWebsite] = pageBuilderWorkspace();
    $foreign = sliderMedia($foreignWebsite, 'Foreign rich text image');

    $this->actingAs($user)->post('/dashboard/builder/sections', ['page_id' => $page->getKey(), 'type' => 'rich-text'])->assertRedirect();
    $section = PageSection::query()->withoutGlobalScopes()->where('type', 'rich-text')->firstOrFail();
    $payload = [
        'variant' => 'wide', 'anchor' => 'our-story',
        'content' => [
            'eyebrow' => '<b>Our story</b>', 'heading' => '<script>bad()</script>A richer section',
            'introduction' => '<i>A clear and readable introduction.</i>',
            'body' => '<p>Safe <strong>formatted</strong> copy.</p><script>alert(1)</script><a href="javascript:bad()">Unsafe</a>',
            'primary_label' => '<b>Read more</b>', 'primary_url' => '/about', 'primary_new_tab' => true,
            'image_id' => $image->public_id, 'image_alt' => '<i>People collaborating</i>',
            'mobile_image_id' => $mobileImage->public_id,
            'image_caption' => '<b>Our team at work</b>', 'image_position' => 'left',
            'image_fit' => 'contain', 'image_ratio' => 'square', 'image_size' => 'large',
            'show_image_caption' => false,
        ],
        'design_settings' => ['surface' => 'muted', 'spacing' => 'normal', 'alignment' => 'left'],
        'is_enabled' => true, 'show_on_desktop' => true, 'show_on_tablet' => true, 'show_on_mobile' => true,
    ];

    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)->assertRedirect();
    $fresh = $section->fresh();
    expect($fresh?->content['eyebrow'])->toBe('Our story')
        ->and($fresh?->content['heading'])->toBe('bad()A richer section')
        ->and($fresh?->content['introduction'])->toBe('A clear and readable introduction.')
        ->and($fresh?->content['body'])->toContain('<strong>formatted</strong>')
        ->and($fresh?->content['body'])->not->toContain('<script>')
        ->and($fresh?->content['body'])->not->toContain('javascript:')
        ->and($fresh?->content['image_alt'])->toBe('People collaborating')
        ->and($fresh?->content['mobile_image_id'])->toBe($mobileImage->public_id)
        ->and($fresh?->content['image_caption'])->toBe('Our team at work')
        ->and($fresh?->content['image_position'])->toBe('left')
        ->and($fresh?->content['image_fit'])->toBe('contain')
        ->and($fresh?->content['image_ratio'])->toBe('square')
        ->and($fresh?->content['image_size'])->toBe('large')
        ->and($fresh?->content['show_image_caption'])->toBe('0')
        ->and($fresh?->content['primary_label'])->toBe('Read more')
        ->and($fresh?->content['primary_url'])->toBe('/about')
        ->and($fresh?->content['primary_new_tab'])->toBe('1')
        ->and(MediaUsage::query()->where('usable_id', $section->getKey())->where('field', 'rich_text_image')->count())->toBe(2);

    $payload['content']['image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');

    $payload['content']['image_id'] = $image->public_id;
    $payload['content']['mobile_image_id'] = $foreign->public_id;
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertSessionHasErrors('content.image_id');

    $payload['content']['mobile_image_id'] = $mobileImage->public_id;
    $payload['content']['primary_url'] = 'javascript:alert(1)';
    $this->actingAs($user)->put('/dashboard/builder/sections/'.$section->getKey(), $payload)
        ->assertRedirect();
    expect($section->fresh()?->content['primary_url'])->toBe('');
});

it('reorders sections exactly and rejects cross page ids', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $first = PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $page->getKey(), 'sort_order' => 0]);
    $second = PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $page->getKey(), 'type' => 'about', 'sort_order' => 1]);
    $otherPage = Page::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'slug' => 'other']);
    $foreign = PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $otherPage->getKey()]);

    $this->actingAs($user)->put('/dashboard/builder/sections/reorder', [
        'page_id' => $page->getKey(),
        'section_ids' => [$second->getKey(), $first->getKey()],
    ])->assertRedirect();
    expect($second->fresh()?->sort_order)->toBe(0)->and($first->fresh()?->sort_order)->toBe(1);

    $this->actingAs($user)->put('/dashboard/builder/sections/reorder', [
        'page_id' => $page->getKey(),
        'section_ids' => [$first->getKey(), $foreign->getKey()],
    ])->assertSessionHasErrors('section_ids');
});

it('duplicates and soft deletes sections without affecting their source', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $section = PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $page->getKey(),
        'anchor' => 'hero',
        'content' => ['heading' => 'Preserve me'],
    ]);

    $this->actingAs($user)->post('/dashboard/builder/sections/'.$section->getKey().'/duplicate')->assertRedirect();
    $copy = PageSection::query()->withoutGlobalScopes()->whereKeyNot($section->getKey())->firstOrFail();
    expect($copy->content)->toBe($section->content)->and($copy->anchor)->toBe('hero-copy');

    $this->actingAs($user)->delete('/dashboard/builder/sections/'.$copy->getKey())->assertRedirect();
    expect(PageSection::query()->whereKey($section->getKey())->exists())->toBeTrue()
        ->and(PageSection::query()->whereKey($copy->getKey())->exists())->toBeFalse()
        ->and(PageSection::query()->withoutGlobalScopes()->withTrashed()->whereKey($copy->getKey())->exists())->toBeTrue();
});

it('prevents deleting the home page', function () {
    [$user, , $page] = pageBuilderWorkspace();
    $this->actingAs($user)->delete('/dashboard/builder/pages/'.$page->getKey())->assertSessionHasErrors('page');
    expect($page->fresh())->not->toBeNull();
});

it('returns not found when a tenant attempts to mutate another tenant section', function () {
    [$user] = pageBuilderWorkspace();
    [, $otherWebsite, $otherPage] = pageBuilderWorkspace();
    $otherSection = PageSection::factory()->create([
        'tenant_id' => $otherWebsite->tenant_id,
        'website_id' => $otherWebsite->getKey(),
        'page_id' => $otherPage->getKey(),
    ]);

    $this->actingAs($user)->delete('/dashboard/builder/sections/'.$otherSection->getKey())->assertNotFound();
    expect(PageSection::query()->withoutGlobalScopes()->whereKey($otherSection->getKey())->exists())->toBeTrue();
});

it('maps compatible section variants during template switching without content loss', function () {
    [$user, $website, $page] = pageBuilderWorkspace();
    $section = PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $page->getKey(),
        'type' => 'hero',
        'variant' => 'split',
        'content' => ['heading' => 'Content survives'],
    ]);

    $this->actingAs($user)->post('/dashboard/design/template', ['template_key' => 'canvas', 'confirmed' => true])->assertRedirect();
    expect($section->fresh()?->variant)->toBe('editorial')
        ->and($section->fresh()?->content['heading'])->toBe('Content survives');
});

/**
 * @param  array<string, mixed>  $websiteOverrides
 * @return array{User, Website, Page}
 */
function pageBuilderWorkspace(array $websiteOverrides = []): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    TenantMembership::factory()->create(['tenant_id' => $tenant->getKey(), 'user_id' => $user->getKey()]);
    $website = Website::factory()->create(['tenant_id' => $tenant->getKey(), ...$websiteOverrides]);
    $page = Page::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'website_id' => $website->getKey(),
        'title' => 'Home',
        'slug' => 'home',
        'is_home' => true,
    ]);

    return [$user, $website, $page];
}

function sliderMedia(Website $website, string $title): MediaAsset
{
    $publicId = (string) Str::ulid();

    return MediaAsset::query()->withoutGlobalScopes()->create([
        'public_id' => $publicId, 'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'uploaded_by' => null, 'disk' => 'local', 'path' => 'tests/'.$publicId.'/original.webp',
        'original_name' => Str::slug($title).'.jpg', 'source_mime_type' => 'image/jpeg', 'mime_type' => 'image/webp',
        'extension' => 'webp', 'size' => 100, 'total_size' => 200, 'width' => 1600, 'height' => 900,
        'title' => $title, 'alt_text' => $title, 'variants' => [
            'thumbnail' => ['path' => 'tests/'.$publicId.'/thumbnail.webp', 'width' => 240, 'height' => 135, 'size' => 50, 'url' => '/media/'.$publicId.'/thumbnail'],
            'large' => ['path' => 'tests/'.$publicId.'/large.webp', 'width' => 1600, 'height' => 900, 'size' => 150, 'url' => '/media/'.$publicId.'/large'],
        ],
        'checksum' => str_repeat('a', 64),
    ]);
}
