<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\Website;
use LogicException;

class CurrentTenant
{
    private ?Tenant $tenant = null;

    private ?Website $website = null;

    public function set(Tenant $tenant, ?Website $website = null): void
    {
        $this->tenant = $tenant;
        $this->website = $website;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->website = null;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): Tenant
    {
        return $this->tenant ?? throw new LogicException('No tenant has been resolved for this request.');
    }

    public function website(): ?Website
    {
        return $this->website;
    }
}
