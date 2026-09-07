<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FolioSequenceService;
use App\Services\OrdenAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AdminFoliosController extends Controller
{
    public function __construct(
        private readonly FolioSequenceService $folios,
        private readonly OrdenAuditService $audit
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $anio = (int) $request->query('anio', date('Y'));
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) date('Y');
        }

        $status = $this->folios->status($anio);
        $nombreTecnico = htmlspecialchars(
            (string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );

        return view('admin.folios', [
            'pageTitle' => 'Folios de órdenes - Anlux',
            'nombreTecnico' => $nombreTecnico,
            'nav_admin_activo' => 'folios',
            'status' => $status,
            'anio' => $anio,
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $anio = (int) $request->input('anio', date('Y'));
        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) date('Y');
        }

        $result = $this->folios->syncNextNum($anio);
        $this->audit->log(0, $this->folios->peekNextFolio($anio), 'folio_secuencia_ajustada', [
            'anio' => $anio,
            'anterior' => $result['anterior'] ?? null,
            'next_num' => $result['next_num'] ?? null,
            'accion' => 'sincronizar_contador',
        ]);

        return redirect()
            ->route('admin.folios.index', ['anio' => $anio])
            ->with('success', $result['message'] ?? 'Contador sincronizado.');
    }
}
