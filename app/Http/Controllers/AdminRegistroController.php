<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ExactoVaultService;
use App\Services\LoginIdSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminRegistroController extends Controller
{
    public function __construct(
        private readonly ExactoVaultService $vault,
        private readonly LoginIdSequenceService $loginIdSequence
    ) {}

    private function passwordPolicy(string $password): bool
    {
        if (mb_strlen($password, 'UTF-8') < 8) {
            return false;
        }
        $hasLetter = preg_match('/\p{L}/u', $password) === 1 || preg_match('/[a-zA-Z]/', $password) === 1;
        $hasDigit = preg_match('/[0-9]/', $password) === 1;
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password) === 1;

        return $hasLetter && ($hasDigit || $hasSpecial);
    }

    private function normalizarNombreUsuario(string $valor): string
    {
        return mb_strtolower(trim($valor), 'UTF-8');
    }

    private function nombreUsuarioValido(string $valor): bool
    {
        return (bool) preg_match('/^[a-z0-9._\-]{3,64}$/', $valor);
    }

    public function create(): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        return view('admin.registro', [
            'pageTitle' => 'Registro - Exacto',
            'nombreTecnico' => htmlspecialchars((string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''), ENT_QUOTES, 'UTF-8'),
            'nav_admin_activo' => 'registro',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $nombre = trim((string) $request->input('nombre', ''));
        $nombreUsuario = $this->normalizarNombreUsuario((string) $request->input('nombre_usuario', ''));
        $correo = trim((string) $request->input('email', ''));
        $contrasena = (string) $request->input('password', '');
        $confirm = (string) $request->input('confirm_password', '');
        $celularDigits = $this->vault->normalizeDigits((string) $request->input('celular', ''));
        $perfil = trim((string) $request->input('perfil', ''));

        if ($nombre === '' || $nombreUsuario === '' || $correo === '' || $contrasena === '' || $confirm === '' || $celularDigits === '' || $perfil === '') {
            return back()->withInput()->with('error', 'Completa todos los campos.');
        }
        if (! $this->nombreUsuarioValido($nombreUsuario)) {
            return back()->withInput()->with('error', 'Nombre de usuario: usa 3 a 64 caracteres (letras, números, punto, guion o guion bajo), sin espacios.');
        }
        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->with('error', 'Ingresa un email válido.');
        }
        if ($contrasena !== $confirm) {
            return back()->withInput()->with('error', 'Las contraseñas no coinciden.');
        }
        if (! $this->passwordPolicy($contrasena)) {
            return back()->withInput()->with('error', 'La contraseña debe tener al menos 8 caracteres, incluir letras y al menos un número o un carácter especial.');
        }
        if (! preg_match('/^\d{7,15}$/', $celularDigits)) {
            return back()->withInput()->with('error', 'Ingresa un número de celular válido (solo dígitos, de 7 a 15).');
        }

        $nombreToken = $this->vault->tecnicoNombreToken($nombre);
        $nombreSeal = $this->vault->tecnicoNombreSeal($nombre);
        $celularStore = $this->vault->loginCelularHashStore($celularDigits);
        $existsQuery = User::query()
            ->where('correo', $correo)
            ->orWhere('nombre_tecnico_token', $nombreToken)
            ->orWhere('nombre_tecnico', $nombre)
            ->orWhere('celular', $celularStore);
        if (Schema::hasColumn('login', 'nombre_usuario')) {
            $existsQuery->orWhere('nombre_usuario', $nombreUsuario);
        }
        if ($existsQuery->exists()) {
            return back()->withInput()->with('error', 'El email, el nombre de usuario, el nombre del técnico o ese número de celular ya están registrados.');
        }

        DB::transaction(function () use ($nombreSeal, $nombreToken, $nombreUsuario, $correo, $contrasena, $celularStore, $perfil): void {
            $idTecnico = $this->loginIdSequence->reserveNextId();
            if (Schema::hasColumn('login', 'nombre_usuario')) {
                DB::insert(
                    'INSERT INTO login (id_tecnico, nombre_tecnico, nombre_tecnico_token, nombre_usuario, correo, contrasena, celular, perfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$idTecnico, $nombreSeal, $nombreToken, $nombreUsuario, $correo, Hash::make($contrasena), $celularStore, $perfil]
                );
            } else {
                DB::insert(
                    'INSERT INTO login (id_tecnico, nombre_tecnico, nombre_tecnico_token, correo, contrasena, celular, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$idTecnico, $nombreSeal, $nombreToken, $correo, Hash::make($contrasena), $celularStore, $perfil]
                );
            }
        });

        return redirect()->route('admin.registro.create')->with('success', 'Registro exitoso. Usuario creado.');
    }
}
