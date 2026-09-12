<?php

namespace App\Jobs;

use App\Mail\ContactEnquiryMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendContactEnquiryEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $messageId, public readonly string $recipient)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $message = ContactMessage::query()->withoutGlobalScopes()->findOrFail($this->messageId);
        Mail::to($this->recipient)->send(new ContactEnquiryMail($message));
    }
}
