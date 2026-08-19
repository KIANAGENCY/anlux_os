<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\OrdenPolicyService;

final class OrderPolicy
{
    public function __construct(
        private readonly OrdenPolicyService $policyService
    ) {}

    public function access(User $user, int $orderId): bool
    {
        return $this->policyService->userCanAccessOrder($user, $orderId);
    }
}
