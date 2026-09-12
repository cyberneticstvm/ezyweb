<?php

namespace App\Contact;

use App\Builder\DraftState;
use App\Models\Website;
use App\Models\WebsiteContactSetting;
use App\Models\WebsiteWhatsappSetting;
use App\Tenancy\TenantScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContactSettings
{
    public function __construct(private readonly DraftState $drafts) {}

    public function contact(Website $website): WebsiteContactSetting
    {
        return WebsiteContactSetting::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['website_id' => $website->getKey()],
            [
                'tenant_id' => $website->tenant_id,
                'form_fields' => ['name' => true, 'email' => true, 'phone' => true, 'subject' => true, 'message' => true],
                'recipient_email' => $website->tenant()->withoutGlobalScopes()->value('contact_email'),
            ],
        );
    }

    public function whatsapp(Website $website): WebsiteWhatsappSetting
    {
        return WebsiteWhatsappSetting::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['website_id' => $website->getKey()],
            ['tenant_id' => $website->tenant_id],
        );
    }

    /** @param array<string, mixed> $input */
    public function updateContact(Website $website, array $input): WebsiteContactSetting
    {
        return DB::transaction(function () use ($website, $input): WebsiteContactSetting {
            $setting = $this->contact($website);
            $setting->update([
                ...Arr::except($input, ['phone_numbers_text', 'email_addresses_text']),
                'phone_numbers' => $this->lines($input['phone_numbers_text'] ?? ''),
                'email_addresses' => $this->lines($input['email_addresses_text'] ?? ''),
            ]);
            $this->drafts->touch($website);

            return $setting;
        }, 3);
    }

    /** @param array<string, mixed> $input */
    public function updateWhatsapp(Website $website, array $input): WebsiteWhatsappSetting
    {
        return DB::transaction(function () use ($website, $input): WebsiteWhatsappSetting {
            $setting = $this->whatsapp($website);
            $setting->update([
                ...Arr::except($input, ['included_pages_text']),
                'included_pages' => $this->lines($input['included_pages_text'] ?? ''),
            ]);
            $this->drafts->touch($website);

            return $setting;
        }, 3);
    }

    /** @return list<string> */
    private function lines(mixed $value): array
    {
        return collect(preg_split('/\R/', is_string($value) ? $value : ''))->map(fn (string $line): string => trim($line))->filter()->unique()->values()->all();
    }
}
