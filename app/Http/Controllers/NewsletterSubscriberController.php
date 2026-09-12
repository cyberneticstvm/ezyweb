<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Models\NewsletterSubscriber;
use App\Tenancy\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function status(Request $request, BuilderWorkspace $workspace, NewsletterSubscriber $subscriber): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        abort_unless((int) $subscriber->website_id === (int) $website->getKey(), 404);
        $validated = $request->validate(['subscribed' => ['required', 'boolean']]);
        $subscriber->update([
            'subscribed_at' => $validated['subscribed'] ? now() : $subscriber->subscribed_at,
            'unsubscribed_at' => $validated['subscribed'] ? null : now(),
        ]);

        return back()->with('success', $validated['subscribed'] ? 'Subscriber reactivated.' : 'Subscriber unsubscribed.');
    }

    public function destroy(BuilderWorkspace $workspace, NewsletterSubscriber $subscriber): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        abort_unless((int) $subscriber->website_id === (int) $website->getKey(), 404);
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }

    public function export(BuilderWorkspace $workspace): StreamedResponse
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $subscribers = NewsletterSubscriber::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->orderByDesc('subscribed_at')->get();

        return response()->streamDownload(function () use ($subscribers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Name', 'Email', 'Status', 'Source page', 'Consented at', 'Subscribed at', 'Unsubscribed at']);
            foreach ($subscribers as $subscriber) {
                fputcsv($handle, [
                    $this->spreadsheetSafe($subscriber->name),
                    $this->spreadsheetSafe($subscriber->email),
                    $subscriber->unsubscribed_at === null ? 'Subscribed' : 'Unsubscribed',
                    $this->spreadsheetSafe($subscriber->source_page),
                    $subscriber->consented_at?->toIso8601String(),
                    $subscriber->subscribed_at?->toIso8601String(),
                    $subscriber->unsubscribed_at?->toIso8601String(),
                ]);
            }
            fclose($handle);
        }, 'newsletter-subscribers.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $text = is_string($value) ? $value : '';

        return preg_match('/^[=+\-@]/', ltrim($text)) === 1 ? "'".$text : $text;
    }
}
