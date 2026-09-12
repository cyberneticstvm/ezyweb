<?php

namespace App\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ReadinessProbe
{
    /** @return array{ready: bool, checks: array<string, array{ok: bool, latency_ms: float}>} */
    public function run(): array
    {
        $checks = [];

        if (config('observability.readiness.database')) {
            $checks['database'] = $this->check(fn () => DB::select('select 1'));
        }

        if (config('observability.readiness.cache')) {
            $checks['cache'] = $this->check(function (): void {
                $key = 'readiness:'.Str::uuid();
                Cache::put($key, 'ok', 10);
                throw_unless(Cache::get($key) === 'ok', new \RuntimeException('Cache read/write check failed.'));
                Cache::forget($key);
            });
        }

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['ok']),
            'checks' => $checks,
        ];
    }

    /** @return array{ok: bool, latency_ms: float} */
    private function check(callable $callback): array
    {
        $startedAt = microtime(true);

        try {
            $callback();
            $ok = true;
        } catch (Throwable) {
            $ok = false;
        }

        return ['ok' => $ok, 'latency_ms' => round((microtime(true) - $startedAt) * 1000, 2)];
    }
}
