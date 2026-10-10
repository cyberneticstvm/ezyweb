<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.headers_enabled')) {
            return $next($request);
        }

        $nonce = bin2hex(random_bytes(16));
        Vite::useCspNonce($nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);
        $response->headers->set('Content-Security-Policy'.(config('security.csp_report_only') ? '-Report-Only' : ''), $this->contentSecurityPolicy($nonce));
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure() && app()->environment('production')) {
            $hsts = 'max-age='.(int) config('security.hsts_max_age');
            if (config('security.hsts_include_subdomains')) {
                $hsts .= '; includeSubDomains';
            }
            if (config('security.hsts_preload')) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'nonce-{$nonce}' https://challenges.cloudflare.com https://www.googletagmanager.com",
            "connect-src 'self' https://challenges.cloudflare.com https://www.google-analytics.com",
            'frame-src https://challenges.cloudflare.com https://www.google.com https://maps.google.com https://www.youtube-nocookie.com https://player.vimeo.com',
            "media-src 'self' https://storage.googleapis.com",
        ];

        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $reportUri = config('security.csp_report_uri');
        if (is_string($reportUri) && filter_var($reportUri, FILTER_VALIDATE_URL)) {
            $directives[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $directives).';';
    }
}
