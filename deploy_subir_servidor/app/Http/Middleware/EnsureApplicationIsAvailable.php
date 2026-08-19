<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\MaintenanceModeService;
use App\Services\OrdenPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApplicationIsAvailable
{
    public function __construct(private readonly MaintenanceModeService $maintenance, private readonly OrdenPolicyService $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $this->policy->userIsAdmin($user) || ! $this->maintenance->enabled()) {
            return $next($request);
        }
        if ($request->routeIs('maintenance.show', 'logout')) {
            return $next($request);
        }
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => $this->maintenance->status()['message'], 'maintenance' => true], 503);
        }

        return redirect()->route('maintenance.show');
    }
}
