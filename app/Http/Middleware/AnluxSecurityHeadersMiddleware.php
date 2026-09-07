<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subconjunto de cabeceras del firewall legacy (CSP/HSTS opcional).
 */
final class AnluxSecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (config('anlux.firewall_disable', false) || config('anlux.firewall_skip_headers', false)) {
            return $response;
        }

        if (! config('anlux.csp_disable', false)) {
            $csp = config('anlux.csp');
            if (is_string($csp) && $csp !== '') {
                $response->headers->set('Content-Security-Policy', $csp);
            } else {
                $response->headers->set(
                    'Content-Security-Policy',
                    "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
                );
            }
        }

        if ($request->secure()) {
            $maxAge = (int) config('anlux.hsts_max_age', 0);
            if ($maxAge > 0) {
                $response->headers->set('Strict-Transport-Security', 'max-age='.$maxAge.'; includeSubDomains');
            }
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
