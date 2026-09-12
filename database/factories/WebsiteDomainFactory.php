<?php

namespace Database\Factories;

use App\Enums\DomainType;
use App\Models\Website;
use App\Models\WebsiteDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebsiteDomain> */
class WebsiteDomainFactory extends Factory
{
    protected $model = WebsiteDomain::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subdomain = fake()->unique()->slug(2);

        return [
            'hostname' => $subdomain.'.ezyweb.in',
            'subdomain' => $subdomain,
            'type' => DomainType::Platform,
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }

    public function forWebsite(Website $website): static
    {
        return $this->state(fn () => [
            'tenant_id' => $website->tenant_id,
            'website_id' => $website->getKey(),
        ]);
    }
}
