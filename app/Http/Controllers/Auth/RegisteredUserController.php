<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (! PlatformSetting::current()->registrations_enabled) {
            return redirect()->route('login')->with('error', 'New account registration is currently unavailable.');
        }

        return Inertia::render('auth/register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        abort_unless(PlatformSetting::current()->registrations_enabled, 403, 'New account registration is currently unavailable.');

        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('onboarding.create')->with('success', 'Welcome to Ezyweb. Let’s create your workspace.');
    }
}
