<?php

use App\Enums\ContentPostStatus;
use App\Enums\ContentPostType;
use App\Enums\WebsiteStatus;
use App\Enums\WebsiteType;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\JobOpening;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\Testimonial;
use App\Models\Website;
use App\Models\WebsiteContactSetting;
use App\Models\WebsiteDomain;
use App\Models\WebsiteVersion;
use App\Publishing\WebsiteSnapshotFactory;
use Database\Seeders\TemplateSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(TemplateSeeder::class);
});

it('renders a published tenant home page with Blade, theme tokens, header, footer and enabled sections', function () {
    [$hostname, $website, $home] = publicWebsite();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'hero',
        'anchor' => 'welcome',
        'content' => ['eyebrow' => 'Trusted locally', 'heading' => 'A polished public website', 'description' => 'Clear content for real visitors.'],
        'show_on_mobile' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'about',
        'content' => ['heading' => 'Disabled secret'],
        'is_enabled' => false,
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertViewIs('tenant.site')
        ->assertSee('A polished public website')
        ->assertDontSee('Disabled secret')
        ->assertSee('Primary navigation')
        ->assertSee('Footer navigation')
        ->assertSee('Website by Ezyweb')
        ->assertSee('--theme-primary:', false)
        ->assertSee('site-hide-mobile', false)
        ->assertDontSee('data-page=', false);
});

