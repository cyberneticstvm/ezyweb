<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** @property list<string>|null $social_profiles */
#[Fillable(['tenant_id', 'website_id', 'site_title', 'default_meta_description', 'default_social_image', 'business_name', 'business_type', 'primary_language', 'search_engine_visibility', 'google_site_verification', 'google_analytics_id', 'social_profiles', 'not_found_title', 'not_found_message', 'not_found_button_label'])]
class WebsiteSeoSetting extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['social_profiles' => 'array', 'search_engine_visibility' => 'boolean'];
    }
}
