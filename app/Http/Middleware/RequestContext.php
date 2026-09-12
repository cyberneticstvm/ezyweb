<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestContext
{
    private float $startedAt = 0.0;

    private string $requestId = '';

    public function handle(Request $request, Closure $next): Response
    {
        $this->startedAt = microtime(true);
        $supplied = $request->headers->get('X-Request-ID');
        $this->requestId = is_string($supplied) && preg_match('/^[A-Za-z0-9._-]{8,100}$/', $supplied) === 1
            ? $supplied
            : (string) Str::uuid();

        $request->attributes->set('request_id', $this->requestId);
        Log::withContext([
            'request_id' => $this->requestId,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $this->requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $durationMs = (microtime(true) - $this->startedAt) * 1000;
        $threshold = (int) config('observability.slow_request_ms', 1500);

        if ($threshold > 0 && $durationMs >= $threshold) {
            Log::warning('Slow HTTP request detected.', [
                'request_id' => $this->requestId,
                'duration_ms' => round($durationMs, 2),
                'status' => $response->getStatusCode(),
                'tenant_id' => $request->attributes->get('tenant')?->getKey(),
                'website_id' => $request->attributes->get('website')?->getKey(),
            ]);
        }
    }
}