it('renders a fully managed responsive hero with copy images and two calls to action', function () {
    [$hostname, $website, $home] = publicWebsite();
    $desktop = (string) Str::ulid();
    $mobile = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'hero', 'variant' => 'editorial',
        'content' => [
            'eyebrow' => 'Conversion first', 'heading' => 'A complete managed hero',
            'description' => 'Every visible element is editable.',
            'primary_label' => 'Start now', 'primary_url' => '#contact',
            'secondary_label' => 'View work', 'secondary_url' => '/portfolio',
            'image_id' => $desktop, 'mobile_image_id' => $mobile, 'image_alt' => 'Customer using Ezyweb',
            'image_position' => 'left', 'image_fit' => 'contain',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('A complete managed hero')
        ->assertSee('Every visible element is editable.')
        ->assertSee('href="#contact"', false)
        ->assertSee('href="/portfolio"', false)
        ->assertSee('/media/'.$desktop.'/large', false)
        ->assertSee('/media/'.$mobile.'/medium', false)
        ->assertSee('image-left', false)
        ->assertSee('fit-contain', false)
        ->assertSee('Customer using Ezyweb')
        ->assertDontSee('site-hero-art', false);
});

it('renders a fully managed conversion call to action with responsive background and secure links', function () {
    [$hostname, $website, $home] = publicWebsite();
    $desktop = (string) Str::ulid();
    $mobile = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'cta', 'variant' => 'split',
        'content' => [
            'eyebrow' => 'Take the next step', 'heading' => 'Ready to build your website?',
            'description' => 'Start a conversation with the Ezyweb team.',
            'primary_label' => 'Book a call', 'primary_url' => '/contact', 'primary_new_tab' => '0',
            'secondary_label' => 'View pricing', 'secondary_url' => 'https://ezyweb.in/pricing', 'secondary_new_tab' => '1',
            'supporting_text' => 'No obligation. Friendly advice.',
            'image_id' => $desktop, 'mobile_image_id' => $mobile, 'image_alt' => 'A productive business conversation',
            'media_layout' => 'background', 'image_fit' => 'cover', 'overlay_strength' => '70',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Ready to build your website?')
        ->assertSee('Start a conversation with the Ezyweb team.')
        ->assertSee('No obligation. Friendly advice.')
        ->assertSee('href="/contact"', false)
        ->assertSee('href="https://ezyweb.in/pricing"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('/media/'.$desktop.'/large', false)
        ->assertSee('/media/'.$mobile.'/medium', false)
        ->assertSee('has-background', false)
        ->assertSee('--cta-overlay: 0.7', false)
        ->assertSee('A productive business conversation');
});

it('renders fully managed feature cards with media icons highlights and secure links', function () {
    [$hostname, $website, $home] = publicWebsite();
    $image = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'features', 'variant' => 'cards',
        'content' => [
            'eyebrow' => 'Why Ezyweb', 'heading' => 'Capabilities that create value',
            'description' => 'A clear introduction to the offering.',
            'feature_items' => [
                ['icon' => '⚡', 'image_id' => $image, 'image_alt' => 'Fast website setup', 'title' => 'Launch faster', 'description' => 'Move from idea to online presence quickly.', 'link_label' => 'Explore speed', 'link_url' => '/speed', 'is_featured' => '1'],
                ['icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Dependable support', 'description' => 'Help when it matters.', 'link_label' => 'Contact us', 'link_url' => 'https://ezyweb.in/contact', 'is_featured' => '0'],
            ],
            'columns' => '2', 'show_icons' => '1', 'show_images' => '1', 'show_descriptions' => '1',
            'show_links' => '1', 'open_new_tab' => '1', 'image_fit' => 'contain',
            'card_alignment' => 'center', 'featured_label' => 'Recommended',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Capabilities that create value')
        ->assertSee('Launch faster')
        ->assertSee('Dependable support')
        ->assertSee('/media/'.$image.'/medium', false)
        ->assertSee('Fast website setup')
        ->assertSee('Recommended')
        ->assertSee('fit-contain', false)
        ->assertSee('align-cards-center', false)
        ->assertSee('--feature-columns: 2', false)
        ->assertSee('href="/speed"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false);
});

it('renders a fully managed process timeline with media notes and secure step links', function () {
    [$hostname, $website, $home] = publicWebsite();
    $image = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'process', 'variant' => 'timeline',
        'content' => [
            'eyebrow' => 'How it works', 'heading' => 'A clear delivery process',
            'description' => 'Know what happens at every stage.',
            'process_items' => [
                ['number' => '01', 'icon' => '✦', 'image_id' => $image, 'image_alt' => 'Collaborative discovery session', 'title' => 'Discover', 'description' => 'We clarify goals and priorities.', 'meta' => '1–2 days', 'link_label' => 'See our method', 'link_url' => 'https://ezyweb.in/method'],
                ['number' => '02', 'icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Deliver', 'description' => 'We review and launch.', 'meta' => 'Launch day', 'link_label' => '', 'link_url' => ''],
            ],
            'process_columns' => '3', 'show_step_numbers' => '1', 'show_step_icons' => '1',
            'show_step_images' => '1', 'show_step_descriptions' => '1', 'show_step_meta' => '1',
            'show_step_links' => '1', 'open_new_tab' => '1', 'image_fit' => 'contain',
            'process_alignment' => 'left', 'process_connector' => 'dashed',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('A clear delivery process')
        ->assertSee('Discover')
        ->assertSee('Deliver')
        ->assertSee('1–2 days')
        ->assertSee('/media/'.$image.'/medium', false)
        ->assertSee('Collaborative discovery session')
        ->assertSee('variant-timeline', false)
        ->assertSee('connector-dashed', false)
        ->assertSee('fit-contain', false)
        ->assertSee('href="https://ezyweb.in/method"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false);
});

it('renders a fully managed pricing comparison with benefits highlights and secure calls to action', function () {
    [$hostname, $website, $home] = publicWebsite();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'pricing', 'variant' => 'comparison',
        'content' => [
            'eyebrow' => 'Plans', 'heading' => 'Pricing that scales with you',
            'description' => 'Choose the package that fits your business.',
            'pricing_items' => [
                ['name' => 'Starter', 'badge' => '', 'description' => 'For new businesses.', 'currency' => '₹', 'price' => '999', 'original_price' => '1,299', 'period' => '/ month', 'features' => "+ One website\n- Priority support", 'button_label' => 'Start today', 'button_url' => '/contact', 'is_featured' => '0'],
                ['name' => 'Growth', 'badge' => 'Most popular', 'description' => 'For growing teams.', 'currency' => '₹', 'price' => '2,499', 'original_price' => '', 'period' => '/ month', 'features' => "+ Five websites\n+ Priority support", 'button_label' => 'Choose Growth', 'button_url' => 'https://ezyweb.in/contact', 'is_featured' => '1'],
            ],
            'pricing_columns' => '2', 'currency_position' => 'before', 'show_plan_descriptions' => '1',
            'show_plan_features' => '1', 'show_plan_buttons' => '1', 'show_plan_badges' => '1',
            'show_original_prices' => '1', 'open_new_tab' => '1',
            'pricing_note' => 'Taxes may apply.',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Pricing that scales with you')
        ->assertSee('Starter')
        ->assertSee('Growth')
        ->assertSee('Most popular')
        ->assertSee('1,299')
        ->assertSee('One website')
        ->assertSee('Priority support')
        ->assertSee('is-excluded', false)
        ->assertSee('variant-comparison', false)
        ->assertSee('--pricing-columns: 2', false)
        ->assertSee('href="https://ezyweb.in/contact"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('Taxes may apply.');
});

it('renders a fully managed contact section using published contact details and form configuration', function () {
    [$hostname, $website, $home] = publicWebsite();
    WebsiteContactSetting::query()->withoutGlobalScopes()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'heading' => 'Old settings heading', 'description' => 'Old settings description',
        'address' => 'Ezyweb Studio, Kolkata', 'phone_numbers' => ['+91 98765 43210'],
        'email_addresses' => ['hello@ezyweb.in'], 'working_hours' => 'Monday–Friday, 9 AM–6 PM',
        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=test',
        'form_fields' => ['name' => true, 'email' => true, 'phone' => true, 'subject' => true, 'message' => true],
        'success_message' => 'Thanks for contacting us.', 'recipient_email' => 'leads@ezyweb.in',
        'is_form_enabled' => true,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'contact', 'variant' => 'map-hours', 'anchor' => 'contact',
        'content' => [
            'eyebrow' => 'Contact', 'heading' => 'Start a useful conversation',
            'description' => 'Tell us what you are planning.', 'details_heading' => 'Visit or call',
            'address_label' => 'Our studio', 'phone_label' => 'Call our team', 'email_label' => 'Email our team',
            'hours_label' => 'Business hours', 'map_title' => 'Directions to Ezyweb Studio',
            'form_heading' => 'Tell us about your project', 'form_description' => 'We usually reply within one business day.',
            'name_label' => 'Your name', 'name_placeholder' => 'Jane Doe',
            'email_field_label' => 'Work email', 'email_placeholder' => 'jane@example.com',
            'phone_field_label' => 'Phone number', 'phone_placeholder' => '+91…',
            'subject_label' => 'Enquiry topic', 'subject_placeholder' => 'New website',
            'message_label' => 'Project details', 'message_placeholder' => 'Tell us what you need',
            'submit_label' => 'Request a callback', 'privacy_note' => 'Your details are only used to answer this enquiry.',
            'show_details_heading' => '1', 'show_address' => '1', 'show_phone_numbers' => '1',
            'show_email_addresses' => '1', 'show_working_hours' => '1', 'show_map' => '1',
            'show_form' => '1', 'show_form_heading' => '1', 'show_form_description' => '1',
            'form_position' => 'left', 'map_height' => 'large',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Start a useful conversation')
        ->assertDontSee('Old settings heading')
        ->assertSee('Visit or call')
        ->assertSee('Ezyweb Studio, Kolkata')
        ->assertSee('href="tel:+919876543210"', false)
        ->assertSee('href="mailto:hello@ezyweb.in"', false)
        ->assertSee('Monday–Friday, 9 AM–6 PM')
        ->assertSee('title="Directions to Ezyweb Studio"', false)
        ->assertSee('map-height-large', false)
        ->assertSee('form-left', false)
        ->assertSee('Tell us about your project')
        ->assertSee('placeholder="jane@example.com"', false)
        ->assertSee('placeholder="Tell us what you need"', false)
        ->assertSee('Request a callback')
        ->assertSee('Your details are only used to answer this enquiry.');
});

it('renders a fully managed map with published location details and secure actions', function () {
    [$hostname, $website, $home] = publicWebsite();
    WebsiteContactSetting::query()->withoutGlobalScopes()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'address' => 'Ezyweb Studio, Kolkata', 'phone_numbers' => [], 'email_addresses' => [],
        'working_hours' => 'Monday–Friday, 9 AM–6 PM',
        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=managed-map',
        'form_fields' => ['name' => true, 'email' => true, 'phone' => true, 'subject' => true, 'message' => true],
        'success_message' => 'Thank you.', 'recipient_email' => 'leads@ezyweb.in', 'is_form_enabled' => true,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'map', 'variant' => 'split', 'anchor' => 'find-us',
        'content' => [
            'eyebrow' => 'Find us', 'heading' => 'Visit Ezyweb Studio',
            'description' => 'Everything you need to plan your visit.', 'location_heading' => 'Our Kolkata studio',
            'address_label' => 'Studio address', 'hours_label' => 'Visitor hours',
            'location_note' => 'Accessible parking is available.', 'map_title' => 'Directions to Ezyweb Studio',
            'directions_label' => 'Open in Google Maps', 'directions_url' => 'https://maps.google.com/?q=Ezyweb',
            'contact_label' => 'Contact our team', 'contact_url' => '/contact',
            'show_location_details' => '1', 'show_address' => '1', 'show_working_hours' => '1',
            'show_map' => '1', 'show_directions_button' => '1', 'show_contact_button' => '1',
            'open_new_tab' => '1', 'map_position' => 'left', 'map_height' => 'large',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Visit Ezyweb Studio')
        ->assertSee('Our Kolkata studio')
        ->assertSee('Ezyweb Studio, Kolkata')
        ->assertSee('Monday–Friday, 9 AM–6 PM')
        ->assertSee('Accessible parking is available.')
        ->assertSee('src="https://www.google.com/maps/embed?pb=managed-map"', false)
        ->assertSee('title="Directions to Ezyweb Studio"', false)
        ->assertSee('href="https://maps.google.com/?q=Ezyweb"', false)
        ->assertSee('href="/contact"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('map-left', false)
        ->assertSee('map-height-large', false)
        ->assertDontSee('site-content-art', false);
});

it('renders a fully managed about section with formatted copy responsive images and caption', function () {
    [$hostname, $website, $home] = publicWebsite();
    $desktop = (string) Str::ulid();
    $mobile = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'about', 'variant' => 'image-left',
        'content' => [
            'eyebrow' => 'About Ezyweb', 'heading' => 'A company with a clear purpose',
            'body' => '<p>Our <strong>formatted story</strong> belongs here.</p>',
            'primary_label' => 'Meet the team', 'primary_url' => '/team',
            'image_id' => $desktop, 'mobile_image_id' => $mobile, 'image_alt' => 'Ezyweb colleagues',
            'image_caption' => 'Building together', 'image_position' => 'right', 'image_fit' => 'contain',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('A company with a clear purpose')
        ->assertSee('<strong>formatted story</strong>', false)
        ->assertSee('href="/team"', false)
        ->assertSee('/media/'.$desktop.'/large', false)
        ->assertSee('/media/'.$mobile.'/medium', false)
        ->assertSee('image-left', false)
        ->assertSee('fit-contain', false)
        ->assertSee('Ezyweb colleagues')
        ->assertSee('Building together')
        ->assertDontSee('site-content-art', false);
});

it('renders managed story mission and vision content with responsive images', function () {
    [$hostname, $website, $home] = publicWebsite();
    $story = (string) Str::ulid();
    $storyMobile = (string) Str::ulid();
    $mission = (string) Str::ulid();
    $vision = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'mission-vision', 'variant' => 'cards',
        'content' => [
            'eyebrow' => 'Our purpose', 'heading' => 'What guides us',
            'story_heading' => 'Our story', 'story_body' => '<p>A <strong>meaningful beginning</strong>.</p>',
            'story_image_id' => $story, 'story_mobile_image_id' => $storyMobile,
            'story_image_alt' => 'The founding team', 'story_image_caption' => 'Where it began',
            'mission_heading' => 'Our mission', 'mission_body' => '<p>Help every business thrive online.</p>',
            'mission_image_id' => $mission, 'mission_mobile_image_id' => '',
            'mission_image_alt' => 'Our team at work', 'mission_image_caption' => '',
            'vision_heading' => 'Our vision', 'vision_body' => '<p>A web that works for everyone.</p>',
            'vision_image_id' => $vision, 'vision_mobile_image_id' => '',
            'vision_image_alt' => 'Looking ahead', 'vision_image_caption' => 'Our future',
            'image_position' => 'right', 'image_fit' => 'contain',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('What guides us')
        ->assertSee('<strong>meaningful beginning</strong>', false)
        ->assertSee('Help every business thrive online.')
        ->assertSee('A web that works for everyone.')
        ->assertSee('/media/'.$story.'/large', false)
        ->assertSee('/media/'.$storyMobile.'/medium', false)
        ->assertSee('/media/'.$mission.'/large', false)
        ->assertSee('/media/'.$vision.'/large', false)
        ->assertSee('variant-cards', false)
        ->assertSee('fit-contain', false)
        ->assertSee('The founding team')
        ->assertSee('Where it began')
        ->assertSee('Our future')
        ->assertDontSee('site-content-art', false);
});

it('renders managed client and project counters with accessible animation data', function () {
    [$hostname, $website, $home] = publicWebsite();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'counters', 'variant' => 'band',
        'content' => [
            'eyebrow' => 'Our impact', 'heading' => 'Results that build trust',
            'description' => 'Measured outcomes for growing businesses.',
            'items' => [
                ['value' => '1250', 'prefix' => '', 'suffix' => '+', 'label' => 'Happy clients', 'description' => 'Across growing businesses'],
                ['value' => '3480', 'prefix' => '', 'suffix' => '+', 'label' => 'Projects delivered', 'description' => 'Websites and campaigns'],
            ],
            'animate' => '1', 'duration' => '3', 'use_grouping' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Results that build trust')
        ->assertSee('Happy clients')
        ->assertSee('Projects delivered')
        ->assertSee('1,250')
        ->assertSee('3,480')
        ->assertSee('data-counter-section', false)
        ->assertSee('data-animate="true"', false)
        ->assertSee('data-duration="3000"', false)
        ->assertSee('data-grouping="true"', false)
        ->assertSee('aria-label="1,250+ Happy clients"', false)
        ->assertSee('variant-band', false)
        ->assertDontSee('site-card-grid', false);
});

it('renders a managed accessible brand and partner carousel', function () {
    [$hostname, $website, $home] = publicWebsite();
    $first = (string) Str::ulid();
    $second = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'brand-carousel', 'variant' => 'carousel',
        'content' => [
            'eyebrow' => 'Trusted by', 'heading' => 'Our partners',
            'description' => 'Organisations that work with us.',
            'logos' => [
                ['image_id' => $first, 'name' => 'Alpha Co', 'alt' => 'Alpha company logo', 'url' => 'https://alpha.example'],
                ['image_id' => $second, 'name' => 'Beta Co', 'alt' => 'Beta company logo', 'url' => '/partners/beta'],
            ],
            'autoplay' => '1', 'interval' => '5', 'show_arrows' => '1', 'pause_on_hover' => '1',
            'show_names' => '1', 'grayscale' => '1', 'open_new_tab' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Our partners')
        ->assertSee('Alpha Co')
        ->assertSee('Beta Co')
        ->assertSee('/media/'.$first.'/medium', false)
        ->assertSee('/media/'.$second.'/small', false)
        ->assertSee('href="https://alpha.example"', false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('data-brand-carousel', false)
        ->assertSee('data-autoplay="true"', false)
        ->assertSee('data-interval="5000"', false)
        ->assertSee('data-brand-previous', false)
        ->assertSee('data-brand-next', false)
        ->assertSee('is-grayscale', false)
        ->assertDontSee('site-card-grid', false);
});

it('renders a managed product catalogue from the immutable product snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    Product::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Starter Kit', 'slug' => 'starter-kit', 'short_description' => 'Everything needed to begin.',
        'featured_image' => 'https://cdn.example/starter.jpg', 'price' => 1499, 'show_price' => true,
        'enquiry_label' => 'Ask about Starter', 'is_featured' => true, 'display_order' => 2,
    ]);
    Product::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Growth Kit', 'slug' => 'growth-kit', 'short_description' => 'Tools for the next stage.',
        'featured_image' => 'https://cdn.example/growth.jpg', 'price' => 2999, 'show_price' => true,
        'enquiry_label' => 'Ask about Growth', 'display_order' => 1,
    ]);
    Product::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Hidden Kit', 'slug' => 'hidden-kit', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'products', 'variant' => 'featured',
        'content' => [
            'eyebrow' => 'Our products', 'heading' => 'Explore the catalogue', 'description' => 'Choose your next step.',
            'selection_mode' => 'selected', 'product_slugs' => 'starter-kit,growth-kit', 'featured_only' => '0',
            'limit' => '6', 'sort' => 'price-high', 'columns' => '3',
            'show_images' => '1', 'show_descriptions' => '1', 'show_prices' => '1', 'price_prefix' => '₹',
            'show_featured_badge' => '1', 'featured_label' => 'Popular',
            'show_button' => '1', 'button_label' => '', 'button_url' => '#contact', 'image_fit' => 'contain',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Explore the catalogue')
        ->assertSeeInOrder(['Growth Kit', 'Starter Kit'])
        ->assertSee('Everything needed to begin.')
        ->assertSee('https://cdn.example/starter.jpg', false)
        ->assertSee('₹2,999.00')
        ->assertSee('₹1,499.00')
        ->assertSee('Popular')
        ->assertSee('Ask about Starter')
        ->assertSee('href="#contact"', false)
        ->assertSee('fit-contain', false)
        ->assertSee('is-lead', false)
        ->assertDontSee('Hidden Kit')
        ->assertDontSee('site-card-grid', false);
});

it('renders a managed accessible testimonial carousel from the published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    Testimonial::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'client_name' => 'Anita Rao', 'client_role' => 'Founder', 'company' => 'Northstar',
        'photo' => 'https://cdn.example/anita.jpg', 'quote' => 'Ezyweb helped us launch with confidence.',
        'rating' => 5, 'is_featured' => true, 'display_order' => 2,
    ]);
    Testimonial::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'client_name' => 'Rahul Shah', 'client_role' => 'Director', 'company' => 'Acme',
        'quote' => 'The process was clear and dependable.', 'rating' => 4, 'display_order' => 1,
    ]);
    Testimonial::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'client_name' => 'Hidden Client', 'quote' => 'Should not appear.', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'testimonials', 'variant' => 'carousel',
        'content' => [
            'eyebrow' => 'Client stories', 'heading' => 'What our clients say', 'description' => 'Real experiences.',
            'testimonial_featured_only' => '0', 'testimonial_limit' => '6', 'testimonial_sort' => 'rating-high',
            'show_photos' => '1', 'show_roles' => '1', 'show_companies' => '1', 'show_ratings' => '1',
            'autoplay' => '1', 'interval' => '7', 'show_arrows' => '1', 'show_dots' => '1',
            'pause_on_hover' => '1', 'slides_per_view' => '2',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('What our clients say')
        ->assertSeeInOrder(['Anita Rao', 'Rahul Shah'])
        ->assertSee('Ezyweb helped us launch with confidence.')
        ->assertSee('Founder · Northstar')
        ->assertSee('https://cdn.example/anita.jpg', false)
        ->assertSee('5 out of 5 stars')
        ->assertSee('data-testimonial-carousel', false)
        ->assertSee('data-interval="7000"', false)
        ->assertSee('data-testimonial-previous', false)
        ->assertSee('data-testimonial-next', false)
        ->assertSee('data-testimonial-dot', false)
        ->assertSee('--testimonial-per-view: 2', false)
        ->assertDontSee('Hidden Client')
        ->assertDontSee('site-card-grid', false);
});

it('renders a filtered managed portfolio from the immutable published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $category = PortfolioCategory::query()->withoutGlobalScopes()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Web design', 'slug' => 'web-design',
    ]);
    $newer = PortfolioProject::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'portfolio_category_id' => $category->getKey(),
        'title' => 'Northstar Commerce', 'slug' => 'northstar-commerce', 'client' => 'Northstar',
        'project_date' => '2026-04-15', 'location' => 'Kochi', 'short_description' => 'A faster buying experience.',
        'featured_image' => 'https://cdn.example/northstar.jpg', 'project_url' => 'https://northstar.example', 'is_featured' => true,
    ]);
    PortfolioProject::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'portfolio_category_id' => $category->getKey(),
        'title' => 'Acme Website', 'slug' => 'acme-website', 'project_date' => '2025-03-01', 'is_featured' => true,
    ]);
    PortfolioProject::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Hidden Project', 'slug' => 'hidden-project', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'portfolio', 'variant' => 'featured',
        'content' => [
            'eyebrow' => 'Our work', 'heading' => 'Selected projects', 'description' => 'Work that creates momentum.',
            'portfolio_selection_mode' => 'selected', 'portfolio_slugs' => 'northstar-commerce,acme-website',
            'portfolio_category' => 'Web design', 'portfolio_featured_only' => '1', 'portfolio_limit' => '6',
            'portfolio_sort' => 'newest', 'portfolio_columns' => '2', 'show_images' => '1',
            'show_descriptions' => '1', 'show_categories' => '1', 'show_clients' => '1', 'show_dates' => '1',
            'show_locations' => '1', 'show_button' => '1', 'button_label' => 'Read case study',
            'image_fit' => 'contain', 'open_new_tab' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $newer->update(['title' => 'Unpublished replacement']);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Selected projects')
        ->assertSeeInOrder(['Northstar Commerce', 'Acme Website'])
        ->assertSee('A faster buying experience.')
        ->assertSee('Web design')
        ->assertSee('Northstar · Apr 2026 · Kochi')
        ->assertSee('https://cdn.example/northstar.jpg', false)
        ->assertSee('fit-contain', false)
        ->assertSee('href="https://northstar.example"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('Read case study')
        ->assertSee('is-lead', false)
        ->assertDontSee('Hidden Project')
        ->assertDontSee('Unpublished replacement')
        ->assertDontSee('site-card-grid', false);
});

it('renders a managed responsive gallery and accessible lightbox from the published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $first = GalleryItem::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Launch Day', 'image' => 'https://cdn.example/launch.jpg', 'alt_text' => 'Team celebrating the launch',
        'caption' => 'A milestone shared together.', 'category' => 'Events', 'aspect_ratio' => 'landscape', 'display_order' => 2,
    ]);
    GalleryItem::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Opening Moment', 'image' => 'https://cdn.example/opening.jpg', 'alt_text' => 'Opening ceremony',
        'caption' => 'The first moment.', 'category' => 'Events', 'aspect_ratio' => 'portrait', 'display_order' => 1,
    ]);
    GalleryItem::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'title' => 'Hidden Image', 'image' => 'https://cdn.example/hidden.jpg', 'alt_text' => 'Hidden', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'gallery', 'variant' => 'masonry',
        'content' => [
            'eyebrow' => 'Inside our work', 'heading' => 'Gallery highlights', 'description' => 'Moments worth sharing.',
            'gallery_selection_mode' => 'selected', 'gallery_images' => "https://cdn.example/launch.jpg\nhttps://cdn.example/opening.jpg",
            'gallery_category' => 'Events', 'gallery_limit' => '12', 'gallery_sort' => 'title', 'gallery_columns' => '4',
            'show_titles' => '1', 'show_captions' => '1', 'show_categories' => '1',
            'respect_aspect_ratios' => '1', 'image_fit' => 'contain', 'gallery_gap' => 'compact',
            'enable_lightbox' => '1', 'lightbox_captions' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $first->update(['title' => 'Unpublished gallery title']);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Gallery highlights')
        ->assertSeeInOrder(['Launch Day', 'Opening Moment'])
        ->assertSee('A milestone shared together.')
        ->assertSee('Team celebrating the launch')
        ->assertSee('https://cdn.example/launch.jpg', false)
        ->assertSee('ratio-landscape', false)
        ->assertSee('ratio-portrait', false)
        ->assertSee('fit-contain', false)
        ->assertSee('gap-compact', false)
        ->assertSee('--gallery-columns: 4', false)
        ->assertSee('data-gallery-lightbox', false)
        ->assertSee('data-gallery-dialog', false)
        ->assertSee('data-gallery-previous', false)
        ->assertSee('data-gallery-next', false)
        ->assertDontSee('Hidden Image')
        ->assertDontSee('Unpublished gallery title')
        ->assertDontSee('site-card-grid', false);
});

