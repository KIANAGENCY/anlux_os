<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MaintenanceModeService;
use App\Services\OrdenPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MaintenanceController extends Controller
{
    public function __construct(
        private readonly MaintenanceModeService $maintenance,
        private readonly OrdenPolicyService $policy
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $status = $this->maintenance->status();
        if (! $status['enabled']) {
            return redirect()->route('dashboard');
        }

        return view('maintenance', [
            'pageTitle' => 'Sistema en mantenimiento - Exacto',
            'maintenance' => $status,
            'canDisableMaintenance' => $this->policy->userIsAdmin($request->user()),
        ]);
    }
}
