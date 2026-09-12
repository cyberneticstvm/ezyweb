<?php

namespace App\Actions;

use App\Contact\PublicContactConfig;
use App\Contact\TurnstileValidator;
use App\Enums\MembershipStatus;
use App\Jobs\SendContactEnquiryEmail;
use App\Models\ContactMessage;
use App\Models\TenantMembership;
use App\Models\Website;
use App\Notifications\NewContactEnquiryNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubmitContactMessage
{
    public function __construct(private readonly PublicContactConfig $settings, private readonly TurnstileValidator $turnstile) {}

    /** @param array<string, mixed> $input */
    public function execute(Website $website, array $input, Request $request): ContactMessage
    {
        $setting = $this->settings->forWebsite($website);
        if (($setting['is_form_enabled'] ?? false) !== true) {
            throw ValidationException::withMessages(['form' => 'This contact form is currently unavailable.']);
        }
        $this->turnstile->validate(is_string($input['cf-turnstile-response'] ?? null) ? $input['cf-turnstile-response'] : null, $request->ip());

        $message = DB::transaction(function () use ($website, $input, $request): ContactMessage {
            return ContactMessage::query()->withoutGlobalScopes()->create([
                'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(),
                'name' => $this->plain($input['name'] ?? ''), 'email' => $this->plain($input['email'] ?? null),
                'phone' => $this->plain($input['phone'] ?? null), 'subject' => $this->plain($input['subject'] ?? null),
                'message' => $this->plain($input['message'] ?? ''), 'source_page' => $this->sourcePage($request),
                'referrer' => $request->headers->get('referer'), 'utm_parameters' => $this->utm($request),
                'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip().'|'.$website->getKey(), (string) config('app.key')) : null,
            ]);
        }, 3);

        $users = TenantMembership::query()->withoutGlobalScopes()->with('user')
            ->where('tenant_id', $website->tenant_id)->where('status', MembershipStatus::Active->value)
            ->get()->pluck('user')->filter();
        Notification::sendNow($users, new NewContactEnquiryNotification($message));
        $recipient = $setting['recipient_email'] ?? null;
        if (is_string($recipient) && $recipient !== '') {
            SendContactEnquiryEmail::dispatch((int) $message->getKey(), $recipient)->afterCommit();
        }

        return $message;
    }

    private function plain(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim(strip_tags($value));

        return $clean !== '' ? $clean : null;
    }

    private function sourcePage(Request $request): string
    {
        $referrer = $request->headers->get('referer');
        $path = is_string($referrer) ? parse_url($referrer, PHP_URL_PATH) : null;

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /** @return array<string, string> */
    private function utm(Request $request): array
    {
        $referrer = $request->headers->get('referer');
        $query = is_string($referrer) ? parse_url($referrer, PHP_URL_QUERY) : null;
        $parameters = [];
        if (is_string($query)) {
            parse_str($query, $parameters);
        }

        return collect(Arr::only($parameters, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']))
            ->filter(fn (mixed $value): bool => is_string($value))
            ->map(fn (string $value): string => mb_substr($value, 0, 255))
            ->all();
    }
}
