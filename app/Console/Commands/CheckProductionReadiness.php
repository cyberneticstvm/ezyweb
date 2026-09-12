<?php

namespace App\Console\Commands;

use App\Monitoring\ProductionConfiguration;
use Illuminate\Console\Command;

class CheckProductionReadiness extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Validate security-critical Ezyweb production configuration';

    public function handle(ProductionConfiguration $configuration): int
    {
        foreach ($configuration->checks() as $name => $check) {
            $line = sprintf('%-20s %s', $name, $check['ok'] ? 'OK' : 'FAIL — '.$check['message']);
            $check['ok'] ? $this->components->info($line) : $this->components->error($line);
        }

        if (! $configuration->isReady()) {
            $this->newLine();
            $this->error('Production configuration is not ready. No secrets were displayed.');

            return self::FAILURE;
        }

        $this->info('Production configuration checks passed.');

        return self::SUCCESS;
    }
}
