<?php

namespace App\Http\Middleware;

use App\Enums\WebsiteStatus;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebsitePublished
{
    public function __construct(private readonly CurrentTenant $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $website = $this->context->website();
        if ($website instanceof Website) {
            abort_unless($website->getAttribute('status') === WebsiteStatus::Published, 404);
        }

        return $next($request);
    }
}
