<?php

namespace App\Tenancy;

use App\Models\WebsiteDomain;

class SubdomainService
{
    public function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    public function isValidFormat(string $value): bool
    {
        return preg_match('/^(?!.*--)[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/', $value) === 1;
    }

    public function isReserved(string $value): bool
    {
        return in_array($value, config('tenancy.reserved_subdomains', []), true);
    }

    public function isAvailable(string $value): bool
    {
        $subdomain = $this->normalize($value);

        return $this->isValidFormat($subdomain)
            && ! $this->isReserved($subdomain)
            && ! WebsiteDomain::query()->withoutGlobalScopes()->where('subdomain', $subdomain)->exists();
    }

    public function hostname(string $subdomain): string
    {
        return $this->normalize($subdomain).'.'.config('domains.root');
    }
}
