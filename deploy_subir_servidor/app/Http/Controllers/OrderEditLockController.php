<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OrdenEditLockService;
use App\Services\OrdenPolicyService;
use App\Support\ExactoAuthContext;
use Illuminate\Http\JsonResponse;

final class OrderEditLockController extends Controller
{
    public function __construct(
        private readonly OrdenEditLockService $locks,
        private readonly OrdenPolicyService $policy
    ) {}

    public function heartbeat(int $id): JsonResponse
    {
        $user = ExactoAuthContext::currentUser();
        abort_unless($user !== null, 403);
        abort_unless($this->policy->userCanAccessOrder($user, $id), 403);

        $operatorId = ExactoAuthContext::editLockUserId();
        $ok = $this->locks->renew($id, $operatorId > 0 ? $operatorId : null);
        if (! $ok) {
            // Lease expiró: intentar recuperar el bloqueo; si otro usuario lo tiene, falla.
            $acq = $this->locks->acquire($id, $user);
            $ok = (bool) ($acq['acquired'] ?? false);
        }

        $lockActivo = $ok ? null : $this->locks->activeLockForOrder($id);

        return response()->json([
            'success' => $ok,
            'holder_nombre' => $lockActivo->locked_by_nombre ?? null,
        ]);
    }

    public function release(int $id): JsonResponse
    {
        abort_unless(ExactoAuthContext::currentUser() !== null, 403);

        $operatorId = ExactoAuthContext::editLockUserId();
        $this->locks->release($id, $operatorId > 0 ? $operatorId : null);

        return response()->json(['success' => true]);
    }
}
