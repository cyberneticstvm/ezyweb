<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'client_name', 'client_role', 'company', 'photo', 'quote', 'rating', 'is_featured', 'is_active', 'display_order'])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }
}
