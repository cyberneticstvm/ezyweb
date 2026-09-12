<?php

namespace App\Rules;

use App\Tenancy\SubdomainService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableSubdomain implements ValidationRule
{
    public function __construct(private readonly SubdomainService $subdomains) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The website address must be text.');

            return;
        }

        $subdomain = $this->subdomains->normalize($value);

        if (! $this->subdomains->isValidFormat($subdomain)) {
            $fail('Use 3–40 lowercase letters, numbers, or single hyphens, beginning and ending with a letter or number.');

            return;
        }

        if ($this->subdomains->isReserved($subdomain)) {
            $fail('This website address is reserved by Ezyweb.');

            return;
        }

        if (! $this->subdomains->isAvailable($subdomain)) {
            $fail('This website address is already in use.');
        }
    }
}
