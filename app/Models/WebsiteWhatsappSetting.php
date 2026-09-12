<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** @property list<string>|null $included_pages */
#[Fillable(['tenant_id', 'website_id', 'is_enabled', 'phone_number', 'default_message', 'show_on_desktop', 'show_on_tablet', 'show_on_mobile', 'position', 'bottom_offset', 'display_delay', 'pulse_animation', 'show_label', 'included_pages', 'mobile_sticky_bar', 'call_number', 'directions_url'])]
class WebsiteWhatsappSetting extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean', 'show_on_desktop' => 'boolean', 'show_on_tablet' => 'boolean',
            'show_on_mobile' => 'boolean', 'pulse_animation' => 'boolean', 'show_label' => 'boolean',
            'included_pages' => 'array', 'mobile_sticky_bar' => 'boolean',
        ];
    }
}
