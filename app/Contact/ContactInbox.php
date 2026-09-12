<?php

namespace App\Contact;

use App\Models\ContactMessage;
use App\Models\Website;
use App\Tenancy\TenantScope;

class ContactInbox
{
    public function find(Website $website, string $publicId): ContactMessage
    {
        return ContactMessage::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())->where('public_id', $publicId)->firstOrFail();
    }

    public function markRead(ContactMessage $message): void
    {
        $message->update(['is_read' => true, 'read_at' => $message->read_at ?? now()]);
    }

    public function setArchived(ContactMessage $message, bool $archived): void
    {
        $message->update(['is_archived' => $archived, 'archived_at' => $archived ? now() : null]);
    }

    /** @return list<string> */
    public function csvRow(ContactMessage $message): array
    {
        $row = [
            $message->submitted_at?->toIso8601String(), $message->name, $message->email, $message->phone,
            $message->subject, $message->message, $message->source_page, $message->is_read ? 'yes' : 'no',
            $message->is_archived ? 'yes' : 'no',
        ];

        return array_map(function (mixed $value): string {
            $text = (string) $value;

            return preg_match('/^[=+\-@]/', $text) === 1 ? "'".$text : $text;
        }, $row);
    }
}
