<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property Carbon|null $consented_at
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $unsubscribed_at
 */
#[Fillable(['public_id', 'tenant_id', 'website_id', 'name', 'email', 'source_page', 'ip_hash', 'consented_at', 'subscribed_at', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            $subscriber->public_id ??= (string) Str::ulid();
            $subscriber->consented_at ??= now();
            $subscriber->subscribed_at ??= now();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
