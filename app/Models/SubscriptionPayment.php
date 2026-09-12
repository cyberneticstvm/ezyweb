<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['public_id', 'tenant_id', 'subscription_id', 'amount', 'currency', 'status', 'provider', 'transaction_reference', 'paid_at', 'metadata', 'recorded_by'])]
class SubscriptionPayment extends Model
{
    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    protected function casts(): array { return ['amount' => 'decimal:2', 'status' => PaymentStatus::class, 'paid_at' => 'datetime', 'metadata' => 'array']; }
}
