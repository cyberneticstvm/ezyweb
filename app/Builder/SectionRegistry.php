<?php

namespace App\Builder;

use App\Content\RichTextSanitizer;
use Illuminate\Support\Str;

class SectionRegistry
{
    public function __construct(private readonly RichTextSanitizer $richText) {}

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $definitions = [
            'hero' => ['Hero', 'Conversion', ['default', 'split', 'contact-first', 'editorial']],
            'hero-slider' => ['Hero slider', 'Conversion', ['default', 'split', 'full-bleed']],
            'about' => ['About us', 'Story', ['default', 'image-left', 'image-right', 'centered']],
            'mission-vision' => ['Story, mission and vision', 'Story', ['default', 'side-by-side', 'cards']],
            'counters' => ['Client and project counters', 'Trust', ['default', 'cards', 'band']],
            'brand-carousel' => ['Brand or partner carousel', 'Trust', ['default', 'carousel', 'grid']],
            'services' => ['Services', 'Offerings', ['default', 'cards', 'icon-list', 'alternating']],
            'products' => ['Products or catalogue', 'Offerings', ['default', 'cards', 'featured']],
            'testimonials' => ['Testimonials', 'Trust', ['default', 'grid', 'carousel', 'featured', 'quote', 'slider']],
            'portfolio' => ['Portfolio', 'Work', ['default', 'grid', 'masonry', 'featured']],
            'gallery' => ['Gallery', 'Work', ['default', 'grid', 'masonry']],
            'team' => ['Team', 'Story', ['default', 'cards', 'compact']],
            'faq' => ['FAQ', 'Information', ['default', 'accordion', 'two-column']],
            'blog-posts' => ['Blog posts', 'Content', ['default', 'cards', 'featured']],
            'news' => ['News', 'Content', ['default', 'list', 'cards']],
            'events' => ['Events', 'Content', ['default', 'list', 'cards']],
            'careers' => ['Careers', 'Content', ['default', 'list', 'featured']],
            'cta' => ['Call to action', 'Conversion', ['default', 'band', 'split']],
            'features' => ['Features', 'Offerings', ['default', 'cards', 'icon-grid']],
            'process' => ['Process or steps', 'Information', ['default', 'numbered', 'timeline']],
            'pricing' => ['Pricing', 'Conversion', ['default', 'cards', 'comparison']],
            'contact' => ['Contact', 'Conversion', ['default', 'split', 'map-hours', 'centered']],
            'map' => ['Map', 'Information', ['default', 'full-width', 'split']],
            'newsletter' => ['Newsletter', 'Conversion', ['default', 'inline', 'card']],
            'rich-text' => ['Rich text', 'Content', ['default', 'narrow', 'wide']],
            'image-text' => ['Image and text', 'Content', ['default', 'image-left', 'image-right']],
            'video' => ['Video', 'Content', ['default', 'contained', 'full-width']],
            'logo-cloud' => ['Logo cloud', 'Trust', ['default', 'grid', 'band']],
            'statistics' => ['Statistics', 'Trust', ['default', 'cards', 'split']],
            'spacer-divider' => ['Spacer or divider', 'Layout', ['default', 'line', 'space']],
        ];

        $registry = [];
        foreach ($definitions as $type => [$label, $category, $variants]) {
            $registry[$type] = [
                'type' => $type,
                'label' => $label,
                'category' => $category,
                'description' => 'Add a controlled '.Str::lower($label).' section to this page.',
                'variants' => array_map(fn (string $variant): array => [
                    'key' => $variant,
                    'label' => Str::headline($variant),
                ], $variants),
                'fields' => $this->fieldsFor($type),
                'default_content' => $this->defaultContent($type, $label),
                'default_design' => $this->defaultDesign(),
            ];
        }

