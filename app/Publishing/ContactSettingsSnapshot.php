<?php

namespace App\Publishing;

use App\Contact\ContactSettings;
use App\Contact\WhatsappUrl;
use App\Models\Website;
use App\Models\WebsiteContactSetting;
use App\Models\WebsiteWhatsappSetting;
use App\Tenancy\TenantScope;
use Illuminate\Support\Arr;

class ContactSettingsSnapshot
{
    public function __construct(private readonly ContactSettings $settings, private readonly WhatsappUrl $whatsapp) {}

    /** @return array<string, mixed> */
    public function capture(Website $website): array
    {
        return [
            'contact' => Arr::except($this->settings->contact($website)->attributesToArray(), ['id', 'tenant_id', 'website_id', 'created_at', 'updated_at']),
            'whatsapp' => Arr::except($this->settings->whatsapp($website)->attributesToArray(), ['id', 'tenant_id', 'website_id', 'created_at', 'updated_at']),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{contact: array<string, mixed>, whatsapp: array<string, mixed>}
     */
    public function publicSettings(array $snapshot, ?string $pageSlug): array
    {
        $settings = $this->associative($snapshot['settings'] ?? null);
        $contact = $this->associative($settings['contact'] ?? null);
        $whatsapp = $this->associative($settings['whatsapp'] ?? null);
        $included = is_array($whatsapp['included_pages'] ?? null) ? $whatsapp['included_pages'] : [];
        $current = $pageSlug === null ? '/' : '/'.$pageSlug;
        $pageIncluded = $included === [] || in_array('*', $included, true) || in_array($current, $included, true) || in_array(ltrim($current, '/'), $included, true);

        return [
            'contact' => Arr::only($contact, ['heading', 'description', 'address', 'phone_numbers', 'email_addresses', 'working_hours', 'maps_embed_url', 'form_fields', 'success_message', 'whatsapp_number', 'is_form_enabled']),
            'whatsapp' => [
                ...Arr::only($whatsapp, ['is_enabled', 'show_on_desktop', 'show_on_tablet', 'show_on_mobile', 'position', 'bottom_offset', 'display_delay', 'pulse_animation', 'show_label', 'mobile_sticky_bar', 'call_number', 'directions_url']),
                'is_enabled' => ($whatsapp['is_enabled'] ?? false) === true && $pageIncluded,
                'url' => $this->whatsapp->make(is_string($whatsapp['phone_number'] ?? null) ? $whatsapp['phone_number'] : null, is_string($whatsapp['default_message'] ?? null) ? $whatsapp['default_message'] : null),
            ],
        ];
    }

    /** @param array<string, mixed> $settings */
    public function restore(Website $website, array $settings): void
    {
        $contact = $this->associative($settings['contact'] ?? null);
        if ($contact !== []) {
            WebsiteContactSetting::query()->withoutGlobalScope(TenantScope::class)->updateOrCreate(
                ['website_id' => $website->getKey()],
                ['tenant_id' => $website->tenant_id, ...Arr::only($contact, (new WebsiteContactSetting)->getFillable())],
            );
        }
        $whatsapp = $this->associative($settings['whatsapp'] ?? null);
        if ($whatsapp !== []) {
            WebsiteWhatsappSetting::query()->withoutGlobalScope(TenantScope::class)->updateOrCreate(
                ['website_id' => $website->getKey()],
                ['tenant_id' => $website->tenant_id, ...Arr::only($whatsapp, (new WebsiteWhatsappSetting)->getFillable())],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function privateContact(array $snapshot): array
    {
        $settings = $this->associative($snapshot['settings'] ?? null);

        return $this->associative($settings['contact'] ?? null);
    }

    /** @return array<string, mixed> */
    private function associative(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
