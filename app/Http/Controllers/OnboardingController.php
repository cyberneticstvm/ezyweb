<?php

namespace App\Http\Controllers;

use App\Actions\CreateTenantFromOnboarding;
use App\Enums\MembershipStatus;
use App\Enums\PlatformRole;
use App\Http\Requests\StoreOnboardingRequest;
use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->getAttribute('platform_role') === PlatformRole::SuperAdmin) {
            return redirect()->route('admin.index');
        }

        if ($this->hasTenant($user)) {
            return redirect()->route('dashboard');
        }

        $settings = PlatformSetting::current();

        return Inertia::render('onboarding/index', [
            'options' => config('onboarding'),
            'defaults' => [
                'country_code' => $settings->default_country_code,
                'timezone' => $settings->default_timezone,
                'locale' => $settings->default_locale,
            ],
        ]);
    }

    public function store(StoreOnboardingRequest $request, CreateTenantFromOnboarding $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->getAttribute('platform_role') === PlatformRole::SuperAdmin, 403);
        abort_if($this->hasTenant($user), 409, 'Your workspace has already been created.');

        $action->execute($user, $request->onboardingData());

        return redirect()->route('dashboard')->with('success', 'Your Ezyweb workspace is ready.');
    }

    private function hasTenant(User $user): bool
    {
        return $user->tenantMemberships()
            ->withoutGlobalScopes()
            ->where('status', MembershipStatus::Active->value)
            ->exists();
    }
}
