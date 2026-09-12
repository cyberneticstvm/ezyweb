<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/** @property Carbon|null $submitted_at */
#[Fillable(['public_id', 'tenant_id', 'website_id', 'name', 'email', 'phone', 'subject', 'message', 'source_page', 'referrer', 'utm_parameters', 'ip_hash', 'is_read', 'read_at', 'is_archived', 'archived_at', 'submitted_at'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->public_id ??= (string) Str::ulid();
            $message->submitted_at ??= now();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'utm_parameters' => 'array', 'is_read' => 'boolean', 'read_at' => 'datetime',
            'is_archived' => 'boolean', 'archived_at' => 'datetime', 'submitted_at' => 'datetime',
        ];
    }
}
