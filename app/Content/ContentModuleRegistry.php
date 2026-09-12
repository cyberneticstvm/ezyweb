<?php

namespace App\Content;

use App\Models\Brand;
use App\Models\ContentPost;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\JobOpening;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ContentModuleRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $commonSeo = [
            $this->field('seo_title', 'SEO title', 'text', false, 255),
            $this->field('seo_description', 'SEO description', 'textarea', false, 500),
        ];
        $visibility = [
            $this->field('is_featured', 'Featured', 'boolean'),
            $this->field('is_active', 'Active', 'boolean'),
            $this->field('display_order', 'Display order', 'number'),
        ];
        $postFields = [
            $this->field('title', 'Title', 'text', true, 255),
            $this->field('slug', 'Slug', 'slug', false, 140),
            $this->field('excerpt', 'Excerpt', 'textarea', false, 1000),
            $this->field('body', 'Body', 'richtext', false, 50000),
            $this->field('featured_image', 'Featured image reference', 'url', false, 2048),
            $this->field('author_name', 'Author', 'text', false, 255),
            $this->field('category', 'Category', 'text', false, 120),
            $this->field('tags', 'Tags (comma separated)', 'text', false, 500),
            $this->field('status', 'Status', 'select', true, 20, ['draft', 'published', 'scheduled']),
            $this->field('published_at', 'Publish date and time', 'datetime'),
            ...$commonSeo,
            $this->field('open_graph_image', 'Open Graph image reference', 'url', false, 2048),
        ];

        return [
            'services' => $this->module(Service::class, 'Services', 'service', 'title', 'short_description', [
                $this->field('title', 'Title', 'text', true, 255), $this->field('slug', 'Slug', 'slug', false, 140),
                $this->field('short_description', 'Short description', 'textarea', false, 1000), $this->field('full_description', 'Full description', 'richtext', false, 50000),
                $this->field('icon', 'Icon name', 'text', false, 120), $this->field('featured_image', 'Featured image reference', 'url', false, 2048),
                $this->field('cta_label', 'CTA label', 'text', false, 80), $this->field('cta_url', 'CTA URL', 'url', false, 2048), ...$visibility, ...$commonSeo,
            ]),
            'products' => $this->module(Product::class, 'Products', 'product', 'name', 'short_description', [
                $this->field('name', 'Name', 'text', true, 255), $this->field('slug', 'Slug', 'slug', false, 140),
                $this->field('short_description', 'Short description', 'textarea', false, 1000), $this->field('full_description', 'Full description', 'richtext', false, 50000),
                $this->field('featured_image', 'Featured image reference', 'url', false, 2048), $this->field('gallery', 'Gallery references (one per line)', 'lines', false, 10000),
                $this->field('price', 'Price', 'decimal'), $this->field('show_price', 'Display price', 'boolean'), $this->field('enquiry_label', 'Enquiry button label', 'text', false, 80),
                ...$visibility, ...$commonSeo,
            ]),
            'brands' => $this->module(Brand::class, 'Brands', 'brand', 'name', 'alt_text', [
                $this->field('name', 'Name', 'text', true, 255), $this->field('logo', 'Logo reference', 'url', false, 2048),
                $this->field('website_url', 'Website URL', 'url', false, 2048), $this->field('alt_text', 'Accessible logo text', 'text', true, 255),
                $this->field('is_active', 'Active', 'boolean'), $this->field('display_order', 'Display order', 'number'),
            ]),
            'testimonials' => $this->module(Testimonial::class, 'Testimonials', 'testimonial', 'client_name', 'quote', [
                $this->field('client_name', 'Client name', 'text', true, 255), $this->field('client_role', 'Role', 'text', false, 255),
                $this->field('company', 'Company', 'text', false, 255), $this->field('photo', 'Photo reference', 'url', false, 2048),
                $this->field('quote', 'Testimonial', 'textarea', true, 3000), $this->field('rating', 'Rating (1–5)', 'number'), ...$visibility,
            ]),
            'portfolio' => $this->module(PortfolioProject::class, 'Portfolio', 'project', 'title', 'short_description', [
                $this->field('title', 'Title', 'text', true, 255), $this->field('slug', 'Slug', 'slug', false, 140), $this->field('category', 'Category', 'text', false, 120),
                $this->field('client', 'Client', 'text', false, 255), $this->field('project_date', 'Project date', 'date'), $this->field('location', 'Location', 'text', false, 255),
                $this->field('short_description', 'Short description', 'textarea', false, 1000), $this->field('case_study', 'Full case study', 'richtext', false, 50000),
                $this->field('featured_image', 'Featured image reference', 'url', false, 2048), $this->field('gallery', 'Gallery references (one per line)', 'lines', false, 10000),
                $this->field('project_url', 'Project URL', 'url', false, 2048), ...$visibility, ...$commonSeo,
            ]),
            'gallery' => $this->module(GalleryItem::class, 'Gallery', 'gallery item', 'title', 'caption', [
                $this->field('title', 'Title', 'text', true, 255), $this->field('image', 'Image reference', 'url', true, 2048), $this->field('alt_text', 'Alt text', 'text', true, 255),
                $this->field('caption', 'Caption', 'textarea', false, 1000), $this->field('category', 'Category', 'text', false, 120),
                $this->field('aspect_ratio', 'Aspect ratio', 'select', true, 20, ['auto', 'square', 'portrait', 'landscape']),
                $this->field('is_active', 'Active', 'boolean'), $this->field('display_order', 'Display order', 'number'),
            ]),
            'team' => $this->module(TeamMember::class, 'Team', 'team member', 'name', 'role', [
                $this->field('name', 'Name', 'text', true, 255), $this->field('role', 'Role', 'text', false, 255), $this->field('photo', 'Photo reference', 'url', false, 2048),
                $this->field('bio', 'Biography', 'textarea', false, 3000), $this->field('email', 'Email', 'email', false, 255), $this->field('phone', 'Phone', 'text', false, 40),
                $this->field('linkedin_url', 'LinkedIn URL', 'url', false, 2048), ...$visibility,
            ]),
            'faq' => $this->module(Faq::class, 'FAQ', 'FAQ', 'question', 'answer', [
                $this->field('question', 'Question', 'text', true, 255), $this->field('answer', 'Answer', 'textarea', true, 5000),
                $this->field('category', 'Category', 'text', false, 120), $this->field('is_active', 'Active', 'boolean'), $this->field('display_order', 'Display order', 'number'),
            ]),
            'blog' => $this->module(ContentPost::class, 'Blog', 'blog post', 'title', 'excerpt', $postFields, 'blog'),
            'news' => $this->module(ContentPost::class, 'News', 'news article', 'title', 'excerpt', $postFields, 'news'),
            'events' => $this->module(ContentPost::class, 'Events', 'event', 'title', 'excerpt', [
                ...$postFields, $this->field('event_start_date', 'Start date', 'date', true), $this->field('event_start_time', 'Start time', 'time'),
                $this->field('event_end_date', 'End date', 'date'), $this->field('event_end_time', 'End time', 'time'), $this->field('venue', 'Venue', 'text', false, 255),
                $this->field('address', 'Address', 'textarea', false, 1000), $this->field('map_url', 'Map URL', 'url', false, 2048),
                $this->field('registration_url', 'Registration URL', 'url', false, 2048), $this->field('online_event_url', 'Online event URL', 'url', false, 2048),
            ], 'event'),
            'careers' => $this->module(JobOpening::class, 'Careers', 'job opening', 'title', 'summary', [
                $this->field('title', 'Job title', 'text', true, 255), $this->field('slug', 'Slug', 'slug', false, 140),
                $this->field('department', 'Department', 'text', false, 255), $this->field('location', 'Location', 'text', false, 255),
                $this->field('employment_type', 'Employment type', 'select', false, 30, ['full-time', 'part-time', 'contract', 'internship', 'temporary']),
                $this->field('workplace_type', 'Workplace type', 'select', false, 30, ['on-site', 'hybrid', 'remote']),
                $this->field('summary', 'Summary', 'textarea', false, 1000), $this->field('description', 'Description', 'richtext', false, 50000),
                $this->field('responsibilities', 'Responsibilities', 'richtext', false, 30000), $this->field('requirements', 'Requirements', 'richtext', false, 30000),
                $this->field('salary_display', 'Salary display', 'text', false, 255), $this->field('closing_date', 'Closing date', 'date'),
                $this->field('application_email', 'Application email', 'email', false, 255), $this->field('is_active', 'Active', 'boolean'),
                $this->field('display_order', 'Display order', 'number'), ...$commonSeo,
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function get(string $module): array
    {
        abort_unless(array_key_exists($module, $this->all()), 404);

        return $this->all()[$module];
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /** @return array<string, mixed> */
    public function rules(string $module, int $websiteId, ?int $entryId = null): array
    {
        $definition = $this->get($module);
        $rules = [];
        foreach ($definition['fields'] as $field) {
            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $type = $field['type'];
            if ($type === 'boolean') {
                $fieldRules[] = 'boolean';
            } elseif (in_array($type, ['number', 'decimal'], true)) {
                $fieldRules[] = 'numeric';
                $fieldRules[] = 'min:0';
            } elseif ($type === 'email') {
                $fieldRules[] = 'email:rfc';
            } elseif ($type === 'url') {
                $fieldRules[] = 'url:http,https';
            } elseif ($type === 'date') {
                $fieldRules[] = 'date';
            } elseif ($type === 'time') {
                $fieldRules[] = 'date_format:H:i';
            } elseif ($type === 'datetime') {
                $fieldRules[] = 'date';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:'.($field['max'] ?? 5000);
            }
            if ($type === 'select') {
                $fieldRules[] = Rule::in($field['options']);
            }
            if ($field['key'] === 'rating') {
                $fieldRules[] = 'between:1,5';
            }
            $rules[$field['key']] = $fieldRules;
        }

        if (array_key_exists('slug', $rules)) {
            $model = $definition['model'];
            $table = (new $model)->getTable();
            /** @var Unique $unique */
            $unique = Rule::unique($table, 'slug')->where('website_id', $websiteId);
            if ($definition['content_type']) {
                $unique->where('content_type', $definition['content_type']);
            }
            if ($entryId) {
                $unique->ignore($entryId);
            }
            $rules['slug'][] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
            $rules['slug'][] = $unique;
        }

        if ($module === 'events') {
            $rules['event_end_date'][] = 'after_or_equal:event_start_date';
        }

        return $rules;
    }

    /** @return class-string<Model> */
    public function model(string $module): string
    {
        return $this->get($module)['model'];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  class-string<Model>  $model
     * @return array<string, mixed>
     */
    private function module(string $model, string $label, string $singular, string $titleKey, string $descriptionKey, array $fields, ?string $contentType = null): array
    {
        return compact('model', 'label', 'singular', 'titleKey', 'descriptionKey', 'fields', 'contentType') + ['content_type' => $contentType];
    }

    /**
     * @param  list<string>  $options
     * @return array<string, mixed>
     */
    private function field(string $key, string $label, string $type, bool $required = false, int $max = 255, array $options = []): array
    {
        return compact('key', 'label', 'type', 'required', 'max', 'options');
    }
}
