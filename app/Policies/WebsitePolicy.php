<?php

namespace App\Policies;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Models\Website;

class WebsitePolicy
{
    public function view(User $user, Website $website): bool
    {
        return $this->hasActiveMembership($user, $website);
    }

    public function update(User $user, Website $website): bool
    {
        return $this->hasActiveMembership($user, $website);
    }

    private function hasActiveMembership(User $user, Website $website): bool
    {
        return $user->tenantMemberships()
            ->withoutGlobalScopes()
            ->where('tenant_id', $website->tenant_id)
            ->where('status', MembershipStatus::Active->value)
            ->exists();
    }
}
