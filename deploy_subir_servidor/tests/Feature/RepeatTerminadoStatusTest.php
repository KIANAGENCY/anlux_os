<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\OrdenStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RepeatTerminadoStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_confirming_terminado_again_replaces_date_but_does_not_send_whatsapp(): void
    {
        Bus::fake();
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.SHOULD-NOT-SEND']],
            ]),
        ]);
        config([
            'exacto.whatsapp_notifications_enabled' => true,
            'services.whatsapp.enabled' => true,
            'services.whatsapp.base_url' => 'https://graph.facebook.com',
            'services.whatsapp.graph_version' => 'v20.0',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.default_country_code' => '52',
            'services.whatsapp.templates.terminado' => 'orden_terminado',
            'services.whatsapp.template_include_document' => false,
        ]);

        $user = User::factory()->administrador()->create();
        DB::table('orden_servicio_c')->insert([
            'id_orden_c' => 600,
            'folio' => 'OS-2026-600',
            'nombre_cliente' => 'Cliente Prueba',
            'telefono' => '5512345678',
            'fecha_entrada' => '2026-08-01 09:00:00',
            'fecha_terminada' => '2026-08-08 10:15:00',
            'estatus' => 'Terminado',
        ]);
        DB::table('order_whatsapp_notifications')->insert([
            'id_orden_c' => 600,
            'folio' => 'OS-2026-600',
            'estatus' => 'Terminado',
            'telefono' => '5215512345678',
            'template_name' => 'orden_terminado',
            'provider_message_id' => 'wamid.FIRST-TERMINADO',
            'status' => 'accepted',
            'sent_at' => '2026-08-08 10:15:00',
            'created_at' => '2026-08-08 10:15:00',
            'updated_at' => '2026-08-08 10:15:00',
        ]);

        Carbon::setTestNow('2026-08-12 16:45:30');

        $result = app(OrdenStatusService::class)->updateStatus($user, 600, 'amarillo');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('orden_servicio_c', [
            'id_orden_c' => 600,
            'estatus' => 'Terminado',
            'fecha_terminada' => '2026-08-12 16:45:30',
        ]);
        // Terminado es uso interno: no se reenvía plantilla WhatsApp.
        $this->assertDatabaseCount('order_whatsapp_notifications', 1);
        Bus::assertNothingDispatched();
    }
}