it('renders a filtered managed team from the immutable published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $director = TeamMember::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Anita Rao', 'role' => 'Director', 'photo' => 'https://cdn.example/anita-team.jpg',
        'bio' => 'Anita leads strategy and client partnerships.', 'email' => 'anita@example.com', 'phone' => '+91 98765 43210',
        'linkedin_url' => 'https://linkedin.com/in/anita-rao', 'is_featured' => true, 'display_order' => 2,
    ]);
    TeamMember::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Kiran Das', 'role' => 'Director', 'email' => 'kiran@example.com', 'display_order' => 1,
    ]);
    TeamMember::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'name' => 'Hidden Person', 'role' => 'Director', 'email' => 'hidden@example.com', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'team', 'variant' => 'compact',
        'content' => [
            'eyebrow' => 'Our people', 'heading' => 'Meet the team', 'description' => 'Experience behind every project.',
            'team_selection_mode' => 'selected', 'team_keys' => "anita@example.com\nkiran@example.com",
            'team_role' => 'Director', 'team_featured_only' => '0', 'team_limit' => '8',
            'team_sort' => 'featured-first', 'team_columns' => '3', 'show_photos' => '1', 'show_roles' => '1',
            'show_bios' => '1', 'show_email' => '1', 'show_phone' => '1', 'show_linkedin' => '1',
            'image_shape' => 'circle', 'image_fit' => 'contain', 'card_alignment' => 'center',
            'show_featured_badge' => '1', 'featured_label' => 'Leadership',
        ],
    ]);

    freezePublishedWebsite($website);
    $director->update(['name' => 'Unpublished Team Name']);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Meet the team')
        ->assertSeeInOrder(['Anita Rao', 'Kiran Das'])
        ->assertSee('Anita leads strategy and client partnerships.')
        ->assertSee('Director')
        ->assertSee('Leadership')
        ->assertSee('https://cdn.example/anita-team.jpg', false)
        ->assertSee('shape-circle', false)
        ->assertSee('fit-contain', false)
        ->assertSee('align-cards-center', false)
        ->assertSee('mailto:anita@example.com', false)
        ->assertSee('tel:+919876543210', false)
        ->assertSee('href="https://linkedin.com/in/anita-rao"', false)
        ->assertSee('target="_blank"', false)
        ->assertDontSee('Hidden Person')
        ->assertDontSee('Unpublished Team Name')
        ->assertDontSee('site-card-grid', false);
});

