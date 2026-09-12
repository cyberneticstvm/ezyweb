<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'website_id', 'name', 'role', 'photo', 'bio', 'email', 'phone', 'linkedin_url', 'is_featured', 'is_active', 'display_order'])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use BelongsToTenant, BelongsToWebsite, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_active' => 'boolean'];
    }
}
