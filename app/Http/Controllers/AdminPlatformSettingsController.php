<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlatformSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('admin/platform-settings', [
            'settings' => $settings->only([
                'platform_name', 'support_email', 'registrations_enabled', 'default_country_code', 'default_timezone', 'default_locale',
            ]),
            'options' => [
                'countries' => config('onboarding.countries'),
                'timezones' => config('onboarding.timezones'),
                'languages' => config('onboarding.languages'),
            ],
            'environment' => [
                'root_domain' => config('domains.root'),
                'marketing_domain' => config('domains.marketing'),
                'dashboard_domain' => config('domains.dashboard'),
                'admin_domain' => config('domains.admin'),
                'media_disk' => config('media.disk'),
                'cache_store' => config('cache.default'),
                'queue_connection' => config('queue.default'),
            ],
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request): RedirectResponse
    {
        $settings = PlatformSetting::query()->firstOrNew(['id' => 1]);
        $before = $settings->exists ? $settings->only($settings->getFillable()) : null;
        $settings->fill($request->validated())->save();

        $actor = $request->user();
        AuditLog::query()->create([
            'actor_id' => $actor instanceof User ? $actor->getKey() : null,
            'action' => 'platform.settings_updated',
            'auditable_type' => PlatformSetting::class,
            'auditable_id' => $settings->getKey(),
            'before' => $before,
            'after' => $settings->only($settings->getFillable()),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        return back()->with('success', 'Platform settings saved.');
    }
}