        return $registry;
    }

    /** @return array<string, mixed> */
    public function get(string $type): array
    {
        return $this->all()[$type] ?? [];
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->all());
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->all());
    }

    /** @return list<string> */
    public function variants(string $type): array
    {
        return array_column($this->get($type)['variants'] ?? [], 'key');
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function normalizeContent(string $type, array $content): array
    {
        if ($type === 'hero') {
            return $this->normalizeHero($content);
        }
        if ($type === 'hero-slider') {
            return $this->normalizeHeroSlider($content);
        }
        if ($type === 'about') {
            return $this->normalizeAbout($content);
        }
        if ($type === 'mission-vision') {
            return $this->normalizeMissionVision($content);
        }
        if ($type === 'counters') {
            return $this->normalizeCounters($content);
        }
        if ($type === 'brand-carousel') {
            return $this->normalizeBrandCarousel($content);
        }
        if ($type === 'products') {
            return $this->normalizeProducts($content);
        }
        if ($type === 'testimonials') {
            return $this->normalizeTestimonials($content);
        }
        if ($type === 'portfolio') {
            return $this->normalizePortfolio($content);
        }
        if ($type === 'gallery') {
            return $this->normalizeGallery($content);
        }
        if ($type === 'team') {
            return $this->normalizeTeam($content);
        }
        if ($type === 'faq') {
            return $this->normalizeFaq($content);
        }
        if ($type === 'blog-posts') {
            return $this->normalizeBlogPosts($content);
        }
        if ($type === 'news') {
            return $this->normalizeNews($content);
        }
        if ($type === 'events') {
            return $this->normalizeEvents($content);
        }
        if ($type === 'careers') {
            return $this->normalizeCareers($content);
        }
        if ($type === 'cta') {
            return $this->normalizeCta($content);
        }
        if ($type === 'features') {
            return $this->normalizeFeatures($content);
        }
        if ($type === 'process') {
            return $this->normalizeProcess($content);
        }
        if ($type === 'pricing') {
            return $this->normalizePricing($content);
        }
        if ($type === 'contact') {
            return $this->normalizeContact($content);
        }
        if ($type === 'map') {
            return $this->normalizeMap($content);
        }
        if ($type === 'newsletter') {
            return $this->normalizeNewsletter($content);
        }
        if ($type === 'rich-text') {
            return $this->normalizeRichText($content);
        }

        $normalized = [];
        foreach ($this->get($type)['fields'] ?? [] as $field) {
            $key = $field['key'];
            $value = $content[$key] ?? '';
            $normalizedValue = Str::limit(trim(strip_tags(is_string($value) ? $value : '')), (int) $field['max'], '');
            if ($field['type'] === 'url' && $normalizedValue !== '' && ! preg_match('/^(?:#|\/|https?:\/\/|mailto:|tel:)/i', $normalizedValue)) {
                $normalizedValue = '';
            }
            $normalized[$key] = $normalizedValue;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $design
     * @return array<string, string>
     */
    public function normalizeDesign(array $design): array
    {
        return [
            'surface' => in_array($design['surface'] ?? null, ['default', 'muted', 'primary', 'dark'], true) ? $design['surface'] : 'default',
            'spacing' => in_array($design['spacing'] ?? null, ['compact', 'normal', 'spacious'], true) ? $design['spacing'] : 'normal',
            'alignment' => in_array($design['alignment'] ?? null, ['left', 'center', 'right'], true) ? $design['alignment'] : 'left',
        ];
    }

    /** @return list<array{key: string, label: string, type: string, max: int}> */
    private function fieldsFor(string $type): array
    {
        if ($type === 'spacer-divider') {
            return [['key' => 'heading', 'label' => 'Internal label', 'type' => 'text', 'max' => 80]];
        }

        if ($type === 'hero-slider') {
            return [['key' => 'slides', 'label' => 'Slides', 'type' => 'slides', 'max' => 10]];
        }
        if ($type === 'cta') {
            return [['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'max' => 160]];
        }
        if ($type === 'features') {
            return [['key' => 'feature_items', 'label' => 'Features', 'type' => 'features', 'max' => 12]];
        }
        if ($type === 'process') {
            return [['key' => 'process_items', 'label' => 'Process steps', 'type' => 'process', 'max' => 12]];
        }
        if ($type === 'pricing') {
            return [['key' => 'pricing_items', 'label' => 'Pricing plans', 'type' => 'pricing', 'max' => 8]];
        }
        if ($type === 'contact') {
            return [['key' => 'heading', 'label' => 'Contact section', 'type' => 'contact', 'max' => 160]];
        }
        if ($type === 'map') {
            return [['key' => 'heading', 'label' => 'Map section', 'type' => 'map', 'max' => 160]];
        }
        if ($type === 'newsletter') {
            return [['key' => 'heading', 'label' => 'Newsletter section', 'type' => 'newsletter', 'max' => 160]];
        }

        if ($type === 'rich-text') {
            return [['key' => 'body', 'label' => 'Content', 'type' => 'richtext', 'max' => 30000]];
        }
        if ($type === 'about') {
            return [['key' => 'body', 'label' => 'Story', 'type' => 'richtext', 'max' => 30000]];
        }
        if ($type === 'mission-vision') {
            return [['key' => 'story_body', 'label' => 'Story', 'type' => 'richtext', 'max' => 30000]];
        }
        if ($type === 'counters') {
            return [['key' => 'items', 'label' => 'Counters', 'type' => 'counters', 'max' => 8]];
        }
        if ($type === 'brand-carousel') {
            return [['key' => 'logos', 'label' => 'Brands or partners', 'type' => 'logos', 'max' => 20]];
        }
        if ($type === 'products') {
            return [['key' => 'product_slugs', 'label' => 'Products', 'type' => 'products', 'max' => 50]];
        }
        if ($type === 'testimonials') {
            return [['key' => 'testimonial_limit', 'label' => 'Testimonials', 'type' => 'testimonials', 'max' => 20]];
        }
        if ($type === 'portfolio') {
            return [['key' => 'portfolio_slugs', 'label' => 'Portfolio projects', 'type' => 'portfolio', 'max' => 50]];
        }
        if ($type === 'gallery') {
            return [['key' => 'gallery_images', 'label' => 'Gallery images', 'type' => 'gallery', 'max' => 50]];
        }
        if ($type === 'team') {
            return [['key' => 'team_keys', 'label' => 'Team members', 'type' => 'team', 'max' => 50]];
        }
        if ($type === 'faq') {
            return [['key' => 'faq_keys', 'label' => 'FAQs', 'type' => 'faqs', 'max' => 50]];
        }
        if ($type === 'blog-posts') {
            return [['key' => 'blog_slugs', 'label' => 'Blog posts', 'type' => 'blog-posts', 'max' => 50]];
        }
        if ($type === 'news') {
            return [['key' => 'news_slugs', 'label' => 'News articles', 'type' => 'news', 'max' => 50]];
        }
        if ($type === 'events') {
            return [['key' => 'event_slugs', 'label' => 'Events', 'type' => 'events', 'max' => 50]];
        }
        if ($type === 'careers') {
            return [['key' => 'career_slugs', 'label' => 'Job openings', 'type' => 'careers', 'max' => 50]];
        }

        $fields = [
            ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'max' => 80],
            ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'max' => 160],
            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'max' => 2000],
        ];

        if (in_array($type, ['hero', 'hero-slider', 'cta', 'contact', 'newsletter', 'pricing', 'careers'], true)) {
            $fields[] = ['key' => 'primary_label', 'label' => 'Button label', 'type' => 'text', 'max' => 60];
            $fields[] = ['key' => 'primary_url', 'label' => 'Button URL', 'type' => 'url', 'max' => 2048];
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    private function defaultContent(string $type, string $label): array
    {
        if ($type === 'hero') {
            return [
                'eyebrow' => 'Welcome to your new website',
                'heading' => 'A clear message that moves your business forward',
                'description' => 'Use the editor to explain your strongest value proposition in a few useful sentences.',
                'primary_label' => 'Get started', 'primary_url' => '#contact',
                'secondary_label' => '', 'secondary_url' => '',
                'image_id' => '', 'mobile_image_id' => '', 'image_alt' => '',
                'image_position' => 'right', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'hero-slider') {
            return [
                'slides' => [[
                    'image_id' => '', 'mobile_image_id' => '', 'alt' => '',
                    'eyebrow' => 'Welcome to your new website',
                    'heading' => 'A clear message that moves your business forward',
                    'description' => 'Add an image from your media library and tailor this message for your visitors.',
                    'primary_label' => 'Get started', 'primary_url' => '#contact',
                ]],
                'autoplay' => '1', 'interval' => '5', 'show_arrows' => '1', 'show_dots' => '1',
            ];
        }

        if ($type === 'cta') {
            return [
                'eyebrow' => 'Ready when you are',
                'heading' => 'Take the next step with us',
                'description' => 'Give visitors one clear reason to act and make the next step easy.',
                'primary_label' => 'Get started', 'primary_url' => '#contact', 'primary_new_tab' => '0',
                'secondary_label' => '', 'secondary_url' => '', 'secondary_new_tab' => '0',
                'supporting_text' => 'No obligation. We will respond as soon as possible.',
                'image_id' => '', 'mobile_image_id' => '', 'image_alt' => '',
                'media_layout' => 'right', 'image_fit' => 'cover', 'overlay_strength' => '55',
            ];
        }

        if ($type === 'features') {
            return [
                'eyebrow' => 'Why choose us', 'heading' => 'Everything you need to move forward',
                'description' => 'Highlight the capabilities and benefits that make your offering valuable.',
                'feature_items' => [
                    ['icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Reliable by design', 'description' => 'Explain a meaningful benefit your customers can depend on.', 'link_label' => '', 'link_url' => '', 'is_featured' => '0'],
                    ['icon' => '★', 'image_id' => '', 'image_alt' => '', 'title' => 'Made for your needs', 'description' => 'Show how this feature solves a real customer problem.', 'link_label' => '', 'link_url' => '', 'is_featured' => '1'],
                    ['icon' => '↗', 'image_id' => '', 'image_alt' => '', 'title' => 'Ready to grow', 'description' => 'Describe the value customers gain as their needs expand.', 'link_label' => '', 'link_url' => '', 'is_featured' => '0'],
                ],
                'columns' => '3', 'show_icons' => '1', 'show_images' => '1', 'show_descriptions' => '1',
                'show_links' => '1', 'open_new_tab' => '0', 'image_fit' => 'cover', 'card_alignment' => 'left',
                'featured_label' => 'Popular',
            ];
        }

        if ($type === 'process') {
            return [
                'eyebrow' => 'How it works', 'heading' => 'A simple path from idea to outcome',
                'description' => 'Help visitors understand what happens next with a clear, reassuring process.',
                'process_items' => [
                    ['number' => '01', 'icon' => '✦', 'image_id' => '', 'image_alt' => '', 'title' => 'Discover', 'description' => 'We learn about your goals, audience, and priorities.', 'meta' => 'Step one', 'link_label' => '', 'link_url' => ''],
                    ['number' => '02', 'icon' => '✎', 'image_id' => '', 'image_alt' => '', 'title' => 'Plan', 'description' => 'We turn the requirements into a focused plan of action.', 'meta' => 'Step two', 'link_label' => '', 'link_url' => ''],
                    ['number' => '03', 'icon' => '✓', 'image_id' => '', 'image_alt' => '', 'title' => 'Deliver', 'description' => 'We complete, review, and launch the finished work.', 'meta' => 'Step three', 'link_label' => '', 'link_url' => ''],
                ],
                'process_columns' => '3', 'show_step_numbers' => '1', 'show_step_icons' => '0',
                'show_step_images' => '1', 'show_step_descriptions' => '1', 'show_step_meta' => '0',
                'show_step_links' => '1', 'open_new_tab' => '0', 'image_fit' => 'cover',
                'process_alignment' => 'left', 'process_connector' => 'solid',
            ];
        }

        if ($type === 'pricing') {
            return [
                'eyebrow' => 'Simple pricing', 'heading' => 'Choose the right option for you',
                'description' => 'Present clear choices so visitors can compare value and take the next step.',
                'pricing_items' => [
                    ['name' => 'Essential', 'badge' => '', 'description' => 'A focused starting point for essential needs.', 'currency' => '₹', 'price' => '9,999', 'original_price' => '', 'period' => 'one time', 'features' => "+ Essential feature\n+ Professional support\n- Advanced option", 'button_label' => 'Get started', 'button_url' => '#contact', 'is_featured' => '0'],
                    ['name' => 'Growth', 'badge' => 'Most popular', 'description' => 'More capability for organisations ready to grow.', 'currency' => '₹', 'price' => '19,999', 'original_price' => '', 'period' => 'one time', 'features' => "+ Everything in Essential\n+ Growth feature\n+ Priority support", 'button_label' => 'Choose Growth', 'button_url' => '#contact', 'is_featured' => '1'],
                ],
                'pricing_columns' => '2', 'currency_position' => 'before', 'show_plan_descriptions' => '1',
                'show_plan_features' => '1', 'show_plan_buttons' => '1', 'show_plan_badges' => '1',
                'show_original_prices' => '1', 'open_new_tab' => '0',
                'pricing_note' => 'Need something different? Contact us for a tailored quote.',
            ];
        }
        if ($type === 'contact') {
            return [
                'eyebrow' => 'Contact us', 'heading' => 'Let’s start a conversation',
                'description' => 'Tell us what you need and our team will get back to you.',
                'details_heading' => 'Contact details',
                'address_label' => 'Address', 'phone_label' => 'Phone', 'email_label' => 'Email',
                'hours_label' => 'Working hours', 'map_title' => 'Map showing our location',
                'form_heading' => 'Send an enquiry', 'form_description' => 'Complete the form and we will respond as soon as possible.',
                'name_label' => 'Name', 'name_placeholder' => 'Your name',
                'email_field_label' => 'Email', 'email_placeholder' => 'you@example.com',
                'phone_field_label' => 'Phone', 'phone_placeholder' => 'Your phone number',
                'subject_label' => 'Subject', 'subject_placeholder' => 'How can we help?',
                'message_label' => 'Message', 'message_placeholder' => 'Tell us about your enquiry',
                'submit_label' => 'Send enquiry', 'privacy_note' => '',
                'show_details_heading' => '1', 'show_address' => '1', 'show_phone_numbers' => '1',
                'show_email_addresses' => '1', 'show_working_hours' => '1', 'show_map' => '1',
                'show_form' => '1', 'show_form_heading' => '1', 'show_form_description' => '1',
                'form_position' => 'right', 'map_height' => 'medium',
            ];
        }
        if ($type === 'map') {
            return [
                'eyebrow' => 'Find us', 'heading' => 'Visit our location',
                'description' => 'Use the map and directions below to plan your visit.',
                'location_heading' => 'Our location', 'address_label' => 'Address',
                'hours_label' => 'Working hours', 'location_note' => '',
                'map_title' => 'Map showing our location', 'directions_label' => 'Get directions',
                'directions_url' => '', 'contact_label' => 'Contact us', 'contact_url' => '#contact',
                'show_location_details' => '1', 'show_address' => '1', 'show_working_hours' => '1',
                'show_map' => '1', 'show_directions_button' => '1', 'show_contact_button' => '0',
                'open_new_tab' => '1', 'map_position' => 'right', 'map_height' => 'medium',
            ];
        }
        if ($type === 'newsletter') {
            return [
                'eyebrow' => 'Stay informed', 'heading' => 'Useful updates, delivered occasionally',
                'description' => 'Subscribe for news, practical ideas, and important announcements.',
                'form_heading' => 'Join our newsletter', 'form_description' => '',
                'name_label' => 'Name', 'name_placeholder' => 'Your name', 'show_name_field' => '0',
                'email_field_label' => 'Email address', 'email_placeholder' => 'you@example.com',
                'button_label' => 'Subscribe',
                'consent_label' => 'I agree to receive newsletter emails and can unsubscribe at any time.',
                'privacy_label' => 'Privacy policy', 'privacy_url' => '',
                'privacy_note' => 'No spam. Unsubscribe whenever you like.',
                'success_message' => 'Thank you for subscribing.',
                'show_form_heading' => '0', 'show_form_description' => '0', 'show_privacy_note' => '1',
                'form_width' => 'medium', 'form_alignment' => 'left', 'privacy_new_tab' => '0',
            ];
        }

        if ($type === 'rich-text') {
            return [
                'eyebrow' => '', 'heading' => 'Rich text',
                'body' => '<p>Use the editor to add formatted content for your visitors.</p>',
                'image_id' => '', 'image_alt' => '', 'image_caption' => '', 'image_position' => 'right',
            ];
        }
        if ($type === 'about') {
            return [
                'eyebrow' => 'About us', 'heading' => 'The story behind our business',
                'body' => '<p>Share what your business does, who it serves, and what makes your approach different.</p>',
                'primary_label' => '', 'primary_url' => '',
                'image_id' => '', 'mobile_image_id' => '', 'image_alt' => '', 'image_caption' => '',
                'image_position' => 'right', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'mission-vision') {
            return [
                'eyebrow' => 'Our purpose', 'heading' => 'Our story, mission and vision',
                'story_heading' => 'Our story',
                'story_body' => '<p>Share how your organisation began and the journey that shaped who you are today.</p>',
                'story_image_id' => '', 'story_mobile_image_id' => '', 'story_image_alt' => '', 'story_image_caption' => '',
                'mission_heading' => 'Our mission',
                'mission_body' => '<p>Explain what you do, who you serve, and the impact you work to create every day.</p>',
                'mission_image_id' => '', 'mission_mobile_image_id' => '', 'mission_image_alt' => '', 'mission_image_caption' => '',
                'vision_heading' => 'Our vision',
                'vision_body' => '<p>Describe the future your organisation is committed to helping build.</p>',
                'vision_image_id' => '', 'vision_mobile_image_id' => '', 'vision_image_alt' => '', 'vision_image_caption' => '',
                'image_position' => 'left', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'counters') {
            return [
                'eyebrow' => 'Our impact', 'heading' => 'Trusted results, measured',
                'description' => 'Use meaningful numbers to show the scale and experience behind your work.',
                'items' => [
                    ['value' => '0', 'prefix' => '', 'suffix' => '+', 'label' => 'Clients served', 'description' => ''],
                    ['value' => '0', 'prefix' => '', 'suffix' => '+', 'label' => 'Projects delivered', 'description' => ''],
                ],
                'animate' => '1', 'duration' => '2', 'use_grouping' => '1',
            ];
        }
        if ($type === 'brand-carousel') {
            return [
                'eyebrow' => 'Trusted by', 'heading' => 'Brands and partners',
                'description' => 'Show the organisations that trust, support, or work with your business.',
                'logos' => [],
                'autoplay' => '1', 'interval' => '4', 'show_arrows' => '1',
                'pause_on_hover' => '1', 'show_names' => '0', 'grayscale' => '0', 'open_new_tab' => '0',
            ];
        }
        if ($type === 'products') {
            return [
                'eyebrow' => 'Our products', 'heading' => 'Explore our catalogue',
                'description' => 'Discover products designed to meet your needs.',
                'selection_mode' => 'all', 'product_slugs' => '', 'featured_only' => '0', 'limit' => '6', 'sort' => 'display-order',
                'columns' => '3', 'show_images' => '1', 'show_descriptions' => '1', 'show_prices' => '1',
                'price_prefix' => '₹', 'show_featured_badge' => '1', 'featured_label' => 'Featured',
                'show_button' => '1', 'button_label' => '', 'button_url' => '#contact', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'testimonials') {
            return [
                'eyebrow' => 'Testimonials', 'heading' => 'What our clients say',
                'description' => 'Share genuine experiences from the people and organisations you serve.',
                'testimonial_featured_only' => '0', 'testimonial_limit' => '6', 'testimonial_sort' => 'display-order',
                'show_photos' => '1', 'show_roles' => '1', 'show_companies' => '1', 'show_ratings' => '1',
                'autoplay' => '1', 'interval' => '6', 'show_arrows' => '1', 'show_dots' => '1',
                'pause_on_hover' => '1', 'slides_per_view' => '1',
            ];
        }
        if ($type === 'portfolio') {
            return [
                'eyebrow' => 'Our work', 'heading' => 'Selected projects',
                'description' => 'Explore the work, ideas, and outcomes we have delivered for our clients.',
                'portfolio_selection_mode' => 'all', 'portfolio_slugs' => '', 'portfolio_category' => '',
                'portfolio_featured_only' => '0', 'portfolio_limit' => '6', 'portfolio_sort' => 'display-order',
                'portfolio_columns' => '3', 'show_images' => '1', 'show_descriptions' => '1',
                'show_categories' => '1', 'show_clients' => '1', 'show_dates' => '1', 'show_locations' => '0',
                'show_button' => '1', 'button_label' => 'View project', 'image_fit' => 'cover', 'open_new_tab' => '0',
            ];
        }
        if ($type === 'gallery') {
            return [
                'eyebrow' => 'Gallery', 'heading' => 'A closer look',
                'description' => 'Browse moments, details, and highlights from our work.',
                'gallery_selection_mode' => 'all', 'gallery_images' => '', 'gallery_category' => '',
                'gallery_limit' => '12', 'gallery_sort' => 'display-order', 'gallery_columns' => '3',
                'show_titles' => '1', 'show_captions' => '1', 'show_categories' => '0',
                'respect_aspect_ratios' => '1', 'image_fit' => 'cover', 'gallery_gap' => 'normal',
                'enable_lightbox' => '1', 'lightbox_captions' => '1',
            ];
        }
        if ($type === 'team') {
            return [
                'eyebrow' => 'Our team', 'heading' => 'Meet the people behind our work',
                'description' => 'Introduce the people, experience, and perspectives that move your organisation forward.',
                'team_selection_mode' => 'all', 'team_keys' => '', 'team_role' => '',
                'team_featured_only' => '0', 'team_limit' => '8', 'team_sort' => 'display-order', 'team_columns' => '4',
                'show_photos' => '1', 'show_roles' => '1', 'show_bios' => '1',
                'show_email' => '0', 'show_phone' => '0', 'show_linkedin' => '1',
                'image_shape' => 'portrait', 'image_fit' => 'cover', 'card_alignment' => 'left',
                'show_featured_badge' => '0', 'featured_label' => 'Leadership',
            ];
        }
        if ($type === 'faq') {
            return [
                'eyebrow' => 'Frequently asked questions', 'heading' => 'Helpful answers',
                'description' => 'Find clear answers to the questions people ask most often.',
                'faq_selection_mode' => 'all', 'faq_keys' => '', 'faq_category' => '',
                'faq_limit' => '12', 'faq_sort' => 'display-order',
                'show_categories' => '1', 'show_numbers' => '0', 'default_open' => 'first',
                'allow_multiple' => '1', 'enable_search' => '0', 'search_placeholder' => 'Search questions…',
                'show_expand_controls' => '0', 'show_cta' => '0',
                'cta_text' => 'Still have a question?', 'cta_label' => 'Contact us', 'cta_url' => '#contact',
            ];
        }
        if ($type === 'blog-posts') {
            return [
                'eyebrow' => 'From the blog', 'heading' => 'Latest insights',
                'description' => 'Explore useful ideas, news, and practical guidance from our team.',
                'blog_selection_mode' => 'all', 'blog_slugs' => '', 'blog_category' => '',
                'blog_limit' => '6', 'blog_sort' => 'newest', 'blog_columns' => '3',
                'show_images' => '1', 'show_excerpts' => '1', 'show_authors' => '1',
                'show_dates' => '1', 'show_categories' => '1', 'show_tags' => '0',
                'show_reading_time' => '1', 'read_more_label' => 'Read article',
                'show_archive_link' => '1', 'archive_label' => 'View all posts', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'news') {
            return [
                'eyebrow' => 'Newsroom', 'heading' => 'Latest news',
                'description' => 'Read the latest announcements, updates, and stories from our organisation.',
                'news_selection_mode' => 'all', 'news_slugs' => '', 'news_category' => '',
                'news_limit' => '6', 'news_sort' => 'newest', 'news_columns' => '3',
                'show_images' => '1', 'show_excerpts' => '1', 'show_authors' => '1',
                'show_dates' => '1', 'show_categories' => '1', 'show_tags' => '0',
                'show_reading_time' => '0', 'read_more_label' => 'Read news',
                'show_archive_link' => '1', 'archive_label' => 'View all news', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'events') {
            return [
                'eyebrow' => 'Events', 'heading' => 'Upcoming events',
                'description' => 'Discover upcoming events, workshops, and opportunities to connect with us.',
                'event_selection_mode' => 'all', 'event_slugs' => '', 'event_category' => '',
                'event_timeframe' => 'upcoming', 'event_limit' => '6', 'event_sort' => 'soonest', 'event_columns' => '3',
                'show_images' => '1', 'show_excerpts' => '1', 'show_dates' => '1', 'show_times' => '1',
                'show_venues' => '1', 'show_addresses' => '0', 'show_categories' => '1',
                'show_registration' => '1', 'registration_label' => 'Register now',
                'show_archive_link' => '1', 'archive_label' => 'View all events', 'image_fit' => 'cover',
            ];
        }
        if ($type === 'careers') {
            return [
                'eyebrow' => 'Careers', 'heading' => 'Join our team',
                'description' => 'Explore current opportunities and build meaningful work with our team.',
                'career_selection_mode' => 'all', 'career_slugs' => '', 'career_department' => '',
                'career_location' => '', 'career_employment_type' => '', 'career_workplace_type' => '',
                'career_limit' => '8', 'career_sort' => 'display-order', 'career_columns' => '2',
                'show_summaries' => '1', 'show_departments' => '1', 'show_locations' => '1',
                'show_employment_types' => '1', 'show_workplace_types' => '1', 'show_salaries' => '1',
                'show_closing_dates' => '1', 'show_apply_button' => '1', 'apply_label' => 'View position',
                'show_archive_link' => '1', 'archive_label' => 'View all openings',
            ];
        }

        $content = [
            'eyebrow' => $type === 'hero' ? 'Welcome to your new website' : '',
            'heading' => $type === 'hero' ? 'A clear message that moves your business forward' : $label,
            'description' => 'Use the editor to replace this text with concise, useful information for your visitors.',
        ];

        if (in_array($type, ['hero', 'hero-slider', 'cta', 'contact', 'newsletter', 'pricing', 'careers'], true)) {
            $content['primary_label'] = 'Get started';
            $content['primary_url'] = '#contact';
        }

        return $content;
    }

    /** @return array<string, string> */
    private function defaultDesign(): array
    {
        return ['surface' => 'default', 'spacing' => 'normal', 'alignment' => 'left'];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeHeroSlider(array $content): array
    {
        $slides = is_array($content['slides'] ?? null) ? $content['slides'] : [];
        if ($slides === [] && collect(['eyebrow', 'heading', 'description', 'primary_label'])->contains(fn (string $key): bool => filled($content[$key] ?? null))) {
            $slides[] = [
                'image_id' => '', 'mobile_image_id' => '', 'alt' => '',
                'eyebrow' => $content['eyebrow'] ?? '', 'heading' => $content['heading'] ?? '',
                'description' => $content['description'] ?? '', 'primary_label' => $content['primary_label'] ?? '',
                'primary_url' => $content['primary_url'] ?? '',
            ];
        }

        $normalizedSlides = collect($slides)->filter(fn (mixed $slide): bool => is_array($slide))->take(10)->map(function (array $slide): array {
            $normalized = [];
            foreach ([
                'image_id' => 26, 'mobile_image_id' => 26, 'alt' => 255, 'eyebrow' => 80,
                'heading' => 160, 'description' => 1000, 'primary_label' => 60, 'primary_url' => 2048,
            ] as $key => $max) {
                $value = Str::limit(trim(strip_tags(is_string($slide[$key] ?? null) ? $slide[$key] : '')), $max, '');
                if (in_array($key, ['image_id', 'mobile_image_id'], true) && $value !== '' && ! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
                    $value = '';
                }
                if ($key === 'primary_url' && $value !== '' && ! preg_match('/^(?:#|\/|https?:\/\/|mailto:|tel:)/i', $value)) {
                    $value = '';
                }
                $normalized[$key] = $value;
            }

            return $normalized;
        })->values()->all();

        if ($normalizedSlides === []) {
            $normalizedSlides = $this->defaultContent('hero-slider', 'Hero slider')['slides'];
        }

        return [
            'slides' => $normalizedSlides,
            'autoplay' => $this->flag($content['autoplay'] ?? '1'),
            'interval' => (string) min(15, max(3, (int) ($content['interval'] ?? 5))),
            'show_arrows' => $this->flag($content['show_arrows'] ?? '1'),
            'show_dots' => $this->flag($content['show_dots'] ?? '1'),
        ];
    }

    private function flag(mixed $value): string
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true) ? '1' : '0';
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeHero(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $imageId = $this->mediaId($plain('image_id', 26));
        $mobileImageId = $this->mediaId($plain('mobile_image_id', 26));

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'primary_label' => $plain('primary_label', 60),
            'primary_url' => $this->safeUrl($plain('primary_url', 2048)),
            'secondary_label' => $plain('secondary_label', 60),
            'secondary_url' => $this->safeUrl($plain('secondary_url', 2048)),
            'image_id' => $imageId,
            'mobile_image_id' => $mobileImageId,
            'image_alt' => $plain('image_alt', 255),
            'image_position' => in_array($content['image_position'] ?? null, ['left', 'right'], true) ? $content['image_position'] : 'right',
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeCta(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'primary_label' => $plain('primary_label', 60),
            'primary_url' => $this->safeUrl($plain('primary_url', 2048)),
            'primary_new_tab' => $this->flag($content['primary_new_tab'] ?? '0'),
            'secondary_label' => $plain('secondary_label', 60),
            'secondary_url' => $this->safeUrl($plain('secondary_url', 2048)),
            'secondary_new_tab' => $this->flag($content['secondary_new_tab'] ?? '0'),
            'supporting_text' => $plain('supporting_text', 300),
            'image_id' => $this->mediaId($plain('image_id', 26)),
            'mobile_image_id' => $this->mediaId($plain('mobile_image_id', 26)),
            'image_alt' => $plain('image_alt', 255),
            'media_layout' => in_array($content['media_layout'] ?? null, ['left', 'right', 'background'], true) ? $content['media_layout'] : 'right',
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'overlay_strength' => (string) min(85, max(20, (int) ($content['overlay_strength'] ?? 55))),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeFeatures(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $items = is_array($content['feature_items'] ?? null) ? $content['feature_items'] : [];
        $normalizedItems = collect($items)->filter(fn (mixed $item): bool => is_array($item))->take(12)->map(function (array $item): array {
            $text = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($item[$key] ?? null) ? $item[$key] : '')), $max, '');

            return [
                'icon' => $text('icon', 12),
                'image_id' => $this->mediaId($text('image_id', 26)),
                'image_alt' => $text('image_alt', 255),
                'title' => $text('title', 120),
                'description' => $text('description', 600),
                'link_label' => $text('link_label', 60),
                'link_url' => $this->safeUrl($text('link_url', 2048)),
                'is_featured' => $this->flag($item['is_featured'] ?? '0'),
            ];
        })->filter(fn (array $item): bool => $item['title'] !== '')->values()->all();

        if ($normalizedItems === []) {
            $normalizedItems = $this->defaultContent('features', 'Features')['feature_items'];
        }

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'feature_items' => $normalizedItems,
            'columns' => in_array((string) ($content['columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['columns'] : '3',
            'show_icons' => $this->flag($content['show_icons'] ?? '1'),
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_descriptions' => $this->flag($content['show_descriptions'] ?? '1'),
            'show_links' => $this->flag($content['show_links'] ?? '1'),
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '0'),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'card_alignment' => in_array($content['card_alignment'] ?? null, ['left', 'center'], true) ? $content['card_alignment'] : 'left',
            'featured_label' => $plain('featured_label', 40),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeProcess(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $items = is_array($content['process_items'] ?? null) ? $content['process_items'] : [];
        $normalizedItems = collect($items)->filter(fn (mixed $item): bool => is_array($item))->take(12)->map(function (array $item, int $index): array {
            $text = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($item[$key] ?? null) ? $item[$key] : '')), $max, '');

            return [
                'number' => $text('number', 12) ?: str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'icon' => $text('icon', 12),
                'image_id' => $this->mediaId($text('image_id', 26)),
                'image_alt' => $text('image_alt', 255),
                'title' => $text('title', 120),
                'description' => $text('description', 800),
                'meta' => $text('meta', 80),
                'link_label' => $text('link_label', 60),
                'link_url' => $this->safeUrl($text('link_url', 2048)),
            ];
        })->filter(fn (array $item): bool => $item['title'] !== '')->values()->all();

        if ($normalizedItems === []) {
            $normalizedItems = $this->defaultContent('process', 'Process or steps')['process_items'];
        }

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'process_items' => $normalizedItems,
            'process_columns' => in_array((string) ($content['process_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['process_columns'] : '3',
            'show_step_numbers' => $this->flag($content['show_step_numbers'] ?? '1'),
            'show_step_icons' => $this->flag($content['show_step_icons'] ?? '0'),
            'show_step_images' => $this->flag($content['show_step_images'] ?? '1'),
            'show_step_descriptions' => $this->flag($content['show_step_descriptions'] ?? '1'),
            'show_step_meta' => $this->flag($content['show_step_meta'] ?? '0'),
            'show_step_links' => $this->flag($content['show_step_links'] ?? '1'),
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '0'),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'process_alignment' => in_array($content['process_alignment'] ?? null, ['left', 'center'], true) ? $content['process_alignment'] : 'left',
            'process_connector' => in_array($content['process_connector'] ?? null, ['solid', 'dashed', 'none'], true) ? $content['process_connector'] : 'solid',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizePricing(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $items = is_array($content['pricing_items'] ?? null) ? $content['pricing_items'] : [];
        $normalizedItems = collect($items)->filter(fn (mixed $item): bool => is_array($item))->take(8)->map(function (array $item): array {
            $text = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($item[$key] ?? null) ? $item[$key] : '')), $max, '');
            $features = collect(preg_split('/\r\n|\r|\n/', $text('features', 3000)) ?: [])
                ->map(fn (string $line): string => Str::limit(trim($line), 180, ''))
                ->filter()->take(20)->implode("\n");

            return [
                'name' => $text('name', 100),
                'badge' => $text('badge', 40),
                'description' => $text('description', 500),
                'currency' => $text('currency', 12),
                'price' => $text('price', 40),
                'original_price' => $text('original_price', 40),
                'period' => $text('period', 60),
                'features' => $features,
                'button_label' => $text('button_label', 60),
                'button_url' => $this->safeUrl($text('button_url', 2048)),
                'is_featured' => $this->flag($item['is_featured'] ?? '0'),
            ];
        })->filter(fn (array $item): bool => $item['name'] !== '')->values()->all();

        if ($normalizedItems === []) {
            $normalizedItems = $this->defaultContent('pricing', 'Pricing')['pricing_items'];
        }

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'pricing_items' => $normalizedItems,
            'pricing_columns' => in_array((string) ($content['pricing_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['pricing_columns'] : '2',
            'currency_position' => ($content['currency_position'] ?? null) === 'after' ? 'after' : 'before',
            'show_plan_descriptions' => $this->flag($content['show_plan_descriptions'] ?? '1'),
            'show_plan_features' => $this->flag($content['show_plan_features'] ?? '1'),
            'show_plan_buttons' => $this->flag($content['show_plan_buttons'] ?? '1'),
            'show_plan_badges' => $this->flag($content['show_plan_badges'] ?? '1'),
            'show_original_prices' => $this->flag($content['show_original_prices'] ?? '1'),
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '0'),
            'pricing_note' => $plain('pricing_note', 300),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeContact(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'details_heading' => $plain('details_heading', 120),
            'address_label' => $plain('address_label', 60),
            'phone_label' => $plain('phone_label', 60),
            'email_label' => $plain('email_label', 60),
            'hours_label' => $plain('hours_label', 60),
            'map_title' => $plain('map_title', 160),
            'form_heading' => $plain('form_heading', 120),
            'form_description' => $plain('form_description', 1000),
            'name_label' => $plain('name_label', 60),
            'name_placeholder' => $plain('name_placeholder', 120),
            'email_field_label' => $plain('email_field_label', 60),
            'email_placeholder' => $plain('email_placeholder', 120),
            'phone_field_label' => $plain('phone_field_label', 60),
            'phone_placeholder' => $plain('phone_placeholder', 120),
            'subject_label' => $plain('subject_label', 60),
            'subject_placeholder' => $plain('subject_placeholder', 160),
            'message_label' => $plain('message_label', 60),
            'message_placeholder' => $plain('message_placeholder', 200),
            'submit_label' => $plain('submit_label', 60),
            'privacy_note' => $plain('privacy_note', 500),
            'show_details_heading' => $this->flag($content['show_details_heading'] ?? '1'),
            'show_address' => $this->flag($content['show_address'] ?? '1'),
            'show_phone_numbers' => $this->flag($content['show_phone_numbers'] ?? '1'),
            'show_email_addresses' => $this->flag($content['show_email_addresses'] ?? '1'),
            'show_working_hours' => $this->flag($content['show_working_hours'] ?? '1'),
            'show_map' => $this->flag($content['show_map'] ?? '1'),
            'show_form' => $this->flag($content['show_form'] ?? '1'),
            'show_form_heading' => $this->flag($content['show_form_heading'] ?? '1'),
            'show_form_description' => $this->flag($content['show_form_description'] ?? '1'),
            'form_position' => ($content['form_position'] ?? null) === 'left' ? 'left' : 'right',
            'map_height' => in_array($content['map_height'] ?? null, ['small', 'medium', 'large'], true) ? $content['map_height'] : 'medium',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeMap(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'location_heading' => $plain('location_heading', 120),
            'address_label' => $plain('address_label', 60),
            'hours_label' => $plain('hours_label', 60),
            'location_note' => $plain('location_note', 500),
            'map_title' => $plain('map_title', 160),
            'directions_label' => $plain('directions_label', 60),
            'directions_url' => $this->safeUrl($plain('directions_url', 2048)),
            'contact_label' => $plain('contact_label', 60),
            'contact_url' => $this->safeUrl($plain('contact_url', 2048)),
            'show_location_details' => $this->flag($content['show_location_details'] ?? '1'),
            'show_address' => $this->flag($content['show_address'] ?? '1'),
            'show_working_hours' => $this->flag($content['show_working_hours'] ?? '1'),
            'show_map' => $this->flag($content['show_map'] ?? '1'),
            'show_directions_button' => $this->flag($content['show_directions_button'] ?? '1'),
            'show_contact_button' => $this->flag($content['show_contact_button'] ?? '0'),
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '1'),
            'map_position' => ($content['map_position'] ?? null) === 'left' ? 'left' : 'right',
            'map_height' => in_array($content['map_height'] ?? null, ['small', 'medium', 'large'], true) ? $content['map_height'] : 'medium',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeNewsletter(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 2000),
            'form_heading' => $plain('form_heading', 120),
            'form_description' => $plain('form_description', 1000),
            'name_label' => $plain('name_label', 60),
            'name_placeholder' => $plain('name_placeholder', 120),
            'show_name_field' => $this->flag($content['show_name_field'] ?? '0'),
            'email_field_label' => $plain('email_field_label', 60),
            'email_placeholder' => $plain('email_placeholder', 120),
            'button_label' => $plain('button_label', 60),
            'consent_label' => $plain('consent_label', 500),
            'privacy_label' => $plain('privacy_label', 60),
            'privacy_url' => $this->safeUrl($plain('privacy_url', 2048)),
            'privacy_note' => $plain('privacy_note', 500),
            'success_message' => $plain('success_message', 500),
            'show_form_heading' => $this->flag($content['show_form_heading'] ?? '0'),
            'show_form_description' => $this->flag($content['show_form_description'] ?? '0'),
            'show_privacy_note' => $this->flag($content['show_privacy_note'] ?? '1'),
            'form_width' => in_array($content['form_width'] ?? null, ['compact', 'medium', 'wide'], true) ? $content['form_width'] : 'medium',
            'form_alignment' => ($content['form_alignment'] ?? null) === 'center' ? 'center' : 'left',
            'privacy_new_tab' => $this->flag($content['privacy_new_tab'] ?? '0'),
        ];
    }

    private function mediaId(string $value): string
    {
        return $value !== '' && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) !== 1 ? '' : $value;
    }

    private function safeUrl(string $value): string
    {
        return $value !== '' && preg_match('/^(?:#|\/|https?:\/\/|mailto:|tel:)/i', $value) !== 1 ? '' : $value;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeRichText(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $imageId = $this->mediaId($plain('image_id', 26));
        $legacyBody = is_string($content['description'] ?? null) ? $content['description'] : '';
        $body = is_string($content['body'] ?? null) ? $content['body'] : $legacyBody;

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'body' => $this->richText->sanitize(mb_substr($body, 0, 30000)),
            'image_id' => $imageId,
            'image_alt' => $plain('image_alt', 255),
            'image_caption' => $plain('image_caption', 500),
            'image_position' => in_array($content['image_position'] ?? null, ['left', 'right'], true) ? $content['image_position'] : 'right',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeAbout(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $legacyBody = is_string($content['description'] ?? null) ? $content['description'] : '';
        $body = is_string($content['body'] ?? null) ? $content['body'] : $legacyBody;

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'body' => $this->richText->sanitize(mb_substr($body, 0, 30000)),
            'primary_label' => $plain('primary_label', 60),
            'primary_url' => $this->safeUrl($plain('primary_url', 2048)),
            'image_id' => $this->mediaId($plain('image_id', 26)),
            'mobile_image_id' => $this->mediaId($plain('mobile_image_id', 26)),
            'image_alt' => $plain('image_alt', 255),
            'image_caption' => $plain('image_caption', 500),
            'image_position' => in_array($content['image_position'] ?? null, ['left', 'right'], true) ? $content['image_position'] : 'right',
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeMissionVision(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $legacyStory = is_string($content['description'] ?? null) ? $content['description'] : '';
        $rich = function (string $key, string $fallback = '') use ($content): string {
            $value = is_string($content[$key] ?? null) ? $content[$key] : $fallback;

            return $this->richText->sanitize(mb_substr($value, 0, 30000));
        };

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'story_heading' => $plain('story_heading', 160) ?: 'Our story',
            'story_body' => $rich('story_body', $legacyStory),
            'story_image_id' => $this->mediaId($plain('story_image_id', 26)),
            'story_mobile_image_id' => $this->mediaId($plain('story_mobile_image_id', 26)),
            'story_image_alt' => $plain('story_image_alt', 255),
            'story_image_caption' => $plain('story_image_caption', 500),
            'mission_heading' => $plain('mission_heading', 160) ?: 'Our mission',
            'mission_body' => $rich('mission_body'),
            'mission_image_id' => $this->mediaId($plain('mission_image_id', 26)),
            'mission_mobile_image_id' => $this->mediaId($plain('mission_mobile_image_id', 26)),
            'mission_image_alt' => $plain('mission_image_alt', 255),
            'mission_image_caption' => $plain('mission_image_caption', 500),
            'vision_heading' => $plain('vision_heading', 160) ?: 'Our vision',
            'vision_body' => $rich('vision_body'),
            'vision_image_id' => $this->mediaId($plain('vision_image_id', 26)),
            'vision_mobile_image_id' => $this->mediaId($plain('vision_mobile_image_id', 26)),
            'vision_image_alt' => $plain('vision_image_alt', 255),
            'vision_image_caption' => $plain('vision_image_caption', 500),
            'image_position' => in_array($content['image_position'] ?? null, ['left', 'right'], true) ? $content['image_position'] : 'left',
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeCounters(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $items = is_array($content['items'] ?? null) ? $content['items'] : [];
        $normalizedItems = collect($items)->filter(fn (mixed $item): bool => is_array($item))->take(8)->map(function (array $item): array {
            $value = is_numeric($item['value'] ?? null) ? (int) $item['value'] : 0;

            return [
                'value' => (string) min(999999999, max(0, $value)),
                'prefix' => Str::limit(trim(strip_tags(is_string($item['prefix'] ?? null) ? $item['prefix'] : '')), 12, ''),
                'suffix' => Str::limit(trim(strip_tags(is_string($item['suffix'] ?? null) ? $item['suffix'] : '')), 12, ''),
                'label' => Str::limit(trim(strip_tags(is_string($item['label'] ?? null) ? $item['label'] : '')), 100, ''),
                'description' => Str::limit(trim(strip_tags(is_string($item['description'] ?? null) ? $item['description'] : '')), 300, ''),
            ];
        })->filter(fn (array $item): bool => $item['label'] !== '')->values()->all();

        if ($normalizedItems === []) {
            $normalizedItems = $this->defaultContent('counters', 'Client and project counters')['items'];
        }

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'items' => $normalizedItems,
            'animate' => $this->flag($content['animate'] ?? '1'),
            'duration' => (string) min(5, max(1, (int) ($content['duration'] ?? 2))),
            'use_grouping' => $this->flag($content['use_grouping'] ?? '1'),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeBrandCarousel(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $logos = is_array($content['logos'] ?? null) ? $content['logos'] : [];
        $normalizedLogos = collect($logos)->filter(fn (mixed $logo): bool => is_array($logo))->take(20)->map(function (array $logo): array {
            $text = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($logo[$key] ?? null) ? $logo[$key] : '')), $max, '');

            return [
                'image_id' => $this->mediaId($text('image_id', 26)),
                'name' => $text('name', 100),
                'alt' => $text('alt', 255),
                'url' => $this->safeUrl($text('url', 2048)),
            ];
        })->filter(fn (array $logo): bool => $logo['image_id'] !== '')->values()->all();

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'logos' => $normalizedLogos,
            'autoplay' => $this->flag($content['autoplay'] ?? '1'),
            'interval' => (string) min(15, max(3, (int) ($content['interval'] ?? 4))),
            'show_arrows' => $this->flag($content['show_arrows'] ?? '1'),
            'pause_on_hover' => $this->flag($content['pause_on_hover'] ?? '1'),
            'show_names' => $this->flag($content['show_names'] ?? '0'),
            'grayscale' => $this->flag($content['grayscale'] ?? '0'),
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '0'),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeProducts(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(explode(',', $plain('product_slugs', 7000)))
            ->map(fn (string $slug): string => Str::slug($slug))
            ->filter()->unique()->take(50)->implode(',');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'selection_mode' => ($content['selection_mode'] ?? null) === 'selected' ? 'selected' : 'all',
            'product_slugs' => $slugs,
            'featured_only' => $this->flag($content['featured_only'] ?? '0'),
            'limit' => (string) min(24, max(1, (int) ($content['limit'] ?? 6))),
            'sort' => in_array($content['sort'] ?? null, ['display-order', 'featured-first', 'name', 'price-low', 'price-high'], true) ? $content['sort'] : 'display-order',
            'columns' => in_array((string) ($content['columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['columns'] : '3',
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_descriptions' => $this->flag($content['show_descriptions'] ?? '1'),
            'show_prices' => $this->flag($content['show_prices'] ?? '1'),
            'price_prefix' => $plain('price_prefix', 8),
            'show_featured_badge' => $this->flag($content['show_featured_badge'] ?? '1'),
            'featured_label' => $plain('featured_label', 40),
            'show_button' => $this->flag($content['show_button'] ?? '1'),
            'button_label' => $plain('button_label', 80),
            'button_url' => $this->safeUrl($plain('button_url', 2048)),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeTestimonials(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'testimonial_featured_only' => $this->flag($content['testimonial_featured_only'] ?? '0'),
            'testimonial_limit' => (string) min(20, max(1, (int) ($content['testimonial_limit'] ?? 6))),
            'testimonial_sort' => in_array($content['testimonial_sort'] ?? null, ['display-order', 'featured-first', 'rating-high'], true) ? $content['testimonial_sort'] : 'display-order',
            'show_photos' => $this->flag($content['show_photos'] ?? '1'),
            'show_roles' => $this->flag($content['show_roles'] ?? '1'),
            'show_companies' => $this->flag($content['show_companies'] ?? '1'),
            'show_ratings' => $this->flag($content['show_ratings'] ?? '1'),
            'autoplay' => $this->flag($content['autoplay'] ?? '1'),
            'interval' => (string) min(15, max(3, (int) ($content['interval'] ?? 6))),
            'show_arrows' => $this->flag($content['show_arrows'] ?? '1'),
            'show_dots' => $this->flag($content['show_dots'] ?? '1'),
            'pause_on_hover' => $this->flag($content['pause_on_hover'] ?? '1'),
            'slides_per_view' => in_array((string) ($content['slides_per_view'] ?? ''), ['1', '2', '3'], true) ? (string) $content['slides_per_view'] : '1',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizePortfolio(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(explode(',', $plain('portfolio_slugs', 7000)))
            ->map(fn (string $slug): string => Str::slug($slug))->filter()->unique()->take(50)->implode(',');

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'portfolio_selection_mode' => in_array($content['portfolio_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['portfolio_selection_mode'] : 'all',
            'portfolio_slugs' => $slugs,
            'portfolio_category' => $plain('portfolio_category', 120),
            'portfolio_featured_only' => $this->flag($content['portfolio_featured_only'] ?? '0'),
            'portfolio_limit' => (string) min(24, max(1, (int) ($content['portfolio_limit'] ?? 6))),
            'portfolio_sort' => in_array($content['portfolio_sort'] ?? null, ['display-order', 'featured-first', 'newest', 'oldest', 'title'], true) ? $content['portfolio_sort'] : 'display-order',
            'portfolio_columns' => in_array((string) ($content['portfolio_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['portfolio_columns'] : '3',
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_descriptions' => $this->flag($content['show_descriptions'] ?? '1'),
            'show_categories' => $this->flag($content['show_categories'] ?? '1'),
            'show_clients' => $this->flag($content['show_clients'] ?? '1'),
            'show_dates' => $this->flag($content['show_dates'] ?? '1'),
            'show_locations' => $this->flag($content['show_locations'] ?? '0'),
            'show_button' => $this->flag($content['show_button'] ?? '1'),
            'button_label' => $plain('button_label', 80),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'open_new_tab' => $this->flag($content['open_new_tab'] ?? '0'),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeGallery(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $images = collect(preg_split('/\R/', $plain('gallery_images', 12000)) ?: [])
            ->map(fn (string $image): string => trim($image))->filter()
            ->filter(fn (string $image): bool => preg_match('/^(?:\/|https?:\/\/)/i', $image) === 1)
            ->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'gallery_selection_mode' => in_array($content['gallery_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['gallery_selection_mode'] : 'all',
            'gallery_images' => $images,
            'gallery_category' => $plain('gallery_category', 120),
            'gallery_limit' => (string) min(48, max(1, (int) ($content['gallery_limit'] ?? 12))),
            'gallery_sort' => in_array($content['gallery_sort'] ?? null, ['display-order', 'title', 'category'], true) ? $content['gallery_sort'] : 'display-order',
            'gallery_columns' => in_array((string) ($content['gallery_columns'] ?? ''), ['2', '3', '4', '5'], true) ? (string) $content['gallery_columns'] : '3',
            'show_titles' => $this->flag($content['show_titles'] ?? '1'),
            'show_captions' => $this->flag($content['show_captions'] ?? '1'),
            'show_categories' => $this->flag($content['show_categories'] ?? '0'),
            'respect_aspect_ratios' => $this->flag($content['respect_aspect_ratios'] ?? '1'),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'gallery_gap' => in_array($content['gallery_gap'] ?? null, ['compact', 'normal', 'spacious'], true) ? $content['gallery_gap'] : 'normal',
            'enable_lightbox' => $this->flag($content['enable_lightbox'] ?? '1'),
            'lightbox_captions' => $this->flag($content['lightbox_captions'] ?? '1'),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeTeam(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $keys = collect(preg_split('/\R/', $plain('team_keys', 12000)) ?: [])
            ->map(fn (string $key): string => trim($key))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'team_selection_mode' => in_array($content['team_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['team_selection_mode'] : 'all',
            'team_keys' => $keys,
            'team_role' => $plain('team_role', 255),
            'team_featured_only' => $this->flag($content['team_featured_only'] ?? '0'),
            'team_limit' => (string) min(24, max(1, (int) ($content['team_limit'] ?? 8))),
            'team_sort' => in_array($content['team_sort'] ?? null, ['display-order', 'featured-first', 'name', 'role'], true) ? $content['team_sort'] : 'display-order',
            'team_columns' => in_array((string) ($content['team_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['team_columns'] : '4',
            'show_photos' => $this->flag($content['show_photos'] ?? '1'),
            'show_roles' => $this->flag($content['show_roles'] ?? '1'),
            'show_bios' => $this->flag($content['show_bios'] ?? '1'),
            'show_email' => $this->flag($content['show_email'] ?? '0'),
            'show_phone' => $this->flag($content['show_phone'] ?? '0'),
            'show_linkedin' => $this->flag($content['show_linkedin'] ?? '1'),
            'image_shape' => in_array($content['image_shape'] ?? null, ['portrait', 'square', 'circle'], true) ? $content['image_shape'] : 'portrait',
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
            'card_alignment' => in_array($content['card_alignment'] ?? null, ['left', 'center'], true) ? $content['card_alignment'] : 'left',
            'show_featured_badge' => $this->flag($content['show_featured_badge'] ?? '0'),
            'featured_label' => $plain('featured_label', 40),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeFaq(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $keys = collect(preg_split('/\R/', $plain('faq_keys', 12000)) ?: [])
            ->map(fn (string $key): string => trim($key))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'faq_selection_mode' => in_array($content['faq_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['faq_selection_mode'] : 'all',
            'faq_keys' => $keys,
            'faq_category' => $plain('faq_category', 120),
            'faq_limit' => (string) min(50, max(1, (int) ($content['faq_limit'] ?? 12))),
            'faq_sort' => in_array($content['faq_sort'] ?? null, ['display-order', 'question', 'category'], true) ? $content['faq_sort'] : 'display-order',
            'show_categories' => $this->flag($content['show_categories'] ?? '1'),
            'show_numbers' => $this->flag($content['show_numbers'] ?? '0'),
            'default_open' => in_array($content['default_open'] ?? null, ['none', 'first', 'all'], true) ? $content['default_open'] : 'first',
            'allow_multiple' => $this->flag($content['allow_multiple'] ?? '1'),
            'enable_search' => $this->flag($content['enable_search'] ?? '0'),
            'search_placeholder' => $plain('search_placeholder', 100),
            'show_expand_controls' => $this->flag($content['show_expand_controls'] ?? '0'),
            'show_cta' => $this->flag($content['show_cta'] ?? '0'),
            'cta_text' => $plain('cta_text', 160),
            'cta_label' => $plain('cta_label', 60),
            'cta_url' => $this->safeUrl($plain('cta_url', 2048)),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeBlogPosts(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(preg_split('/\R/', $plain('blog_slugs', 12000)) ?: [])
            ->map(fn (string $slug): string => Str::slug($slug))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'blog_selection_mode' => in_array($content['blog_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['blog_selection_mode'] : 'all',
            'blog_slugs' => $slugs,
            'blog_category' => $plain('blog_category', 120),
            'blog_limit' => (string) min(24, max(1, (int) ($content['blog_limit'] ?? 6))),
            'blog_sort' => in_array($content['blog_sort'] ?? null, ['newest', 'oldest', 'title'], true) ? $content['blog_sort'] : 'newest',
            'blog_columns' => in_array((string) ($content['blog_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['blog_columns'] : '3',
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_excerpts' => $this->flag($content['show_excerpts'] ?? '1'),
            'show_authors' => $this->flag($content['show_authors'] ?? '1'),
            'show_dates' => $this->flag($content['show_dates'] ?? '1'),
            'show_categories' => $this->flag($content['show_categories'] ?? '1'),
            'show_tags' => $this->flag($content['show_tags'] ?? '0'),
            'show_reading_time' => $this->flag($content['show_reading_time'] ?? '1'),
            'read_more_label' => $plain('read_more_label', 60),
            'show_archive_link' => $this->flag($content['show_archive_link'] ?? '1'),
            'archive_label' => $plain('archive_label', 60),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeNews(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(preg_split('/\R/', $plain('news_slugs', 12000)) ?: [])
            ->map(fn (string $slug): string => Str::slug($slug))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'news_selection_mode' => in_array($content['news_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['news_selection_mode'] : 'all',
            'news_slugs' => $slugs,
            'news_category' => $plain('news_category', 120),
            'news_limit' => (string) min(24, max(1, (int) ($content['news_limit'] ?? 6))),
            'news_sort' => in_array($content['news_sort'] ?? null, ['newest', 'oldest', 'title'], true) ? $content['news_sort'] : 'newest',
            'news_columns' => in_array((string) ($content['news_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['news_columns'] : '3',
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_excerpts' => $this->flag($content['show_excerpts'] ?? '1'),
            'show_authors' => $this->flag($content['show_authors'] ?? '1'),
            'show_dates' => $this->flag($content['show_dates'] ?? '1'),
            'show_categories' => $this->flag($content['show_categories'] ?? '1'),
            'show_tags' => $this->flag($content['show_tags'] ?? '0'),
            'show_reading_time' => $this->flag($content['show_reading_time'] ?? '0'),
            'read_more_label' => $plain('read_more_label', 60),
            'show_archive_link' => $this->flag($content['show_archive_link'] ?? '1'),
            'archive_label' => $plain('archive_label', 60),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeEvents(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(preg_split('/\R/', $plain('event_slugs', 12000)) ?: [])
            ->map(fn (string $slug): string => Str::slug($slug))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'event_selection_mode' => in_array($content['event_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['event_selection_mode'] : 'all',
            'event_slugs' => $slugs,
            'event_category' => $plain('event_category', 120),
            'event_timeframe' => in_array($content['event_timeframe'] ?? null, ['upcoming', 'past', 'all'], true) ? $content['event_timeframe'] : 'upcoming',
            'event_limit' => (string) min(24, max(1, (int) ($content['event_limit'] ?? 6))),
            'event_sort' => in_array($content['event_sort'] ?? null, ['soonest', 'latest', 'title'], true) ? $content['event_sort'] : 'soonest',
            'event_columns' => in_array((string) ($content['event_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['event_columns'] : '3',
            'show_images' => $this->flag($content['show_images'] ?? '1'),
            'show_excerpts' => $this->flag($content['show_excerpts'] ?? '1'),
            'show_dates' => $this->flag($content['show_dates'] ?? '1'),
            'show_times' => $this->flag($content['show_times'] ?? '1'),
            'show_venues' => $this->flag($content['show_venues'] ?? '1'),
            'show_addresses' => $this->flag($content['show_addresses'] ?? '0'),
            'show_categories' => $this->flag($content['show_categories'] ?? '1'),
            'show_registration' => $this->flag($content['show_registration'] ?? '1'),
            'registration_label' => $plain('registration_label', 60),
            'show_archive_link' => $this->flag($content['show_archive_link'] ?? '1'),
            'archive_label' => $plain('archive_label', 60),
            'image_fit' => in_array($content['image_fit'] ?? null, ['cover', 'contain'], true) ? $content['image_fit'] : 'cover',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private function normalizeCareers(array $content): array
    {
        $plain = fn (string $key, int $max): string => Str::limit(trim(strip_tags(is_string($content[$key] ?? null) ? $content[$key] : '')), $max, '');
        $slugs = collect(preg_split('/\R/', $plain('career_slugs', 12000)) ?: [])
            ->map(fn (string $slug): string => Str::slug($slug))->filter()->unique()->take(50)->implode("\n");

        return [
            'eyebrow' => $plain('eyebrow', 80),
            'heading' => $plain('heading', 160),
            'description' => $plain('description', 1000),
            'career_selection_mode' => in_array($content['career_selection_mode'] ?? null, ['all', 'selected'], true) ? $content['career_selection_mode'] : 'all',
            'career_slugs' => $slugs,
            'career_department' => $plain('career_department', 255),
            'career_location' => $plain('career_location', 255),
            'career_employment_type' => in_array($content['career_employment_type'] ?? null, ['', 'full-time', 'part-time', 'contract', 'internship', 'temporary'], true) ? (string) $content['career_employment_type'] : '',
            'career_workplace_type' => in_array($content['career_workplace_type'] ?? null, ['', 'on-site', 'hybrid', 'remote'], true) ? (string) $content['career_workplace_type'] : '',
            'career_limit' => (string) min(50, max(1, (int) ($content['career_limit'] ?? 8))),
            'career_sort' => in_array($content['career_sort'] ?? null, ['display-order', 'title', 'closing-soonest', 'location'], true) ? $content['career_sort'] : 'display-order',
            'career_columns' => in_array((string) ($content['career_columns'] ?? ''), ['1', '2', '3'], true) ? (string) $content['career_columns'] : '2',
            'show_summaries' => $this->flag($content['show_summaries'] ?? '1'),
            'show_departments' => $this->flag($content['show_departments'] ?? '1'),
            'show_locations' => $this->flag($content['show_locations'] ?? '1'),
            'show_employment_types' => $this->flag($content['show_employment_types'] ?? '1'),
            'show_workplace_types' => $this->flag($content['show_workplace_types'] ?? '1'),
            'show_salaries' => $this->flag($content['show_salaries'] ?? '1'),
            'show_closing_dates' => $this->flag($content['show_closing_dates'] ?? '1'),
            'show_apply_button' => $this->flag($content['show_apply_button'] ?? '1'),
            'apply_label' => $plain('apply_label', 60),
            'show_archive_link' => $this->flag($content['show_archive_link'] ?? '1'),
            'archive_label' => $plain('archive_label', 60),
        ];
    }
}
