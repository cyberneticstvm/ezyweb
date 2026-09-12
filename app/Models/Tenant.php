<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'business_category', 'country_code', 'timezone', 'default_locale', 'contact_email', 'contact_phone', 'status'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    /** @return HasMany<TenantMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /** @return HasMany<Website, $this> */
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /** @return HasMany<WebsiteDomain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(WebsiteDomain::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    /** @return HasOne<Subscription, $this> */
    public function currentSubscription(): HasOne { return $this->hasOne(Subscription::class)->latestOfMany(); }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => TenantStatus::class];
    }
}
