<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Contact\ContactSettings;
use App\Http\Requests\UpdateContactSettingsRequest;
use App\Http\Requests\UpdateWhatsappSettingsRequest;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Tenancy\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactDashboardController extends Controller
{
    public function index(Request $request, BuilderWorkspace $workspace, ContactSettings $settings): Response
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $contact = $settings->contact($website);
        $whatsapp = $settings->whatsapp($website);
        $messages = ContactMessage::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())
            ->when($request->boolean('archived'), fn ($query) => $query->where('is_archived', true), fn ($query) => $query->where('is_archived', false))
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%'));
            })->orderByDesc('submitted_at')->paginate(20)->withQueryString();
        $subscribers = NewsletterSubscriber::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())
            ->when($request->string('subscriber_search')->trim()->value(), function ($query, string $search): void {
                $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            })
            ->when($request->string('subscriber_status')->value(), function ($query, string $status): void {
                if ($status === 'subscribed') {
                    $query->whereNull('unsubscribed_at');
                } elseif ($status === 'unsubscribed') {
                    $query->whereNotNull('unsubscribed_at');
                }
            })->orderByDesc('subscribed_at')->paginate(20, ['*'], 'subscriber_page')->withQueryString();
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('contact/index', [
            'contactSettings' => [
                ...$contact->toArray(),
                'phone_numbers_text' => implode("\n", $contact->phone_numbers ?? []),
                'email_addresses_text' => implode("\n", $contact->email_addresses ?? []),
            ],
            'whatsappSettings' => [
                ...$whatsapp->toArray(),
                'included_pages_text' => implode("\n", $whatsapp->included_pages ?? []),
            ],
            'messages' => $messages,
            'subscribers' => $subscribers,
            'notifications' => $user->notifications()->where('data->website_id', $website->getKey())->latest()->limit(10)->get(),
            'filters' => $request->only(['search', 'archived', 'subscriber_search', 'subscriber_status']),
        ]);
    }

    public function updateContact(UpdateContactSettingsRequest $request, BuilderWorkspace $workspace, ContactSettings $settings): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $settings->updateContact($website, $request->validated());

        return back()->with('success', 'Contact settings saved.');
    }

    public function updateWhatsapp(UpdateWhatsappSettingsRequest $request, BuilderWorkspace $workspace, ContactSettings $settings): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $settings->updateWhatsapp($website, $request->validated());

        return back()->with('success', 'WhatsApp settings saved.');
    }
}
