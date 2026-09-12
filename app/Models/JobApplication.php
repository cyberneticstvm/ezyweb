<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'website_id', 'job_opening_id', 'applicant_name', 'email', 'phone', 'cover_letter', 'resume_path', 'consent', 'status'])]
class JobApplication extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return BelongsTo<JobOpening, $this> */
    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    protected function casts(): array
    {
        return ['consent' => 'boolean'];
    }
}
