<?php

namespace App\Actions;

use App\Builder\SectionRegistry;
use App\Data\OnboardingData;
use App\Enums\DomainType;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\WebsiteStatus;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Tenancy\SubdomainService;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTenantFromOnboarding
{
    public function __construct(private readonly SubdomainService $subdomains, private readonly SectionRegistry $sections, private readonly SubscriptionManager $subscriptions) {}

    public function execute(User $user, OnboardingData $data): Website
    {
        $website = DB::transaction(function () use ($user, $data): Website {
            $subdomainExists = WebsiteDomain::query()
                ->withoutGlobalScopes()
                ->where('subdomain', $data->subdomain)
                ->lockForUpdate()
                ->exists();

            if ($subdomainExists) {
                throw ValidationException::withMessages(['subdomain' => 'This website address was just claimed. Please choose another.']);
            }

            $tenant = Tenant::query()->create([
                'name' => $data->businessName,
                'slug' => Str::slug($data->businessName).'-'.Str::lower(Str::random(6)),
                'business_category' => $data->businessCategory,
                'country_code' => $data->countryCode,
                'timezone' => $data->timezone,
                'default_locale' => $data->defaultLanguage,
                'contact_email' => $data->contactEmail,
                'contact_phone' => $data->contactPhone,
                'status' => TenantStatus::Active,
            ]);

            TenantMembership::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->getKey(),
                'role' => MembershipRole::Owner,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            $website = Website::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $data->businessName,
                'slug' => 'main',
                'type' => $data->websiteType,
                'status' => WebsiteStatus::Draft,
                'template_key' => $data->templateKey,
                'theme_settings' => $data->theme,
                'initial_sections' => $data->sections,
                'onboarding_completed_at' => now(),
            ]);

            $home = Page::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'website_id' => $website->getKey(),
                'title' => 'Home',
                'slug' => 'home',
                'navigation_label' => 'Home',
                'is_home' => true,
                'is_enabled' => true,
                'sort_order' => 0,
            ]);

            foreach ($data->sections as $order => $type) {
                $definition = $this->sections->get($type);
                PageSection::query()->withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->getKey(),
                    'website_id' => $website->getKey(),
                    'page_id' => $home->getKey(),
                    'type' => $type,
                    'variant' => $definition['variants'][0]['key'],
                    'anchor' => Str::slug($type),
                    'content' => $definition['default_content'],
                    'design_settings' => $definition['default_design'],
                    'is_enabled' => true,
                    'show_on_desktop' => true,
                    'show_on_tablet' => true,
                    'show_on_mobile' => true,
                    'sort_order' => $order,
                ]);
            }

            WebsiteDomain::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->getKey(),
                'website_id' => $website->getKey(),
                'hostname' => $this->subdomains->hostname($data->subdomain),
                'subdomain' => $data->subdomain,
                'type' => DomainType::Platform,
                'is_primary' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]);

            $this->subscriptions->startTrial($tenant, $user);

            return $website;
        }, attempts: 3);

        Cache::forget('tenant-domain-id:'.$this->subdomains->hostname($data->subdomain));

        return $website;
    }
}
