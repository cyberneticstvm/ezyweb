<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WebsiteVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use UnexpectedValueException;

#[Fillable(['tenant_id', 'website_id', 'version_number', 'reason', 'snapshot_json', 'created_by', 'published_at'])]
class WebsiteVersion extends Model
{
    /** @use HasFactory<WebsiteVersionFactory> */
    use BelongsToTenant, HasFactory;

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $snapshot = $this->getAttribute('snapshot_json');
        if (! is_array($snapshot)) {
            throw new UnexpectedValueException('Website version snapshot is invalid.');
        }

        return $snapshot;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
