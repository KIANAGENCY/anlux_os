<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ExactoUtf8;
use Illuminate\View\View;

class HistorialOrdenesController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $nombreTecnico = htmlspecialchars(
            (string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
        $nav_activo = 'historial_ordenes';
        $pageTitle = 'Historial PDF '.ExactoUtf8::fromCodepoint(0x00F3).'rdenes - Exacto';
        $pageHeadExtra = '';

        return view('orders.historial_page', compact(
            'nombreTecnico',
            'nav_activo',
            'pageTitle',
            'pageHeadExtra'
        ));
    }
}
