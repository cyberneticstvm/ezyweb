<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Builder\SectionRegistry;
use App\Models\ContentPost;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\JobOpening;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\TeamMember;
use App\Models\Template;
use App\Models\Testimonial;
use App\Models\User;
use App\Publishing\PreviewUrlFactory;
use App\Tenancy\TenantScope;
use App\Themes\ThemeTokenService;
use Inertia\Inertia;
use Inertia\Response;

class BuilderController extends Controller
{
    public function __invoke(BuilderWorkspace $workspace, SectionRegistry $registry, ThemeTokenService $tokens, PreviewUrlFactory $previews): Response
    {
        $website = $workspace->website();
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $website);
        $template = Template::query()->where('key', $website->template_key)->where('is_active', true)->firstOrFail();
        $theme = $tokens->forWebsite($website, $template);
        $pages = $website->pages()->with('sections')->get();
        $mediaAssets = MediaAsset::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->latest()->limit(100)->get()
            ->map(fn (MediaAsset $asset): array => [
                'public_id' => $asset->public_id,
                'title' => $asset->title ?: $asset->original_name,
                'alt_text' => $asset->alt_text,
                'thumbnail_url' => $asset->variants['thumbnail']['url'] ?? route('media.show', ['media' => $asset->public_id, 'variant' => 'thumbnail'], absolute: false),
            ])->values();
        $productOptions = Product::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('name')->get()
            ->map(fn (Product $product): array => [
                'slug' => $product->getAttribute('slug'),
                'name' => $product->getAttribute('name'),
                'is_active' => (bool) $product->getAttribute('is_active'),
                'is_featured' => (bool) $product->getAttribute('is_featured'),
                'short_description' => $product->getAttribute('short_description'),
                'featured_image' => $product->getAttribute('featured_image'),
                'price' => $product->getAttribute('price'),
                'show_price' => (bool) $product->getAttribute('show_price'),
                'enquiry_label' => $product->getAttribute('enquiry_label'),
            ])->values();
        $testimonialOptions = Testimonial::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (Testimonial $testimonial): array => [
                'client_name' => $testimonial->getAttribute('client_name'),
                'client_role' => $testimonial->getAttribute('client_role'),
                'company' => $testimonial->getAttribute('company'),
                'photo' => $testimonial->getAttribute('photo'),
                'quote' => $testimonial->getAttribute('quote'),
                'rating' => $testimonial->getAttribute('rating'),
                'is_active' => (bool) $testimonial->getAttribute('is_active'),
                'is_featured' => (bool) $testimonial->getAttribute('is_featured'),
            ])->values();
        $portfolioOptions = PortfolioProject::query()->withoutGlobalScope(TenantScope::class)
            ->with('category')->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('title')->get()
            ->map(fn (PortfolioProject $project): array => [
                'slug' => $project->getAttribute('slug'),
                'title' => $project->getAttribute('title'),
                'category' => $project->category?->getAttribute('name'),
                'client' => $project->getAttribute('client'),
                'project_date' => $project->getAttribute('project_date')?->toDateString(),
                'location' => $project->getAttribute('location'),
                'short_description' => $project->getAttribute('short_description'),
                'featured_image' => $project->getAttribute('featured_image'),
                'project_url' => $project->getAttribute('project_url'),
                'is_active' => (bool) $project->getAttribute('is_active'),
                'is_featured' => (bool) $project->getAttribute('is_featured'),
            ])->values();
        $galleryOptions = GalleryItem::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (GalleryItem $item): array => [
                'title' => $item->getAttribute('title'),
                'image' => $item->getAttribute('image'),
                'alt_text' => $item->getAttribute('alt_text'),
                'caption' => $item->getAttribute('caption'),
                'category' => $item->getAttribute('category'),
                'aspect_ratio' => $item->getAttribute('aspect_ratio'),
                'is_active' => (bool) $item->getAttribute('is_active'),
            ])->values();
        $teamOptions = TeamMember::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (TeamMember $member): array => [
                'key' => $member->getAttribute('email') ?: $member->getAttribute('name'),
                'name' => $member->getAttribute('name'),
                'role' => $member->getAttribute('role'),
                'photo' => $member->getAttribute('photo'),
                'bio' => $member->getAttribute('bio'),
                'email' => $member->getAttribute('email'),
                'phone' => $member->getAttribute('phone'),
                'linkedin_url' => $member->getAttribute('linkedin_url'),
                'is_active' => (bool) $member->getAttribute('is_active'),
                'is_featured' => (bool) $member->getAttribute('is_featured'),
            ])->values();
        $faqOptions = Faq::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (Faq $faq): array => [
                'key' => $faq->getAttribute('question'),
                'question' => $faq->getAttribute('question'),
                'answer' => $faq->getAttribute('answer'),
                'category' => $faq->getAttribute('category'),
                'is_active' => (bool) $faq->getAttribute('is_active'),
            ])->values();
        $blogOptions = ContentPost::query()->withoutGlobalScope(TenantScope::class)
            ->with(['category', 'tags'])->where('website_id', $website->getKey())->where('content_type', 'blog')
            ->orderByDesc('published_at')->orderByDesc('id')->get()
            ->map(fn (ContentPost $post): array => [
                'slug' => $post->getAttribute('slug'),
                'title' => $post->getAttribute('title'),
                'excerpt' => $post->getAttribute('excerpt'),
                'featured_image' => $post->getAttribute('featured_image'),
                'author_name' => $post->getAttribute('author_name'),
                'category' => $post->category?->getAttribute('name'),
                'tags' => $post->tags->pluck('name')->values(),
                'status' => $post->getAttribute('status')?->value,
                'published_at' => $post->getAttribute('published_at')?->toIso8601String(),
                'is_published' => in_array($post->getAttribute('status')?->value, ['published', 'scheduled'], true) && $post->getAttribute('published_at')?->isPast(),
                'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags((string) $post->getAttribute('body'))) / 200)),
            ])->values();
        $newsOptions = ContentPost::query()->withoutGlobalScope(TenantScope::class)
            ->with(['category', 'tags'])->where('website_id', $website->getKey())->where('content_type', 'news')
            ->orderByDesc('published_at')->orderByDesc('id')->get()
            ->map(fn (ContentPost $post): array => [
                'slug' => $post->getAttribute('slug'),
                'title' => $post->getAttribute('title'),
                'excerpt' => $post->getAttribute('excerpt'),
                'featured_image' => $post->getAttribute('featured_image'),
                'author_name' => $post->getAttribute('author_name'),
                'category' => $post->category?->getAttribute('name'),
                'tags' => $post->tags->pluck('name')->values(),
                'status' => $post->getAttribute('status')?->value,
                'published_at' => $post->getAttribute('published_at')?->toIso8601String(),
                'is_published' => in_array($post->getAttribute('status')?->value, ['published', 'scheduled'], true) && $post->getAttribute('published_at')?->isPast(),
                'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags((string) $post->getAttribute('body'))) / 200)),
            ])->values();
        $eventOptions = ContentPost::query()->withoutGlobalScope(TenantScope::class)
            ->with('category')->where('website_id', $website->getKey())->where('content_type', 'event')
            ->orderBy('event_start_date')->orderBy('event_start_time')->get()
            ->map(fn (ContentPost $event): array => [
                'slug' => $event->getAttribute('slug'),
                'title' => $event->getAttribute('title'),
                'excerpt' => $event->getAttribute('excerpt'),
                'featured_image' => $event->getAttribute('featured_image'),
                'category' => $event->category?->getAttribute('name'),
                'status' => $event->getAttribute('status')?->value,
                'published_at' => $event->getAttribute('published_at')?->toIso8601String(),
                'is_published' => in_array($event->getAttribute('status')?->value, ['published', 'scheduled'], true) && $event->getAttribute('published_at')?->isPast(),
                'event_start_date' => $event->getAttribute('event_start_date')?->toDateString(),
                'event_start_time' => $event->getAttribute('event_start_time'),
                'event_end_date' => $event->getAttribute('event_end_date')?->toDateString(),
                'event_end_time' => $event->getAttribute('event_end_time'),
                'venue' => $event->getAttribute('venue'),
                'address' => $event->getAttribute('address'),
                'registration_url' => $event->getAttribute('registration_url'),
                'online_event_url' => $event->getAttribute('online_event_url'),
                'is_upcoming' => $event->getAttribute('event_end_date')?->copy()->endOfDay()->isFuture() ?? $event->getAttribute('event_start_date')?->copy()->endOfDay()->isFuture() ?? false,
            ])->values();
        $careerOptions = JobOpening::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderBy('display_order')->orderBy('title')->get()
            ->map(fn (JobOpening $job): array => [
                'slug' => $job->getAttribute('slug'),
                'title' => $job->getAttribute('title'),
                'department' => $job->getAttribute('department'),
                'location' => $job->getAttribute('location'),
                'employment_type' => $job->getAttribute('employment_type'),
                'workplace_type' => $job->getAttribute('workplace_type'),
                'summary' => $job->getAttribute('summary'),
                'salary_display' => $job->getAttribute('salary_display'),
                'closing_date' => $job->getAttribute('closing_date')?->toDateString(),
                'application_email' => $job->getAttribute('application_email'),
                'is_active' => (bool) $job->getAttribute('is_active'),
                'is_open' => (bool) $job->getAttribute('is_active') && ($job->getAttribute('closing_date') === null || $job->getAttribute('closing_date')->copy()->startOfDay()->gte(now()->startOfDay())),
            ])->values();

        return Inertia::render('builder/index', [
            'builderWebsite' => [
                ...$website->only('id', 'name', 'type', 'template_key', 'draft_revision'),
                'draft_updated_at' => $website->draft_updated_at,
            ],
            'pages' => $pages,
            'registry' => $registry->all(),
            'theme' => $theme,
            'cssVariables' => $tokens->cssVariables($theme),
            'previewUrl' => $previews->draft($website),
            'mediaAssets' => $mediaAssets,
            'productOptions' => $productOptions,
            'testimonialOptions' => $testimonialOptions,
            'portfolioOptions' => $portfolioOptions,
            'galleryOptions' => $galleryOptions,
            'teamOptions' => $teamOptions,
            'faqOptions' => $faqOptions,
            'blogOptions' => $blogOptions,
            'newsOptions' => $newsOptions,
            'eventOptions' => $eventOptions,
            'careerOptions' => $careerOptions,
        ]);
    }
}
