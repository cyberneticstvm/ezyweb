<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactMessage $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Ezyweb enquiry: '.($this->enquiry->subject ?: $this->enquiry->name));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-enquiry');
    }
}
