<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class AdminUsersController extends Controller
{
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

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $cols = ['id_tecnico', 'nombre_tecnico', 'correo', 'perfil'];
        if (Schema::hasColumn('login', 'nombre_usuario')) {
            $cols[] = 'nombre_usuario';
        }
        if (Schema::hasColumn('login', 'activo')) {
            $cols[] = 'activo';
        }

        return view('admin.users', [
            'pageTitle' => 'Tabla de usuarios - Anlux',
            'nombreTecnico' => htmlspecialchars((string) (session('nombre_tecnico') ?? $user->nombre_tecnico ?? ''), ENT_QUOTES, 'UTF-8'),
            'nav_admin_activo' => 'usuarios',
            'tieneNombreUsuario' => Schema::hasColumn('login', 'nombre_usuario'),
            'usuarios' => User::query()
                ->select($cols)
                ->orderBy('id_tecnico')
                ->get(),
        ]);
    }

    public function toggleActivo(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (! Schema::hasColumn('login', 'activo')) {
            return response()->json(['success' => false, 'message' => 'Migración pendiente: columna activo.'], 503);
        }

        $registro = User::query()->find($id);
        if (! $registro) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        if ((int) $registro->id_tecnico === (int) $user->id_tecnico) {
            return response()->json(['success' => false, 'message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }

        $activo = $request->boolean('activo');
        $registro->activo = $activo;
        $registro->save();

        return response()->json([
            'success' => true,
            'activo' => (bool) $registro->activo,
            'message' => $activo ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);
    }

    public function updatePassword(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $registro = User::query()->find($id);
        if (! $registro) {
            return redirect()->route('admin.users.index')->with('error', 'Usuario no encontrado.');
        }

        $nombreUsuario = $this->normalizarNombreUsuario((string) $request->input('nombre_usuario', ''));
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('confirm_password', '');
        $cambioPassword = $password !== '' || $confirm !== '';
        $tieneColUsuario = Schema::hasColumn('login', 'nombre_usuario');

        if ($tieneColUsuario) {
            if ($nombreUsuario === '') {
                return redirect()->route('admin.users.index')->with('error', 'El nombre de usuario es obligatorio.');
            }
            if (! $this->nombreUsuarioValido($nombreUsuario)) {
                return redirect()->route('admin.users.index')->with('error', 'Nombre de usuario: usa 3 a 64 caracteres (letras, números, punto, guion o guion bajo), sin espacios.');
            }
            $existe = User::query()
                ->where('nombre_usuario', $nombreUsuario)
                ->where('id_tecnico', '!=', $id)
                ->exists();
            if ($existe) {
                return redirect()->route('admin.users.index')->with('error', 'Ese nombre de usuario ya está en uso.');
            }
            $registro->nombre_usuario = $nombreUsuario;
        }

        if ($cambioPassword) {
            if ($password === '' || $confirm === '') {
                return redirect()->route('admin.users.index')->with('error', 'Captura y confirma la nueva contraseña.');
            }
            if ($password !== $confirm) {
                return redirect()->route('admin.users.index')->with('error', 'Las contraseñas no coinciden.');
            }
            if (! $this->passwordPolicy($password)) {
                return redirect()->route('admin.users.index')->with('error', 'La contraseña debe tener al menos 8 caracteres, incluir letras y al menos un número o un carácter especial.');
            }
            $registro->contrasena = Hash::make($password);
        }

        if (! $tieneColUsuario && ! $cambioPassword) {
            return redirect()->route('admin.users.index')->with('error', 'No hay cambios que guardar.');
        }

        $registro->save();

        $msg = $cambioPassword && $tieneColUsuario
            ? 'Usuario y contraseña actualizados correctamente.'
            : ($cambioPassword ? 'Contraseña actualizada correctamente.' : 'Nombre de usuario actualizado correctamente.');

        return redirect()->route('admin.users.index')->with('success', $msg);
    }

    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $registro = User::query()->find($id);
        if (! $registro) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
            }

            return redirect()->route('admin.users.index')->with('error', 'Usuario no encontrado.');
        }

        if ((int) $registro->id_tecnico === (int) $user->id_tecnico) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.'], 422);
            }

            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $adminsRestantes = User::query()
            ->where('perfil', 'administrador')
            ->where('id_tecnico', '!=', $id)
            ->count();
        if (mb_strtolower((string) $registro->perfil, 'UTF-8') === 'administrador' && $adminsRestantes < 1) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No se puede eliminar el último administrador.'], 422);
            }

            return redirect()->route('admin.users.index')->with('error', 'No se puede eliminar el último administrador.');
        }

        DB::transaction(function () use ($id, $registro): void {
            if (Schema::hasTable('login_remember_tokens')) {
                DB::table('login_remember_tokens')->where('id_tecnico', $id)->delete();
            }
            if (Schema::hasTable('impersonation_requests')) {
                DB::table('impersonation_requests')
                    ->where(function ($query) use ($id): void {
                        $query->where('admin_id', $id)->orWhere('target_id', $id);
                    })
                    ->delete();
            }
            $registro->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cuenta eliminada correctamente.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Cuenta eliminada correctamente.');
    }
}
