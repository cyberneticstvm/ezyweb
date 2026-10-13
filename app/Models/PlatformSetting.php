<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['platform_name', 'support_email', 'registrations_enabled', 'default_country_code', 'default_timezone', 'default_locale'])]
class PlatformSetting extends Model
{
    public static function current(): self
    {
        $defaults = [
            'platform_name' => (string) config('app.name', 'Ezyweb'),
            'support_email' => null,
            'registrations_enabled' => true,
            'default_country_code' => 'IN',
            'default_timezone' => 'Asia/Kolkata',
            'default_locale' => 'en',
        ];

        if (! Schema::hasTable((new static)->getTable())) {
            return new static($defaults);
        }

        return static::query()->first() ?? new static($defaults);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['registrations_enabled' => 'boolean'];
    }
}
