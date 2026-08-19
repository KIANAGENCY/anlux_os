<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\OrderPdfController;
use App\Services\OrderWhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OrderWhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.base_url' => 'https://graph.facebook.com',
            'services.whatsapp.graph_version' => 'v20.0',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.verify_token' => 'verify-me',
            'services.whatsapp.language' => 'es_MX',
            'services.whatsapp.default_country_code' => '52',
            'services.whatsapp.templates.recepcion' => 'orden_recepcion',
            'services.whatsapp.templates.terminado' => 'orden_terminado',
            'services.whatsapp.templates.entregado' => 'orden_entregado',
            'services.whatsapp.template_include_document' => false,
        ]);
    }

    public function test_queue_for_status_with_result_rejects_invalid_phone(): void
    {
        Bus::fake();

        $result = app(OrderWhatsappService::class)->queueForStatusWithResult(7, 'Recepción', [
            'folio' => 'OS-2026-007',
            'telefono' => '0000000000',
            'nombre_cliente' => 'Cliente',
        ]);

        $this->assertSame('rejected', $result['status']);
        $this->assertStringContainsString('no es válido', $result['message']);
        Bus::assertNothingDispatched();
        $this->assertDatabaseCount('order_whatsapp_notifications', 0);
    }

    public function test_queue_for_status_with_result_rejects_missing_phone(): void
    {
        Bus::fake();

        $result = app(OrderWhatsappService::class)->queueForStatusWithResult(7, 'Recepción', [
            'folio' => 'OS-2026-007',
            'telefono' => '',
            'nombre_cliente' => 'Cliente',
        ]);

        $this->assertSame('rejected', $result['status']);
        $this->assertStringContainsString('No se registró un teléfono', $result['message']);
        Bus::assertNothingDispatched();
    }

    public function test_probe_phone_detects_missing_and_valid_numbers(): void
    {
        $service = app(OrderWhatsappService::class);

        $missing = $service->probePhone('');
        $this->assertSame('missing', $missing['status']);

        $valid = $service->probePhone('5512345678');
        $this->assertSame('valid', $valid['status']);
        $this->assertSame('5215512345678', $valid['phone']);
    }

    public function test_queue_for_status_with_result_creates_row_and_schedules_after_response(): void
    {
        Bus::fake();

        $result = app(OrderWhatsappService::class)->queueForStatusWithResult(8, 'Recepción', [
            'folio' => 'OS-2026-008',
            'telefono' => '5512345678',
            'nombre_cliente' => 'Cliente',
        ]);

        $this->assertSame('queued', $result['status']);
        $this->assertNotNull($result['notification_id']);
        $this->assertDatabaseHas('order_whatsapp_notifications', [
            'id_orden_c' => 8,
            'estatus' => 'Recepción',
            'telefono' => '5215512345678',
            'status' => 'queued',
        ]);
    }

    public function test_send_queued_notification_marks_row_as_accepted(): void
    {
        Bus::fake();
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.TEST123'],
                ],
            ], 200),
        ]);

        $result = app(OrderWhatsappService::class)->queueForStatusWithResult(9, 'Terminado', [
            'folio' => 'OS-2026-009',
            'telefono' => '5512345678',
            'nombre_cliente' => 'Cliente',
        ]);

        $notificationId = (int) $result['notification_id'];
        $sendResult = app(OrderWhatsappService::class)->sendQueuedNotification($notificationId);

        $this->assertTrue($sendResult['sent']);
        $this->assertSame('accepted', $sendResult['status']);
        $this->assertDatabaseHas('order_whatsapp_notifications', [
            'id' => $notificationId,
            'status' => 'accepted',
            'provider_message_id' => 'wamid.TEST123',
        ]);
        $this->assertDatabaseHas('wa_messages', [
            'provider_message_id' => 'wamid.TEST123',
            'direction' => 'out',
            'type' => 'template',
        ]);
    }

    public function test_send_queued_notification_uploads_pdf_and_sends_document_header(): void
    {
        Bus::fake();
        config([
            'services.whatsapp.template_include_document' => true,
            'services.whatsapp.document_delivery' => 'upload',
        ]);

        $this->mock(OrderPdfController::class, function ($mock): void {
            $mock->shouldReceive('renderOrderPdfBinary')
                ->once()
                ->with(9, true)
                ->andReturn('%PDF-1.4 test');
        });

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response(['id' => 'MEDIA_PDF_123'], 200),
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.PDF123'],
                ],
            ], 200),
        ]);

        $queued = app(OrderWhatsappService::class)->queueForStatusWithResult(9, 'Terminado', [
            'id_orden_c' => 9,
            'folio' => 'OS-2026-009',
            'telefono' => '5512345678',
            'nombre_cliente' => 'Cliente',
        ]);

        $sendResult = app(OrderWhatsappService::class)->sendQueuedNotification((int) $queued['notification_id']);

        $this->assertTrue($sendResult['sent']);
        $this->assertStringContainsString('PDF', $sendResult['message']);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/messages')) {
                return false;
            }
            $payload = $request->data();
            $components = $payload['template']['components'] ?? [];

            return ($components[0]['type'] ?? '') === 'header'
                && ($components[0]['parameters'][0]['type'] ?? '') === 'document'
                && ($components[0]['parameters'][0]['document']['id'] ?? '') === 'MEDIA_PDF_123';
        });
    }

    public function test_send_queued_notification_falls_back_without_pdf_when_media_upload_fails(): void
    {
        Bus::fake();
        config([
            'services.whatsapp.template_include_document' => true,
            'services.whatsapp.document_delivery' => 'upload',
            'services.whatsapp.fallback_without_document' => true,
        ]);

        $this->mock(OrderPdfController::class, function ($mock): void {
            $mock->shouldReceive('renderOrderPdfBinary')
                ->once()
                ->with(9, true)
                ->andReturn('%PDF-1.4 test');
        });

        Http::fake([
            'https://graph.facebook.com/*/media' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Connection timed out');
            },
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.NO_PDF'],
                ],
            ], 200),
        ]);

        $queued = app(OrderWhatsappService::class)->queueForStatusWithResult(9, 'Terminado', [
            'id_orden_c' => 9,
            'folio' => 'OS-2026-009',
            'telefono' => '5512345678',
            'nombre_cliente' => 'Cliente',
        ]);

        $sendResult = app(OrderWhatsappService::class)->sendQueuedNotification((int) $queued['notification_id']);

        $this->assertTrue($sendResult['sent']);
        $this->assertSame('accepted', $sendResult['status']);
        $this->assertStringContainsString('sin PDF', $sendResult['message']);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/messages')) {
                return false;
            }
            $components = $request->data()['template']['components'] ?? [];

            return ($components[0]['type'] ?? '') === 'body';
        });
    }

    public function test_send_queued_notification_uses_signed_pdf_link_by_default(): void
    {
        Bus::fake();
        config([
            'services.whatsapp.template_include_document' => true,
            'services.whatsapp.document_delivery' => 'link',
            'app.url' => 'https://soporte.exactolp.mx',
        ]);

        $this->mock(OrderPdfController::class, function ($mock): void {
            $mock->shouldReceive('renderOrderPdfBinary')
                ->once()
                ->with(9, true)
                ->andReturn('%PDF-1.4 test');
        });

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.LINK123'],
                ],
            ], 200),
        ]);

        $queued = app(OrderWhatsappService::class)->queueForStatusWithResult(9, 'Recepción', [
            'id_orden_c' => 9,
            'folio' => 'OS-2026-009',
            'telefono' => '5512345678',
            'nombre_cliente' => 'Cliente',
        ]);

        $sendResult = app(OrderWhatsappService::class)->sendQueuedNotification((int) $queued['notification_id']);

        $this->assertTrue($sendResult['sent']);
        $this->assertStringContainsString('enlace', $sendResult['message']);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/messages')) {
                return false;
            }
            $components = $request->data()['template']['components'] ?? [];
            $doc = $components[0]['parameters'][0]['document'] ?? [];

            return ($components[0]['type'] ?? '') === 'header'
                && isset($doc['link'])
                && str_contains((string) $doc['link'], '/wa/pdf/orden/9')
                && ($doc['filename'] ?? '') === 'OS-2026-009.pdf';
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/media');
        });
    }
}