it('renders a searchable managed faq and matching structured data from the published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $delivery = Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'How quickly can we launch?', 'answer' => 'Most projects launch within four weeks.', 'category' => 'Delivery', 'display_order' => 2,
    ]);
    Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'Can you migrate our content?', 'answer' => 'Yes, migration is available.', 'category' => 'Delivery', 'display_order' => 1,
    ]);
    Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'Not selected FAQ', 'answer' => 'This should not be rendered.', 'category' => 'Other',
    ]);
    Faq::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
        'question' => 'Inactive FAQ', 'answer' => 'Hidden.', 'category' => 'Delivery', 'is_active' => false,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(),
        'type' => 'faq', 'variant' => 'two-column',
        'content' => [
            'eyebrow' => 'FAQ', 'heading' => 'Helpful answers', 'description' => 'What clients ask most often.',
            'faq_selection_mode' => 'selected', 'faq_keys' => "How quickly can we launch?\nCan you migrate our content?",
            'faq_category' => 'Delivery', 'faq_limit' => '8', 'faq_sort' => 'question',
            'show_categories' => '1', 'show_numbers' => '1', 'default_open' => 'first', 'allow_multiple' => '0',
            'enable_search' => '1', 'search_placeholder' => 'Search our answers', 'show_expand_controls' => '1',
            'show_cta' => '1', 'cta_text' => 'Still need help?', 'cta_label' => 'Contact our team', 'cta_url' => '/contact',
        ],
    ]);

    freezePublishedWebsite($website);
    $delivery->update(['question' => 'Unpublished FAQ question']);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Helpful answers')
        ->assertSeeInOrder(['Can you migrate our content?', 'How quickly can we launch?'])
        ->assertSee('Most projects launch within four weeks.')
        ->assertSee('Delivery')
        ->assertSee('Search our answers')
        ->assertSee('data-faq-search', false)
        ->assertSee('data-allow-multiple="false"', false)
        ->assertSee('data-faq-expand', false)
        ->assertSee('data-faq-collapse', false)
        ->assertSee('site-faq-number', false)
        ->assertSee('variant-two-column', false)
        ->assertSee('Still need help?')
        ->assertSee('href="/contact"', false)
        ->assertSee('FAQPage')
        ->assertDontSee('Not selected FAQ')
        ->assertDontSee('Inactive FAQ')
        ->assertDontSee('Unpublished FAQ question')
        ->assertDontSee('site-card-grid', false);
});

