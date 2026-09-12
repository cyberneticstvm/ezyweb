<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $code
 * @property int $trial_days
 * @property int $grace_days
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanFeature> $features
 */
#[Fillable(['public_id', 'code', 'name', 'description', 'annual_price', 'currency', 'trial_days', 'grace_days', 'retention_days', 'is_active', 'is_featured', 'sort_order'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /** @return HasMany<PlanFeature, $this> */
    public function features(): HasMany { return $this->hasMany(PlanFeature::class); }
    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }

    protected function casts(): array
    {
        return ['annual_price' => 'decimal:2', 'is_active' => 'boolean', 'is_featured' => 'boolean'];
    }
}
