<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\FolioSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FolioSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('orden_folio_sequence') || ! Schema::hasTable('orden_servicio_c')) {
            $this->markTestSkipped('Faltan tablas de folio/órdenes en el schema de pruebas.');
        }
    }

    public function test_reservar_reutiliza_menor_hueco(): void
    {
        $anio = 2026;
        DB::table('orden_folio_sequence')->insert([
            'anio' => $anio,
            'next_num' => 5,
        ]);
        DB::table('orden_servicio_c')->insert([
            [
                'id_orden_c' => 601,
                'folio' => 'OS-2026-001',
                'nombre_cliente' => 'A',
                'estatus' => 'En proceso',
                'fecha_entrada' => now(),
            ],
            [
                'id_orden_c' => 602,
                'folio' => 'OS-2026-004',
                'nombre_cliente' => 'B',
                'estatus' => 'En proceso',
                'fecha_entrada' => now(),
            ],
        ]);

        $svc = app(FolioSequenceService::class);
        $this->assertSame('OS-2026-002', $svc->peekNextFolio($anio));

        $r1 = DB::transaction(fn () => $svc->reservarFolio($anio));
        $this->assertSame('OS-2026-002', $r1['folio']);
        $this->assertTrue($r1['reutilizado']);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 603,
            'folio' => $r1['folio'],
            'nombre_cliente' => 'C',
            'estatus' => 'En proceso',
            'fecha_entrada' => now(),
        ]);

        $r2 = DB::transaction(fn () => $svc->reservarFolio($anio));
        $this->assertSame('OS-2026-003', $r2['folio']);
        $this->assertTrue($r2['reutilizado']);

        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 604,
            'folio' => $r2['folio'],
            'nombre_cliente' => 'D',
            'estatus' => 'En proceso',
            'fecha_entrada' => now(),
        ]);

        $r3 = DB::transaction(fn () => $svc->reservarFolio($anio));
        $this->assertSame('OS-2026-005', $r3['folio']);
        $this->assertFalse($r3['reutilizado']);
        $this->assertSame(6, (int) DB::table('orden_folio_sequence')->where('anio', $anio)->value('next_num'));
    }

    public function test_sin_huecos_sigue_next_num(): void
    {
        $anio = 2026;
        DB::table('orden_folio_sequence')->insert([
            'anio' => $anio,
            'next_num' => 3,
        ]);
        DB::table('orden_servicio_c')->insert([
            [
                'id_orden_c' => 701,
                'folio' => 'OS-2026-001',
                'nombre_cliente' => 'A',
                'estatus' => 'Enproceso',
                'fecha_entrada' => now(),
            ],
            [
                'id_orden_c' => 702,
                'folio' => 'OS-2026-002',
                'nombre_cliente' => 'B',
                'estatus' => 'Enproceso',
                'fecha_entrada' => now(),
            ],
        ]);

        $svc = app(FolioSequenceService::class);
        $r = DB::transaction(fn () => $svc->reservarFolio($anio));
        $this->assertSame('OS-2026-003', $r['folio']);
        $this->assertFalse($r['reutilizado']);
        $this->assertSame(4, (int) DB::table('orden_folio_sequence')->where('anio', $anio)->value('next_num'));
    }

    public function test_list_gaps_y_sync(): void
    {
        $anio = 2026;
        DB::table('orden_folio_sequence')->insert([
            'anio' => $anio,
            'next_num' => 10,
        ]);
        DB::table('orden_servicio_c')->insert([
            [
                'id_orden_c' => 801,
                'folio' => 'OS-2026-001',
                'nombre_cliente' => 'A',
                'estatus' => 'En proceso',
                'fecha_entrada' => now(),
            ],
            [
                'id_orden_c' => 802,
                'folio' => 'OS-2026-004',
                'nombre_cliente' => 'B',
                'estatus' => 'En proceso',
                'fecha_entrada' => now(),
            ],
        ]);

        $svc = app(FolioSequenceService::class);
        $gaps = $svc->listGaps($anio);
        $this->assertContains('OS-2026-002', $gaps);
        $this->assertContains('OS-2026-003', $gaps);

        $sync = $svc->syncNextNum($anio);
        $this->assertTrue($sync['success']);
        $this->assertSame(5, (int) $sync['next_num']);
        $this->assertSame(5, (int) DB::table('orden_folio_sequence')->where('anio', $anio)->value('next_num'));
    }
}