it('renders selected managed blog cards from the immutable published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $category = ContentCategory::query()->withoutGlobalScopes()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Guides', 'slug' => 'guides']);
    $post = ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_category_id' => $category->getKey(), 'content_type' => ContentPostType::Blog, 'title' => 'Build a better website', 'slug' => 'better-website', 'excerpt' => 'A practical Ezyweb guide.', 'body' => str_repeat('useful content ', 220), 'featured_image' => 'https://cdn.example.test/blog.webp', 'author_name' => 'Ezyweb Team', 'status' => ContentPostStatus::Published, 'published_at' => now()->subDay()]);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog, 'title' => 'Hidden draft', 'slug' => 'hidden-draft', 'status' => ContentPostStatus::Draft]);
    PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(), 'type' => 'blog-posts', 'variant' => 'featured', 'content' => ['heading' => 'Latest insights', 'blog_selection_mode' => 'selected', 'blog_slugs' => 'better-website', 'blog_category' => 'Guides', 'blog_limit' => '3', 'blog_sort' => 'newest', 'blog_columns' => '2', 'show_images' => '1', 'show_excerpts' => '1', 'show_authors' => '1', 'show_dates' => '1', 'show_categories' => '1', 'show_tags' => '0', 'show_reading_time' => '1', 'read_more_label' => 'Read article', 'show_archive_link' => '1', 'archive_label' => 'View all posts', 'image_fit' => 'contain']]);

    freezePublishedWebsite($website);
    $post->update(['title' => 'Unpublished title']);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Latest insights')->assertSee('Build a better website')->assertSee('A practical Ezyweb guide.')->assertSee('Ezyweb Team')->assertSee('3 min read')->assertSee('object-fit: contain', false)->assertSee('href="/blog/better-website"', false)->assertSee('href="/blog"', false)->assertDontSee('Hidden draft')->assertDontSee('Unpublished title')->assertDontSee('site-card-grid', false);
});

