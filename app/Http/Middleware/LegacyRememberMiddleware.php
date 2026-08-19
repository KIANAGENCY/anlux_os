<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RememberTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LegacyRememberMiddleware
{
    public function __construct(
        private readonly RememberTokenService $remember
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->remember->attemptFromCookie();

        return $next($request);
    }
}
