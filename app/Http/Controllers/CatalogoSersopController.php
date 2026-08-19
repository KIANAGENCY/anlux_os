<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OrdenPolicyService;
use App\Services\PdfCondicionesService;
use App\Services\SersopCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CatalogoSersopController extends Controller
{
    public function __construct(
        private readonly SersopCatalogService $catalog,
        private readonly PdfCondicionesService $pdfCondiciones,
        private readonly OrdenPolicyService $policy
    ) {}

    public function index(): View
    {
        $guest = (bool) config('exacto.catalogo_sersop_guest');
        $user = Auth::user();
        if (! $guest) {
            abort_unless($user, 403);
        }

        $nombre = session('nombre_tecnico');
        if (($nombre === null || $nombre === '') && $user instanceof User) {
            $nombre = $user->nombre_tecnico;
        }
        if ($nombre === null || $nombre === '') {
            $nombre = $guest ? 'Local (sin sesión)' : '';
        }

        $canEditPdfCondiciones = $user instanceof User && $this->policy->userIsAdmin($user);

        // Sin SSH: si la BD aún tiene precios viejos con IVA (ej. SERSOP01=700),
        // sincroniza una vez desde config (precios sin IVA) al abrir el catálogo.
        $catalogo = $this->catalog->all();
        if ($this->catalogParecePreciosConIva($catalogo)) {
            try {
                $catalogo = $this->catalog->syncFromConfig();
                session()->flash('success', 'Precios SIN IVA aplicados automáticamente (ej. SERSOP01 = 603.45).');
            } catch (\Throwable) {
                // Si falla, el admin puede usar el botón "Aplicar precios SIN IVA".
            }
        }

        return view('admin.catalogo_sersop', [
            'pageTitle' => 'Catálogo SERSOP - Exacto',
            'nombreTecnico' => htmlspecialchars((string) $nombre, ENT_QUOTES, 'UTF-8'),
            'nav_admin_activo' => 'catalogo_sersop',
            'catalogo' => $catalogo,
            'condicionesPdf' => $this->pdfCondiciones->get(),
            'canEditPdfCondiciones' => $canEditPdfCondiciones,
        ]);
    }

    /**
     * @param  array<int, array{clave?:string, precio?:float}>  $catalogo
     */
    private function catalogParecePreciosConIva(array $catalogo): bool
    {
        foreach ($catalogo as $row) {
            $clave = mb_strtoupper(trim((string) ($row['clave'] ?? '')), 'UTF-8');
            $precio = round((float) ($row['precio'] ?? 0), 2);
            // Precio histórico con IVA de SERSOP01 (700). El sin IVA es 603.45.
            if ($clave === 'SERSOP01' && abs($precio - 700.0) < 0.05) {
                return true;
            }
            if ($clave === 'SERSOP02' && abs($precio - 1000.0) < 0.05) {
                return true;
            }
        }

        return false;
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rows = $request->input('servicios', []);
        if (! is_array($rows)) {
            return back()->with('error', 'Formato inválido del catálogo.');
        }

        if ($user instanceof User && $this->policy->userIsAdmin($user) && $request->has('condiciones_pdf')) {
            $request->validate([
                'condiciones_pdf' => ['nullable', 'string', 'max:32000'],
            ]);
        }

        $claves = [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clave = mb_strtoupper(preg_replace('/\s+/', '', trim((string) ($row['clave'] ?? ''))) ?? '', 'UTF-8');
            $desc = trim((string) ($row['descripcion'] ?? ''));
            $precioRaw = str_replace(',', '.', trim((string) ($row['precio'] ?? '0')));
            $editable = isset($row['editable']);
            $activo = isset($row['activo']);

            if ($clave === '' && $desc === '' && $precioRaw === '') {
                continue;
            }
            if ($clave === '' || ! preg_match('/^[A-Z0-9_]{3,20}$/', $clave)) {
                return back()->withInput()->with('error', 'Cada clave debe usar letras/números/guion bajo (3 a 20 caracteres).');
            }
            if (isset($claves[$clave])) {
                return back()->withInput()->with('error', "La clave {$clave} está repetida.");
            }
            if ($desc === '') {
                return back()->withInput()->with('error', "La clave {$clave} necesita descripción.");
            }
            if (! is_numeric($precioRaw) || (float) $precioRaw < 0) {
                return back()->withInput()->with('error', "La clave {$clave} tiene un PRECIO SIN IVA inválido.");
            }
            $claves[$clave] = true;
            $out[] = [
                'clave' => $clave,
                'descripcion' => $desc,
                'precio' => round((float) $precioRaw, 2),
                'editable' => $editable,
                'activo' => $activo,
            ];
        }

        if (empty($out)) {
            return back()->withInput()->with('error', 'Agrega al menos una clave al catálogo.');
        }

        DB::transaction(function () use ($out, $request, $user): void {
            $this->catalog->replaceAll($out);
            if ($user instanceof User && $this->policy->userIsAdmin($user) && $request->has('condiciones_pdf')) {
                $this->pdfCondiciones->save((string) $request->input('condiciones_pdf', ''));
            }
        });

        return redirect()->route('admin.catalogo.index')->with('success', 'Catálogo SERSOP actualizado (precios sin IVA).');
    }

    public function syncPreciosSinIva(): RedirectResponse
    {
        $guest = (bool) config('exacto.catalogo_sersop_guest');
        $user = Auth::user();
        if (! $guest) {
            abort_unless($user instanceof User && $this->policy->userIsAdmin($user), 403);
        }

        try {
            $rows = $this->catalog->syncFromConfig();
        } catch (\Throwable $e) {
            return redirect()->route('admin.catalogo.index')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.catalogo.index')
            ->with('success', 'Precios SIN IVA aplicados desde config ('.count($rows).' servicios). Ej. SERSOP01 = 603.45');
    }
}