it('renders selected managed news from the immutable published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $category = ContentCategory::query()->withoutGlobalScopes()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Announcements', 'slug' => 'announcements']);
    $article = ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_category_id' => $category->getKey(), 'content_type' => ContentPostType::News, 'title' => 'Ezyweb opens a new office', 'slug' => 'new-office', 'excerpt' => 'The team is growing.', 'body' => str_repeat('news content ', 220), 'featured_image' => 'https://cdn.example.test/news.webp', 'author_name' => 'News Desk', 'status' => ContentPostStatus::Published, 'published_at' => now()->subDay()]);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::News, 'title' => 'Future announcement', 'slug' => 'future-announcement', 'status' => ContentPostStatus::Scheduled, 'published_at' => now()->addDay()]);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Blog, 'title' => 'Blog must stay separate', 'slug' => 'blog-separate', 'status' => ContentPostStatus::Published, 'published_at' => now()->subDay()]);
    PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(), 'type' => 'news', 'variant' => 'list', 'content' => ['heading' => 'Company newsroom', 'news_selection_mode' => 'selected', 'news_slugs' => 'new-office', 'news_category' => 'Announcements', 'news_limit' => '4', 'news_sort' => 'newest', 'news_columns' => '2', 'show_images' => '1', 'show_excerpts' => '1', 'show_authors' => '1', 'show_dates' => '1', 'show_categories' => '1', 'show_tags' => '0', 'show_reading_time' => '1', 'read_more_label' => 'Full story', 'show_archive_link' => '1', 'archive_label' => 'All news', 'image_fit' => 'contain']]);

    freezePublishedWebsite($website);
    $article->update(['title' => 'Unpublished news title']);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Company newsroom')->assertSee('Ezyweb opens a new office')->assertSee('The team is growing.')->assertSee('News Desk')->assertSee('3 min read')->assertSee('variant-list', false)->assertSee('object-fit: contain', false)->assertSee('href="/news/new-office"', false)->assertSee('href="/news"', false)->assertDontSee('Future announcement')->assertDontSee('Blog must stay separate')->assertDontSee('Unpublished news title')->assertDontSee('site-card-grid', false);
});

it('renders filtered managed events with schedule venue and registration from the immutable snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $category = ContentCategory::query()->withoutGlobalScopes()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'name' => 'Workshops', 'slug' => 'workshops']);
    $event = ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_category_id' => $category->getKey(), 'content_type' => ContentPostType::Event, 'title' => 'Website growth workshop', 'slug' => 'growth-workshop', 'excerpt' => 'A practical session for growing businesses.', 'featured_image' => 'https://cdn.example.test/event.webp', 'status' => ContentPostStatus::Published, 'published_at' => now()->subDay(), 'event_start_date' => now()->addWeek()->toDateString(), 'event_start_time' => '10:00', 'event_end_date' => now()->addWeek()->toDateString(), 'event_end_time' => '13:30', 'venue' => 'Ezyweb Studio', 'address' => 'MG Road, Bengaluru', 'registration_url' => 'https://events.example.test/register']);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Event, 'title' => 'Past event', 'slug' => 'past-event', 'status' => ContentPostStatus::Published, 'published_at' => now()->subMonth(), 'event_start_date' => now()->subWeek()->toDateString()]);
    ContentPost::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'content_type' => ContentPostType::Event, 'title' => 'Unpublished event', 'slug' => 'unpublished-event', 'status' => ContentPostStatus::Draft, 'event_start_date' => now()->addWeek()->toDateString()]);
    PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(), 'type' => 'events', 'variant' => 'list', 'content' => ['heading' => 'Upcoming workshops', 'event_selection_mode' => 'all', 'event_slugs' => '', 'event_category' => 'Workshops', 'event_timeframe' => 'upcoming', 'event_limit' => '4', 'event_sort' => 'soonest', 'event_columns' => '2', 'show_images' => '1', 'show_excerpts' => '1', 'show_dates' => '1', 'show_times' => '1', 'show_venues' => '1', 'show_addresses' => '1', 'show_categories' => '1', 'show_registration' => '1', 'registration_label' => 'Book a seat', 'show_archive_link' => '1', 'archive_label' => 'All events', 'image_fit' => 'contain']]);

    freezePublishedWebsite($website);
    $event->update(['title' => 'Unpublished event title']);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Upcoming workshops')->assertSee('Website growth workshop')->assertSee('A practical session for growing businesses.')->assertSee('Ezyweb Studio')->assertSee('MG Road, Bengaluru')->assertSee('10:00–13:30')->assertSee('href="https://events.example.test/register"', false)->assertSee('Book a seat')->assertSee('href="/events"', false)->assertSee('object-fit: contain', false)->assertDontSee('Past event')->assertDontSee('Unpublished event')->assertDontSee('Unpublished event title')->assertDontSee('site-card-grid', false);
    $this->get("http://{$hostname}/events/growth-workshop")->assertOk()->assertSee('Website growth workshop')->assertSee('Ezyweb Studio')->assertSee('MG Road, Bengaluru')->assertSee('10:00–13:30')->assertSee('href="https://events.example.test/register"', false)->assertSee('Register now');
});

