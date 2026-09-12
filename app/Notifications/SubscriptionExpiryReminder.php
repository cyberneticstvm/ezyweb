<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription, public readonly int $days) {}
    /** @return list<string> */
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->days > 0 ? "Your Ezyweb subscription expires in {$this->days} day(s)." : ($this->days === 0 ? 'Your Ezyweb subscription expires today.' : 'Your Ezyweb subscription has expired.');
        return (new MailMessage)->subject('Ezyweb subscription reminder')->greeting('Hello '.$notifiable->name.',')->line($message)->action('Review subscription', url('/dashboard/subscription'))->line('Renew before the grace period ends to keep your published website online.');
    }
    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Subscription reminder', 'message' => $this->days > 0 ? "Expires in {$this->days} day(s)." : 'Renewal is due.', 'subscription_id' => $this->subscription->public_id];
    }
}
