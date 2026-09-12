<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicWebsite
{
    public function __construct(
        private readonly CurrentTenant $context,
        private readonly ResolveTenantFromDomain $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        if ($this->isPlatformHost(strtolower($request->getHost()))) {
            return $next($request);
        }

        return $this->resolver->handle($request, $next);
    }

    private function isPlatformHost(string $hostname): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $hosts = [...array_values(config('domains')), $appHost, 'localhost', '127.0.0.1'];

        return in_array($hostname, array_filter($hosts, 'is_string'), true);
    }
}
