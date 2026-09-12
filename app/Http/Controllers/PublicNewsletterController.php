<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PublicNewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request, CurrentTenant $context): RedirectResponse
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);
        $data = $request->validated();
        $email = mb_strtolower(trim((string) $data['email']));

        DB::transaction(function () use ($website, $data, $email, $request): void {
            NewsletterSubscriber::query()->withoutGlobalScopes()->updateOrCreate(
                ['website_id' => $website->getKey(), 'email' => $email],
                [
                    'tenant_id' => $website->tenant_id,
                    'name' => $this->plain($data['name'] ?? null),
                    'source_page' => $this->sourcePage($request),
                    'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip().'|'.$website->getKey(), (string) config('app.key')) : null,
                    'consented_at' => now(),
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ],
            );
        }, 3);

        return back()->with('newsletter_success', true);
    }

    private function plain(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim(strip_tags($value));

        return $clean !== '' ? $clean : null;
    }

    private function sourcePage(StoreNewsletterSubscriptionRequest $request): string
    {
        $referrer = $request->headers->get('referer');
        $path = is_string($referrer) ? parse_url($referrer, PHP_URL_PATH) : null;

        return is_string($path) && $path !== '' ? mb_substr($path, 0, 255) : '/';
    }
}
