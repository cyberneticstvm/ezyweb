<?php

namespace App\Contact;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TurnstileValidator
{
    public function validate(?string $token, ?string $ip): void
    {
        $secret = (string) config('services.turnstile.secret_key');
        if ($secret === '') {
            return;
        }
        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages(['cf-turnstile-response' => 'Please complete the security check.']);
        }

        try {
            $result = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret, 'response' => $token, 'remoteip' => $ip,
            ])->throw()->json();
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification request failed.', ['exception' => $exception::class]);
            throw ValidationException::withMessages(['cf-turnstile-response' => 'The security check could not be verified. Please retry.']);
        }

        if (! is_array($result) || ($result['success'] ?? false) !== true) {
            throw ValidationException::withMessages(['cf-turnstile-response' => 'The security check was unsuccessful. Please retry.']);
        }
    }
}
