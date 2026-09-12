<?php

namespace App\Console\Commands;

use App\Subscriptions\SubscriptionLifecycle;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';
    protected $description = 'Send renewal reminders and apply subscription lifecycle transitions';
    public function handle(SubscriptionLifecycle $lifecycle): int
    {
        $result = $lifecycle->process();
        $this->info("Processed {$result['transitioned']} transition(s) and {$result['reminders']} reminder(s).");
        return self::SUCCESS;
    }
}
