<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\JobOpeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'website_id', 'title', 'slug', 'department', 'location', 'employment_type', 'workplace_type', 'summary', 'description', 'responsibilities', 'requirements', 'salary_display', 'closing_date', 'application_email', 'is_active', 'display_order', 'seo_title', 'seo_description'])]
class JobOpening extends Model
{
    /** @use HasFactory<JobOpeningFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory;

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    protected function casts(): array
    {
        return ['closing_date' => 'date', 'is_active' => 'boolean'];
    }
}
