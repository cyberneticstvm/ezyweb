<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewContactEnquiryNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ContactMessage $message) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'website_id' => $this->message->website_id,
            'contact_message_id' => $this->message->public_id,
            'title' => 'New website enquiry',
            'message' => $this->message->name.' submitted an enquiry.',
        ];
    }
}
