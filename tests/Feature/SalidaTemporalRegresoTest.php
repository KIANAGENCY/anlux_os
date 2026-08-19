<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegistrarOrdenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SalidaTemporalRegresoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('orden_servicio_c', 'salida_temporal_activa')) {
            $this->markTestSkipped('Faltan columnas de salida temporal en el schema de pruebas.');
        }
    }

    public function test_regreso_temporal_limpia_flag_y_guarda_fecha(): void
    {
        $user = User::factory()->administrador()->create([
            'nombre_tecnico' => 'Administrador',
        ]);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 501,
            'folio' => 'OS-REGRESO-501',
            'nombre_cliente' => 'Cliente Regreso',
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'En proceso',
            'fecha_entrada' => now()->subDay(),
            'salida_temporal_activa' => 1,
            'fecha_salida_temporal' => now()->subHours(2),
            'fecha_regreso_temporal' => null,
            'motivo_salida_temporal' => 'Cliente retira equipo por piezas',
        ]);

        $response = $this->actingAs($user)->postJson('/api/ordenes/501/regreso-temporal', [
            '_token' => csrf_token(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['fecha_regreso_temporal']);

        $row = DB::table('orden_servicio_c')->where('id_orden_c', 501)->first();
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row->salida_temporal_activa);
        $this->assertNotNull($row->fecha_regreso_temporal);
        $this->assertSame('En proceso', (string) $row->estatus);
    }

    public function test_regreso_falla_si_no_hay_salida_activa(): void
    {
        $user = User::factory()->administrador()->create();

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 502,
            'folio' => 'OS-REGRESO-502',
            'nombre_cliente' => 'Cliente Sin Salida',
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'En proceso',
            'fecha_entrada' => now(),
            'salida_temporal_activa' => 0,
            'fecha_salida_temporal' => null,
            'fecha_regreso_temporal' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/ordenes/502/regreso-temporal');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_servicio_regreso_deja_mensaje_operable_en_proceso(): void
    {
        $user = User::factory()->administrador()->create([
            'nombre_tecnico' => 'Administrador',
        ]);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 503,
            'folio' => 'OS-REGRESO-503',
            'nombre_cliente' => 'Cliente Servicio',
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'En proceso',
            'fecha_entrada' => now(),
            'salida_temporal_activa' => 1,
            'fecha_salida_temporal' => now()->subHour(),
            'motivo_salida_temporal' => 'Motivo prueba',
        ]);

        $result = app(RegistrarOrdenService::class)->registrarRegresoTemporal($user, 503);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('En proceso', (string) $result['message']);
        $this->assertFalse(app(RegistrarOrdenService::class)->isSalidaTemporalActiva(503));
    }

    public function test_puede_ofrecer_salida_otra_vez_tras_regreso_con_estatus_sin_espacio(): void
    {
        $user = User::factory()->administrador()->create([
            'nombre_tecnico' => 'Administrador',
        ]);

        // Legado/BD: a veces "Enproceso" sin espacio; el aviso debe seguir ofreciéndose.
        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 504,
            'folio' => 'OS-REGRESO-504',
            'nombre_cliente' => 'Cliente Segunda Salida',
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'Enproceso',
            'fecha_entrada' => now(),
            'salida_temporal_activa' => 0,
            'fecha_salida_temporal' => now()->subDay(),
            'fecha_regreso_temporal' => now()->subHours(3),
            'motivo_salida_temporal' => 'Salida anterior ya regresada',
        ]);

        $this->assertTrue(\App\Support\OrderStatus::isEnProceso('Enproceso'));
        $this->assertSame('En proceso', \App\Support\OrderStatus::map('Enproceso'));

        $svc = app(RegistrarOrdenService::class);
        $ask = new \ReflectionMethod($svc, 'askSalidaTemporalFlag');
        $ask->setAccessible(true);
        $this->assertTrue($ask->invoke($svc, 504, 'Enproceso'));
        $this->assertTrue($ask->invoke($svc, 504, 'En proceso'));
        $this->assertFalse($svc->isSalidaTemporalActiva(504));
    }
}
