<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $key
 * @property mixed $value
 * @property string|null $label
 */
#[Fillable(['plan_id', 'key', 'value', 'label'])]
class PlanFeature extends Model
{
    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    protected function casts(): array { return ['value' => 'json']; }
}
