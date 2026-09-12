<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subscription_id', 'reminder_key', 'sent_at'])]
class SubscriptionReminder extends Model
{
    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    protected function casts(): array { return ['sent_at' => 'datetime']; }
}
