<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MaintenanceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private readonly MaintenanceModeService $maintenance) {}

    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user, 403);

        return view('admin.index', [
            'pageTitle' => 'Panel de administrador - Exacto',
            'nombreTecnico' => htmlspecialchars((string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''), ENT_QUOTES, 'UTF-8'),
            'nav_admin_activo' => 'admin',
            'maintenance' => $this->maintenance->status(),
        ]);
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $validated = $request->validate(['enabled' => ['nullable', 'boolean'], 'message' => ['nullable', 'string', 'max:500']]);
        $enabled = $request->boolean('enabled');
        $this->maintenance->update($enabled, (string) ($validated['message'] ?? ''));

        return redirect()->route('admin.index')->with('status', $enabled
            ? 'Modo de mantenimiento activado. Los administradores conservan el acceso.'
            : 'Modo de mantenimiento desactivado. El sistema está disponible para todos.');
    }
}
