<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Console\Command;

class BackfillSubscriptions extends Command
{
    protected $signature = 'subscriptions:backfill';
    protected $description = 'Assign the Starter trial to tenants that do not yet have a subscription';
    public function handle(SubscriptionManager $manager): int
    {
        $count = 0;
        Tenant::query()->whereDoesntHave('subscriptions')->chunkById(100, function ($tenants) use ($manager, &$count): void {
            foreach ($tenants as $tenant) if ($manager->startTrial($tenant)) $count++;
        });
        $this->info("Created {$count} subscription(s).");
        return self::SUCCESS;
    }
}
