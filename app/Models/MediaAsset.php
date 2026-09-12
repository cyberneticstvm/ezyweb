<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** @property array<string, array{path: string, width: int, height: int, size: int, url: string}> $variants */
#[Fillable(['public_id', 'tenant_id', 'website_id', 'uploaded_by', 'disk', 'path', 'original_name', 'source_mime_type', 'mime_type', 'extension', 'size', 'total_size', 'width', 'height', 'folder', 'alt_text', 'title', 'caption', 'variants', 'checksum'])]
class MediaAsset extends Model
{
    use BelongsToTenant, BelongsToWebsite, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(fn (self $asset) => $asset->public_id ??= (string) Str::ulid());
    }

    /** @return HasMany<MediaUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['variants' => 'array'];
    }
}
