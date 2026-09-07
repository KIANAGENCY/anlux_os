<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class HistorialOrdenesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_history_and_load_orders(): void
    {
        $user = User::factory()->administrador()->create(['nombre_tecnico' => 'Administrador']);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 801,
            'folio' => 'OS-2026-801',
            'nombre_cliente' => 'Cliente historial',
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'En proceso',
            'fecha_entrada' => now(),
        ]);

        $this->actingAs($user)->get(route('historial.index'))->assertOk();

        $this->actingAs($user)
            ->getJson(route('ordenes.list', ['perPage' => 50, 'page' => 1, 'sort' => 'fecha']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id_orden_c', 801);
    }
}
