<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\RegistrarOrdenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tests\TestCase;

final class EquipoTerminadoNoWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_skips_terminado_when_entrega_por_equipo(): void
    {
        Bus::fake();
        Http::fake();
        config([
            'exacto.whatsapp_notifications_enabled' => true,
            'services.whatsapp.enabled' => true,
        ]);

        $service = app(RegistrarOrdenService::class);
        $method = (new ReflectionClass($service))->getMethod('dispatchStatusNotifications');
        $method->setAccessible(true);

        $result = $method->invoke(
            $service,
            910,
            'Terminado',
            'OS-2026-910',
            'CLIENTE PRUEBA',
            '5512345678',
            'cliente@example.com',
            'LA PAZ',
            0.0,
            [['marca' => 'HP', 'modelo' => 'PAVILION', 'serie' => 'SER-910']],
            true,
            true,
            ['entrega_por_equipo' => 1, 'equipo_indice' => 1]
        );

        $this->assertNull($result['whatsapp']['message']);
        $this->assertFalse($result['whatsapp_applicable']);
        Bus::assertNothingDispatched();
    }

    public function test_dispatch_queues_terminado_for_normal_order(): void
    {
        Bus::fake();
        Http::fake();
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

        $service = app(RegistrarOrdenService::class);
        $method = (new ReflectionClass($service))->getMethod('dispatchStatusNotifications');
        $method->setAccessible(true);

        $result = $method->invoke(
            $service,
            911,
            'Terminado',
            'OS-2026-911',
            'CLIENTE PRUEBA',
            '5512345678',
            'cliente@example.com',
            'LA PAZ',
            0.0,
            [['marca' => 'HP', 'modelo' => 'PAVILION', 'serie' => 'SER-911']],
            true,
            true,
            []
        );

        $this->assertDatabaseHas('order_whatsapp_notifications', [
            'id_orden_c' => 911,
            'estatus' => 'Terminado',
            'template_name' => 'orden_terminado',
        ]);
        $this->assertNotNull($result['whatsapp_notification_id']);
    }
}