it('renders filtered managed careers and application details from the immutable snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $job = JobOpening::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'Senior Product Designer', 'slug' => 'senior-product-designer', 'department' => 'Design', 'location' => 'Bengaluru', 'employment_type' => 'full-time', 'workplace_type' => 'hybrid', 'summary' => 'Shape thoughtful Ezyweb experiences.', 'description' => '<p>Lead product design across our platform.</p>', 'responsibilities' => '<ul><li>Own design systems</li></ul>', 'requirements' => '<ul><li>Five years experience</li></ul>', 'salary_display' => '₹18–24 LPA', 'closing_date' => now()->addMonth()->toDateString(), 'application_email' => 'careers@example.test', 'is_active' => true]);
    JobOpening::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'Expired position', 'slug' => 'expired-position', 'department' => 'Design', 'closing_date' => now()->subDay()->toDateString(), 'is_active' => true]);
    JobOpening::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'title' => 'Inactive position', 'slug' => 'inactive-position', 'department' => 'Design', 'is_active' => false]);
    PageSection::factory()->create(['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'page_id' => $home->getKey(), 'type' => 'careers', 'variant' => 'featured', 'content' => ['heading' => 'Build with Ezyweb', 'career_selection_mode' => 'all', 'career_slugs' => '', 'career_department' => 'Design', 'career_location' => 'Bengaluru', 'career_employment_type' => 'full-time', 'career_workplace_type' => 'hybrid', 'career_limit' => '6', 'career_sort' => 'closing-soonest', 'career_columns' => '2', 'show_summaries' => '1', 'show_departments' => '1', 'show_locations' => '1', 'show_employment_types' => '1', 'show_workplace_types' => '1', 'show_salaries' => '1', 'show_closing_dates' => '1', 'show_apply_button' => '1', 'apply_label' => 'Explore role', 'show_archive_link' => '1', 'archive_label' => 'All openings']]);

    freezePublishedWebsite($website);
    $job->update(['title' => 'Unpublished job title']);
    $this->get("http://{$hostname}/")->assertOk()->assertSee('Build with Ezyweb')->assertSee('Senior Product Designer')->assertSee('Shape thoughtful Ezyweb experiences.')->assertSee('Design')->assertSee('Bengaluru')->assertSee('Full Time')->assertSee('Hybrid')->assertSee('₹18–24 LPA')->assertSee('Explore role')->assertSee('href="/careers/senior-product-designer"', false)->assertSee('href="/careers"', false)->assertDontSee('Expired position')->assertDontSee('Inactive position')->assertDontSee('Unpublished job title')->assertDontSee('site-card-grid', false);
    $this->get("http://{$hostname}/careers/senior-product-designer")->assertOk()->assertSee('Senior Product Designer')->assertSee('Lead product design across our platform.')->assertSee('Responsibilities')->assertSee('Own design systems')->assertSee('Requirements')->assertSee('Five years experience')->assertSee('mailto:careers@example.test', false)->assertSee('Apply now');
});

it('uses section anchors for landing navigation and rejects page slugs', function () {
    [$hostname, $website, $home] = publicWebsite(['type' => WebsiteType::LandingPage]);
    foreach (['about', 'services', 'contact'] as $order => $type) {
        PageSection::factory()->create([
            'tenant_id' => $website->tenant_id,
            'website_id' => $website->getKey(),
            'page_id' => $home->getKey(),
            'type' => $type,
            'anchor' => $type,
            'sort_order' => $order,
        ]);
    }

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('href="#about"', false)
        ->assertSee('href="#services"', false)
        ->assertSee('href="#contact"', false);

    $this->get("http://{$hostname}/about")->assertNotFound();
});

it('routes enabled multipage slugs and excludes disabled pages from navigation', function () {
    [$hostname, $website] = publicWebsite();
    $about = Page::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'title' => 'About our studio',
        'slug' => 'about',
        'navigation_label' => 'About',
        'sort_order' => 1,
    ]);
    $hidden = Page::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'title' => 'Hidden page',
        'slug' => 'hidden',
        'is_enabled' => false,
        'sort_order' => 2,
    ]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $about->getKey(),
        'type' => 'about',
        'content' => ['heading' => 'Our real story', 'description' => 'This content belongs to the About page.'],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/about")
        ->assertOk()
        ->assertSee('Our real story')
        ->assertSee('href="/about"', false)
        ->assertDontSee('Hidden page');
    $this->get("http://{$hostname}/{$hidden->slug}")->assertNotFound();
    $this->get("http://{$hostname}/missing")->assertNotFound();
});

it('does not expose draft or unverified websites', function () {
    [$draftHost] = publicWebsite(['status' => WebsiteStatus::Draft]);
    $this->get("http://{$draftHost}/")->assertNotFound();

    [$unverifiedHost] = publicWebsite([], false);
    $this->get("http://{$unverifiedHost}/")->assertNotFound();
});

