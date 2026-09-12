<?php

namespace App\Http\Middleware;

use App\Enums\PlatformRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->getAttribute('platform_role');

        abort_unless($role === PlatformRole::SuperAdmin, 403);

        return $next($request);
    }
}
