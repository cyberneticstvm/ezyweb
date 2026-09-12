<?php

namespace App\Contact;

class WhatsappUrl
{
    public function make(?string $number, ?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        if (! is_string($digits) || strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return 'https://wa.me/'.$digits.($message ? '?text='.rawurlencode($message) : '');
    }
}