it('normalizes unsafe legacy content again at render time', function () {
    [$hostname, $website, $home] = publicWebsite();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'hero',
        'content' => [
            'heading' => '<script>alert(1)</script>Safe public heading',
            'description' => '<img src=x onerror=alert(1)>A safe description',
            'primary_label' => 'Unsafe link',
            'primary_url' => 'javascript:alert(1)',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Safe public heading')
        ->assertSee('A safe description')
        ->assertDontSee('<script>', false)
        ->assertDontSee('onerror=', false)
        ->assertDontSee('javascript:', false);
});

it('renders a managed newsletter signup form with consent privacy and layout options', function () {
    [$hostname, $website, $home] = publicWebsite();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'newsletter',
        'variant' => 'card',
        'content' => [
            'eyebrow' => 'Ezyweb insights', 'heading' => 'Ideas worth opening',
            'description' => 'A considered update for growing businesses.',
            'form_heading' => 'Join the newsletter', 'form_description' => 'Monthly and practical.',
            'name_label' => 'Your name', 'name_placeholder' => 'Priya', 'show_name_field' => '1',
            'email_field_label' => 'Work email', 'email_placeholder' => 'you@company.com',
            'button_label' => 'Subscribe now',
            'consent_label' => 'I agree to receive Ezyweb updates.',
            'privacy_label' => 'Read our privacy policy', 'privacy_url' => '/privacy',
            'privacy_note' => 'No spam. Unsubscribe at any time.', 'success_message' => 'Welcome aboard.',
            'show_form_heading' => '1', 'show_form_description' => '1', 'show_privacy_note' => '1',
            'form_width' => 'wide', 'form_alignment' => 'center', 'privacy_new_tab' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Ideas worth opening')
        ->assertSee('action="/newsletter/subscriptions"', false)
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="consent"', false)
        ->assertSee('Read our privacy policy')
        ->assertSee('href="/privacy"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('variant-card', false)
        ->assertSee('form-width-wide', false)
        ->assertSee('align-form-center', false)
        ->assertSee('No spam. Unsubscribe at any time.');
});

it('renders a responsive accessible hero image slider from the published snapshot', function () {
    [$hostname, $website, $home] = publicWebsite();
    $first = (string) Str::ulid();
    $second = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'hero-slider',
        'variant' => 'full-bleed',
        'content' => [
            'slides' => [
                ['image_id' => $first, 'mobile_image_id' => $second, 'alt' => 'Team collaborating', 'eyebrow' => 'Ezyweb', 'heading' => 'Build something memorable', 'description' => 'First slide description', 'primary_label' => 'Start now', 'primary_url' => '#contact'],
                ['image_id' => $second, 'mobile_image_id' => '', 'alt' => 'Second scene', 'eyebrow' => '', 'heading' => 'A second message', 'description' => '', 'primary_label' => '', 'primary_url' => ''],
            ],
            'autoplay' => '1', 'interval' => '6', 'show_arrows' => '1', 'show_dots' => '1',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('data-hero-slider', false)
        ->assertSee('data-interval="6000"', false)
        ->assertSee('Build something memorable')
        ->assertSee('A second message')
        ->assertSee('/media/'.$first.'/large', false)
        ->assertSee('/media/'.$second.'/medium', false)
        ->assertSee('aria-label="Previous slide"', false)
        ->assertSee('Team collaborating');
});

it('renders formatted rich text with an optional positioned image instead of placeholder art', function () {
    [$hostname, $website, $home] = publicWebsite();
    $image = (string) Str::ulid();
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'type' => 'rich-text',
        'variant' => 'wide',
        'content' => [
            'eyebrow' => 'About Ezyweb', 'heading' => 'Content with a real image',
            'body' => '<p>Rich <strong>formatted content</strong> for visitors.</p>',
            'image_id' => $image, 'image_alt' => 'Team discussion', 'image_caption' => 'Planning together',
            'image_position' => 'left',
        ],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('Content with a real image')
        ->assertSee('<strong>formatted content</strong>', false)
        ->assertSee('/media/'.$image.'/large', false)
        ->assertSee('image-left', false)
        ->assertSee('Team discussion')
        ->assertSee('Planning together')
        ->assertDontSee('site-content-art', false);
});

it('keeps page routing isolated to the resolved website', function () {
    [$hostname, $website] = publicWebsite();
    [, $otherWebsite] = publicWebsite();
    $foreignPage = Page::factory()->create([
        'tenant_id' => $otherWebsite->tenant_id,
        'website_id' => $otherWebsite->getKey(),
        'title' => 'Private tenant page',
        'slug' => 'private-page',
    ]);
    PageSection::factory()->create([
        'tenant_id' => $otherWebsite->tenant_id,
        'website_id' => $otherWebsite->getKey(),
        'page_id' => $foreignPage->getKey(),
        'content' => ['heading' => 'Never leak this content'],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/private-page")
        ->assertNotFound()
        ->assertDontSee('Never leak this content');
});

it('renders the selected template presentation', function (string $templateKey) {
    [$hostname, $website, $home] = publicWebsite(['template_key' => $templateKey]);
    PageSection::factory()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'page_id' => $home->getKey(),
        'content' => ['heading' => Str::headline($templateKey).' presentation'],
    ]);

    freezePublishedWebsite($website);
    $this->get("http://{$hostname}/")
        ->assertOk()
        ->assertSee('template-'.$templateKey, false)
        ->assertSee(Str::headline($templateKey).' presentation');
})->with(['horizon', 'localpro', 'canvas']);

/**
 * @param  array<string, mixed>  $websiteOverrides
 * @return array{string, Website, Page}
 */
function publicWebsite(array $websiteOverrides = [], bool $verified = true): array
{
    $tenant = Tenant::factory()->create();
    $website = Website::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'status' => WebsiteStatus::Published,
        'type' => WebsiteType::Multipage,
        'template_key' => 'horizon',
        ...$websiteOverrides,
    ]);
    $hostname = 'site-'.Str::lower(Str::random(10)).'.ezyweb.in';
    WebsiteDomain::factory()->forWebsite($website)->create([
        'hostname' => $hostname,
        'subdomain' => Str::before($hostname, '.'),
        'is_verified' => $verified,
        'verified_at' => $verified ? now() : null,
    ]);
    $home = Page::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'website_id' => $website->getKey(),
        'title' => 'Home',
        'slug' => 'home',
        'navigation_label' => 'Home',
        'is_home' => true,
        'sort_order' => 0,
    ]);

    return [$hostname, $website, $home];
}

function freezePublishedWebsite(Website $website): WebsiteVersion
{
    $website->refresh();
    $snapshot = app(WebsiteSnapshotFactory::class)->capture($website);
    $version = WebsiteVersion::query()->withoutGlobalScopes()->create([
        'tenant_id' => $website->tenant_id,
        'website_id' => $website->getKey(),
        'version_number' => 1,
        'reason' => 'renderer-test',
        'snapshot_json' => $snapshot,
        'created_by' => null,
        'published_at' => now(),
    ]);
    $website->update([
        'published_version_id' => $version->getKey(),
        'published_revision' => (int) $website->draft_revision,
        'publication_status' => 'published',
        'published_at' => now(),
    ]);

    return $version;
}
