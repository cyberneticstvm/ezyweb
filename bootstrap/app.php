<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\ApplyPublishedRedirects;
use App\Http\Middleware\EnsureWebsitePublished;
use App\Http\Middleware\EnsureSubscriptionAllowsPublicAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolvePublicWebsite;
use App\Http\Middleware\ResolveTenantFromDomain;
use App\Http\Middleware\ResolveTenantFromMembership;
use App\Http\Middleware\RequestContext;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([RequestContext::class, SecurityHeaders::class]);
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias([
            'super-admin' => EnsureSuperAdmin::class,
            'resolve-tenant-domain' => ResolveTenantFromDomain::class,
            'resolve-public-website' => ResolvePublicWebsite::class,
            'website-published' => EnsureWebsitePublished::class,
            'subscription-public-access' => EnsureSubscriptionAllowsPublicAccess::class,
            'published-redirects' => ApplyPublishedRedirects::class,
            'tenant-member' => ResolveTenantFromMembership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
