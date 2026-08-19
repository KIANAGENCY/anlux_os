<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\ExactoVaultService;
use App\Services\OrdenPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OrdenPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sealed_session_name_does_not_bypass_order_access_controls(): void
    {
        $user = User::factory()->create([
            'nombre_tecnico' => 'Tecnico Dos',
            'perfil' => 'tecnico',
        ]);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 100,
            'folio' => 'OS-2026-100',
            'nombre_cliente' => 'Cliente Uno',
            'tecnico_recibido' => 'Tecnico Uno',
            'estatus' => 'Recepción',
            'fecha_entrada' => now(),
        ]);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 101,
            'folio' => 'OS-2026-101',
            'nombre_cliente' => 'Cliente Dos',
            'tecnico_recibido' => 'Tecnico Dos',
            'estatus' => 'Recepción',
            'fecha_entrada' => now(),
        ]);

        $vault = app(ExactoVaultService::class);
        session()->put('nombre_tecnico', $vault->tecnicoNombreSeal('Tecnico Dos'));

        $policy = app(OrdenPolicyService::class);

        $this->assertFalse($policy->userCanAccessOrder($user, 100));
        $this->assertTrue($policy->userCanAccessOrder($user, 101));
    }
}
