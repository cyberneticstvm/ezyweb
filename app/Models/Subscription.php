<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $tenant_id
 * @property int $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon $starts_at
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_starts_at
 * @property Carbon|null $current_period_ends_at
 * @property Carbon|null $grace_ends_at
 * @property Plan|null $plan
 * @property Tenant|null $tenant
 */
#[Fillable(['public_id', 'tenant_id', 'plan_id', 'status', 'starts_at', 'trial_ends_at', 'current_period_starts_at', 'current_period_ends_at', 'grace_ends_at', 'cancelled_at', 'suspended_at', 'ended_at', 'auto_renew', 'provider', 'provider_reference', 'internal_notes', 'created_by'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    /** @return HasMany<SubscriptionPayment, $this> */
    public function payments(): HasMany { return $this->hasMany(SubscriptionPayment::class)->latest(); }
    /** @return HasMany<SubscriptionReminder, $this> */
    public function reminders(): HasMany { return $this->hasMany(SubscriptionReminder::class); }

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class, 'starts_at' => 'datetime', 'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime', 'current_period_ends_at' => 'datetime', 'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime', 'suspended_at' => 'datetime', 'ended_at' => 'datetime', 'auto_renew' => 'boolean',
        ];
    }
}
