<?php

namespace App\Policies;

use App\Enums\MembershipStatus;
use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $this->hasActiveMembership($user, $tenant);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->hasActiveMembership($user, $tenant);
    }

    private function hasActiveMembership(User $user, Tenant $tenant): bool
    {
        return $user->tenantMemberships()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('status', MembershipStatus::Active->value)
            ->exists();
    }
}
