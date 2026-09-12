<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'website_id', 'question', 'answer', 'category', 'is_active', 'display_order'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
