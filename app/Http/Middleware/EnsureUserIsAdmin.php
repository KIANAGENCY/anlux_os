<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OrdenPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    public function __construct(
        private readonly OrdenPolicyService $policy
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->policy->userIsAdmin($request->user())) {
            return redirect()->route('ordenes.index');
        }

        return $next($request);
    }
}
