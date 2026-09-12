<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case GracePeriod = 'grace_period';
    case PastDue = 'past_due';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function allowsPublicAccess(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::GracePeriod], true);
    }
}
