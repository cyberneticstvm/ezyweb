<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToWebsite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property list<string>|null $phone_numbers
 * @property list<string>|null $email_addresses
 * @property array<string, bool> $form_fields
 */
#[Fillable(['tenant_id', 'website_id', 'heading', 'description', 'address', 'phone_numbers', 'email_addresses', 'working_hours', 'maps_embed_url', 'form_fields', 'success_message', 'recipient_email', 'whatsapp_number', 'is_form_enabled'])]
class WebsiteContactSetting extends Model
{
    use BelongsToTenant, BelongsToWebsite;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'phone_numbers' => 'array',
            'email_addresses' => 'array',
            'form_fields' => 'array',
            'is_form_enabled' => 'boolean',
        ];
    }
}
