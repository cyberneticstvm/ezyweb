<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Contact\ContactInbox;
use App\Models\ContactMessage;
use App\Models\User;
use App\Tenancy\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactMessageController extends Controller
{
    public function read(string $message, Request $request, BuilderWorkspace $workspace, ContactInbox $inbox): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $entry = $inbox->find($website, $message);
        $inbox->markRead($entry);
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications()->where('data->contact_message_id', $message)->get()->each->markAsRead();

        return back()->with('success', 'Enquiry marked as read.');
    }

    public function archive(string $message, Request $request, BuilderWorkspace $workspace, ContactInbox $inbox): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $entry = $inbox->find($website, $message);
        $inbox->setArchived($entry, $request->boolean('archived', true));

        return back()->with('success', $entry->is_archived ? 'Enquiry archived.' : 'Enquiry restored.');
    }

    public function export(BuilderWorkspace $workspace, ContactInbox $inbox): StreamedResponse
    {
        $website = $workspace->website();
        $this->authorize('view', $website);

        return response()->streamDownload(function () use ($website, $inbox): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Submitted at', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Source page', 'Read', 'Archived']);
            ContactMessage::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())
                ->orderByDesc('submitted_at')->each(fn (ContactMessage $message): int|false => fputcsv($handle, $inbox->csvRow($message)));
            fclose($handle);
        }, 'ezyweb-enquiries-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
