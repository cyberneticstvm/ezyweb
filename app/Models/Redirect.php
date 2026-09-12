<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'tenant_id', 'website_id', 'source_path', 'target_path', 'status_code', 'is_active', 'hit_count', 'last_hit_at'])]
class Redirect extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    protected static function booted(): void
    {
        static::creating(fn (self $redirect) => $redirect->public_id ??= (string) Str::ulid());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];
    }
}
