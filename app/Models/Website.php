<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\WebsiteStatus;
use App\Enums\WebsiteType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WebsiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'name', 'slug', 'type', 'status', 'publication_status', 'template_key', 'theme_settings', 'initial_sections', 'draft_revision', 'draft_updated_at', 'published_revision', 'published_version_id', 'published_by', 'published_at', 'last_publish_error', 'onboarding_completed_at'])]
class Website extends Model
{
    /** @use HasFactory<WebsiteFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /** @return HasMany<WebsiteDomain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(WebsiteDomain::class);
    }

    /** @return HasMany<WebsiteTemplateSnapshot, $this> */
    public function templateSnapshots(): HasMany
    {
        return $this->hasMany(WebsiteTemplateSnapshot::class);
    }

    /** @return HasMany<Page, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<PageSection, $this> */
    public function pageSections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('display_order');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('display_order');
    }

    /** @return HasMany<ContentPost, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(ContentPost::class)->orderByDesc('published_at');
    }

    /** @return HasMany<PortfolioProject, $this> */
    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class)->orderBy('display_order');
    }

    /** @return HasMany<JobOpening, $this> */
    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class)->orderBy('display_order');
    }

    /** @return HasOne<WebsiteContactSetting, $this> */
    public function contactSetting(): HasOne
    {
        return $this->hasOne(WebsiteContactSetting::class);
    }

    /** @return HasOne<WebsiteWhatsappSetting, $this> */
    public function whatsappSetting(): HasOne
    {
        return $this->hasOne(WebsiteWhatsappSetting::class);
    }

    /** @return HasMany<ContactMessage, $this> */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class)->orderByDesc('submitted_at');
    }

    /** @return HasMany<NewsletterSubscriber, $this> */
    public function newsletterSubscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class)->orderByDesc('subscribed_at');
    }

    /** @return HasMany<MediaAsset, $this> */
    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    /** @return HasOne<WebsiteSeoSetting, $this> */
    public function seoSetting(): HasOne
    {
        return $this->hasOne(WebsiteSeoSetting::class);
    }

    /** @return HasMany<Redirect, $this> */
    public function redirects(): HasMany
    {
        return $this->hasMany(Redirect::class);
    }

    /** @return HasMany<WebsiteVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(WebsiteVersion::class)->orderByDesc('version_number');
    }

    /** @return BelongsTo<WebsiteVersion, $this> */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(WebsiteVersion::class, 'published_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => WebsiteType::class,
            'status' => WebsiteStatus::class,
            'publication_status' => PublicationStatus::class,
            'theme_settings' => 'array',
            'initial_sections' => 'array',
            'draft_updated_at' => 'datetime',
            'published_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }
}
