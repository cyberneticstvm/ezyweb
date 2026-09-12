<?php

namespace App\Data;

class OnboardingData
{
    /**
     * @param  array<string, string>  $theme
     * @param  array<int, string>  $sections
     */
    public function __construct(
        public readonly string $businessName,
        public readonly string $businessCategory,
        public readonly string $countryCode,
        public readonly string $timezone,
        public readonly string $defaultLanguage,
        public readonly string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly string $subdomain,
        public readonly string $websiteType,
        public readonly string $templateKey,
        public readonly array $theme,
        public readonly array $sections,
    ) {}
}
