<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\OrderPdfController;
use App\Models\User;
use App\Services\EquipoEntregaResolver;
use App\Services\ExactoVaultService;
use App\Services\RegistrarOrdenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

final class EquipoEntregaReceptorTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_supports_a_different_receiver_for_each_equipment(): void
    {
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_receptor_tipo'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_recibido_cliente'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_firma_cliente'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_firma_tecnico'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_tecnico'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_fecha'));
    }

    public function test_reinserting_equipment_preserves_each_receiver_by_index(): void
    {
        $service = app(RegistrarOrdenService::class);
        $method = new ReflectionMethod($service, 'equipoInsertRows');

        $equipos = [
            [
                'marca' => 'LENOVO',
                'modelo' => 'IDEAPAD 5',
                'serie' => 'SERIE-1',
                'descripcionFalla' => 'FALLA 1',
                'tipoServicio' => 'REVISION',
            ],
            [
                'marca' => 'DELL',
                'modelo' => 'INSPIRON',
                'serie' => 'SERIE-2',
                'descripcionFalla' => 'FALLA 2',
                'tipoServicio' => 'RESPALDO',
            ],
        ];

        $rows = $method->invoke($service, 20, $equipos, [2, 1], [
            [
                'tipo' => 'tercero',
                'nombre' => 'CIFRADO-GEPETO',
                'firma_cliente' => 'FIRMA-CLIENTE-1',
                'firma_tecnico' => 'FIRMA-TECNICO-1',
                'tecnico' => 'TECNICO-1',
                'fecha' => '2026-08-25 10:00:00',
            ],
            [
                'tipo' => 'cliente',
                'nombre' => 'CIFRADO-PINOCHO',
                'firma_cliente' => 'FIRMA-CLIENTE-2',
                'firma_tecnico' => 'FIRMA-TECNICO-2',
                'tecnico' => 'TECNICO-2',
                'fecha' => '2026-08-25 11:00:00',
            ],
        ]);

        $this->assertSame('tercero', $rows[0][8]);
        $this->assertSame('CIFRADO-GEPETO', $rows[0][9]);
        $this->assertSame('cliente', $rows[1][8]);
        $this->assertSame('CIFRADO-PINOCHO', $rows[1][9]);
        $this->assertSame('FIRMA-CLIENTE-1', $rows[0][10]);
        $this->assertSame('FIRMA-TECNICO-1', $rows[0][11]);
        $this->assertSame('TECNICO-1', $rows[0][12]);
        $this->assertSame('2026-08-25 10:00:00', $rows[0][13]);
        $this->assertSame('FIRMA-CLIENTE-2', $rows[1][10]);
    }

    public function test_pdf_equipment_line_is_only_for_third_party_and_uses_make_and_model(): void
    {
        $controller = app(OrderPdfController::class);
        $method = new ReflectionMethod($controller, 'equipoTerceroFirmaLine');
        $equipo = (object) [
            'marca' => 'LENOVO',
            'modelo' => 'IDEAPAD 5 15ARE05',
            'serie' => 'NO-DEBE-SALIR',
        ];

        $tercero = (string) $method->invoke($controller, $equipo, 'tercero');
        $titular = (string) $method->invoke($controller, $equipo, 'cliente');

        $this->assertStringContainsString('SE ENTREGÓ EL: LENOVO IDEAPAD 5 15ARE05', $tercero);
        $this->assertStringNotContainsString('NO-DEBE-SALIR', $tercero);
        $this->assertSame('', $titular);
    }

    public function test_legacy_receiver_different_from_customer_is_treated_as_third_party(): void
    {
        $controller = app(OrderPdfController::class);
        $method = new ReflectionMethod($controller, 'receptorEsTercero');

        $this->assertTrue($method->invoke($controller, '', 'LUIS ENRIQUE', 'PINOCHO PINOCHET'));
        $this->assertFalse($method->invoke($controller, '', 'PINOCHO PINOCHET', 'PINOCHO PINOCHET'));
        $this->assertFalse($method->invoke($controller, 'cliente', 'LUIS ENRIQUE', 'PINOCHO PINOCHET'));
        $this->assertTrue($method->invoke($controller, 'tercero', 'PINOCHO PINOCHET', 'PINOCHO PINOCHET'));
    }

    public function test_delivered_equipment_endpoint_returns_only_delivered_items_without_signature_paths(): void
    {
        $user = User::factory()->administrador()->create();
        $vault = app(ExactoVaultService::class);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 850,
            'folio' => 'OS-EQUIPOS-850',
            'nombre_cliente' => $vault->nombreClienteSeal('PINOCHO PINOCHET'),
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'Terminado',
            'fecha_entrada' => now()->subDay(),
        ]);
        DB::table('equipos_orden')->insert([
            [
                'id_orden_c' => 850,
                'marca' => 'HP',
                'modelo' => 'PAVILION 15',
                'serie' => 'HP-001',
                'acciones' => 2,
                'entrega_receptor_tipo' => 'tercero',
                'entrega_recibido_cliente' => $vault->nombreClienteSeal('LUIS ENRIQUE'),
                'entrega_firma_cliente' => $vault->firmaRutaSeal('firmas/cliente-1.png'),
                'entrega_firma_tecnico' => $vault->firmaRutaSeal('firmas/tecnico-1.png'),
                'entrega_tecnico' => $vault->tecnicoNombreSeal('ANA TECNICA'),
                'entrega_fecha' => '2026-08-25 10:30:00',
            ],
            [
                'id_orden_c' => 850,
                'marca' => 'DELL',
                'modelo' => 'LATITUDE',
                'serie' => 'DELL-002',
                'acciones' => 1,
                'entrega_receptor_tipo' => null,
                'entrega_recibido_cliente' => null,
                'entrega_firma_cliente' => null,
                'entrega_firma_tecnico' => null,
                'entrega_tecnico' => null,
                'entrega_fecha' => null,
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/ordenes/850/equipos-entregados');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.indice', 1)
            ->assertJsonPath('data.0.receptor', 'LUIS ENRIQUE')
            ->assertJsonPath('data.0.receptor_tipo', 'tercero')
            ->assertJsonPath('data.0.tecnico', 'ANA TECNICA')
            ->assertJsonMissingPath('data.0.entrega_firma_cliente')
            ->assertJsonMissingPath('data.0.entrega_firma_tecnico');
        $this->assertStringContainsString('/pdf/orden/850', (string) $response->json('data.0.pdf_url'));
        $this->assertStringContainsString('eq=1', (string) $response->json('data.0.pdf_url'));
    }

    public function test_delivered_equipment_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/ordenes/850/equipos-entregados')->assertUnauthorized();
    }

    public function test_legacy_equipment_delivery_uses_its_whatsapp_event_for_receiver_and_date(): void
    {
        $user = User::factory()->administrador()->create();
        $vault = app(ExactoVaultService::class);
        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 851,
            'folio' => 'OS-LEGACY-851',
            'nombre_cliente' => $vault->nombreClienteSeal('CLIENTE TITULAR'),
            'tecnico_recibido' => 'Administrador',
            'estatus' => 'En proceso',
            'fecha_entrada' => '2026-08-20 08:00:00',
        ]);
        DB::table('orden_servicio_t')->insert([
            'id_orden_c' => 851,
            'recibido_cliente' => $vault->nombreClienteSeal('RECEPTOR GLOBAL ULTIMO'),
            'tecnico_recibido' => 'Administrador',
            'entregado_por_tecnico' => 'Administrador',
        ]);
        DB::table('equipos_orden')->insert([
            [
                'id_orden_c' => 851, 'marca' => 'HP', 'modelo' => 'UNO', 'serie' => 'SER-UNO',
                'acciones' => 2, 'entrega_receptor_tipo' => null, 'entrega_recibido_cliente' => null,
                'entrega_fecha' => null,
            ],
            [
                'id_orden_c' => 851, 'marca' => 'DELL', 'modelo' => 'DOS', 'serie' => 'SER-DOS',
                'acciones' => 2, 'entrega_receptor_tipo' => null, 'entrega_recibido_cliente' => null,
                'entrega_fecha' => null,
            ],
        ]);
        foreach ([
            [1, 'RECEPTOR PRIMERO', '2026-08-21 09:15:00'],
            [2, 'RECEPTOR SEGUNDO', '2026-08-22 10:30:00'],
        ] as [$indice, $receptor, $fecha]) {
            DB::table('order_whatsapp_notifications')->insert([
                'id_orden_c' => 851,
                'folio' => 'OS-LEGACY-851',
                'estatus' => 'Entregado',
                'status' => 'sent',
                'payload_json' => json_encode([
                    'equipo_indice' => $indice,
                    'recibido_cliente' => $receptor,
                ], JSON_UNESCAPED_UNICODE),
                'queued_at' => $fecha,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/ordenes/851/equipos-entregados');

        $response->assertOk()
            ->assertJsonPath('data.0.receptor', 'RECEPTOR PRIMERO')
            ->assertJsonPath('data.0.fecha_entrega', '2026-08-21 09:15:00')
            ->assertJsonPath('data.1.receptor', 'RECEPTOR SEGUNDO')
            ->assertJsonPath('data.1.fecha_entrega', '2026-08-22 10:30:00');
    }

    public function test_shared_resolver_keeps_each_receiver_and_summarizes_multiple_names(): void
    {
        $vault = app(ExactoVaultService::class);
        $resolver = app(EquipoEntregaResolver::class);
        $equipos = [
            (object) [
                'acciones' => 2,
                'marca' => 'TOSHIBA',
                'modelo' => '3450 SUPER',
                'entrega_receptor_tipo' => 'tercero',
                'entrega_recibido_cliente' => $vault->nombreClienteSeal('PEDRO LOPEZ'),
                'entrega_fecha' => '2026-08-25 12:43:55',
            ],
            (object) [
                'acciones' => 2,
                'marca' => 'DELL',
                'modelo' => 'INSPIRON 3535',
                'entrega_receptor_tipo' => 'cliente',
                'entrega_recibido_cliente' => $vault->nombreClienteSeal('PINOCHO PINOCHET'),
                'entrega_fecha' => '2026-08-25 11:06:48',
            ],
        ];

        $entregas = $resolver->resolveAll(0, $equipos, [
            'nombre_cliente' => $vault->nombreClienteSeal('PINOCHO PINOCHET'),
            'recibido_cliente' => $vault->nombreClienteSeal('PEDRO LOPEZ'),
        ]);

        $this->assertSame('PEDRO LOPEZ', $entregas[1]['receptor']);
        $this->assertSame('tercero', $entregas[1]['receptor_tipo']);
        $this->assertSame('PINOCHO PINOCHET', $entregas[2]['receptor']);
        $this->assertSame('cliente', $entregas[2]['receptor_tipo']);
        $this->assertSame('VARIOS RECEPTORES', $resolver->resumenReceptores($entregas));
    }

    public function test_pending_equipment_does_not_inherit_last_global_receiver(): void
    {
        $vault = app(ExactoVaultService::class);
        $entregas = app(EquipoEntregaResolver::class)->resolveAll(0, [
            (object) [
                'acciones' => 0,
                'marca' => 'HP',
                'modelo' => 'PAVILION',
            ],
        ], [
            'nombre_cliente' => $vault->nombreClienteSeal('CLIENTE TITULAR'),
            'recibido_cliente' => $vault->nombreClienteSeal('ULTIMO RECEPTOR'),
        ]);

        $this->assertSame('', $entregas[1]['receptor']);
        $this->assertNull($entregas[1]['fecha_entrega']);
    }

    public function test_delivered_equipment_does_not_inherit_global_signature(): void
    {
        $vault = app(ExactoVaultService::class);
        $firmaEquipo = $vault->firmaRutaSeal('firmas/equipo-2.png');
        $firmaGlobal = $vault->firmaRutaSeal('firmas/global.png');

        $entregas = app(EquipoEntregaResolver::class)->resolveAll(0, [
            (object) [
                'acciones' => 2,
                'entrega_firma_cliente' => null,
                'entrega_firma_tecnico' => null,
            ],
            (object) [
                'acciones' => 2,
                'entrega_firma_cliente' => $firmaEquipo,
                'entrega_firma_tecnico' => $vault->firmaRutaSeal('firmas/tecnico-2.png'),
            ],
        ], [
            'firma_c_r' => $firmaGlobal,
            'firma_t_e' => $firmaGlobal,
        ]);

        $this->assertNull($entregas[1]['firma_cliente']);
        $this->assertNull($entregas[1]['firma_tecnico']);
        $this->assertSame($firmaEquipo, $entregas[2]['firma_cliente']);
    }

    public function test_equipos_entrega_schema_ensure_creates_missing_signature_columns(): void
    {
        if (Schema::hasColumn('equipos_orden', 'entrega_firma_cliente')) {
            Schema::table('equipos_orden', function ($table): void {
                $table->dropColumn('entrega_firma_cliente');
            });
        }

        $this->assertFalse(Schema::hasColumn('equipos_orden', 'entrega_firma_cliente'));

        \App\Support\EquiposOrdenEntregaSchema::ensure();

        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_firma_cliente'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_firma_tecnico'));
        $this->assertTrue(Schema::hasColumn('equipos_orden', 'entrega_fecha'));
    }
}
