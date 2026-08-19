<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WhatsappWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.verify_token' => 'verify-me',
        ]);
    }

    public function test_whatsapp_webhook_verification_returns_challenge(): void
    {
        $this->get('/webhooks/whatsapp/cloud?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=12345')
            ->assertOk()
            ->assertSeeText('12345');
    }

    public function test_whatsapp_webhook_updates_delivery_status(): void
    {
        DB::table('order_whatsapp_notifications')->insert([
            'id_orden_c' => 10,
            'folio' => 'OS-2026-010',
            'estatus' => 'Terminado',
            'telefono' => '525512345678',
            'template_name' => 'orden_terminado',
            'provider_message_id' => 'wamid.TEST456',
            'status' => 'accepted',
            'message' => 'WhatsApp aceptado por Meta.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.TEST456',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp/cloud', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('order_whatsapp_notifications', [
            'provider_message_id' => 'wamid.TEST456',
            'status' => 'delivered',
        ]);
    }
}
