<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\IntegrationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AdminIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_encrypted_runtime_integrations(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('admin.integrations.update'), $this->payload())
            ->assertRedirect();

        $content = (string) DB::table('anlux_settings')->where('setting_key', 'runtime_integrations')->value('content');
        $this->assertStringNotContainsString('smtp-secret-value', $content);
        $this->assertStringNotContainsString('wa-secret-value', $content);
        $settings = app(IntegrationSettingsService::class);
        $this->assertSame('smtp-secret-value', $settings->revealForRuntime('mail', 'password_sealed'));
        $this->assertSame('wa-secret-value', $settings->revealForRuntime('whatsapp', 'access_token_sealed'));
        $public = $settings->publicSettings();
        $this->assertArrayNotHasKey('password_sealed', $public['mail']);
        $this->assertArrayNotHasKey('access_token_sealed', $public['whatsapp']);
    }

    public function test_blank_secret_preserves_the_existing_encrypted_value(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)->put(route('admin.integrations.update'), $this->payload())->assertRedirect();
        $second = $this->payload();
        $second['mail']['password'] = '';
        $second['whatsapp']['access_token'] = '';

        $this->actingAs($admin)->put(route('admin.integrations.update'), $second)->assertRedirect();

        $settings = app(IntegrationSettingsService::class);
        $this->assertSame('smtp-secret-value', $settings->revealForRuntime('mail', 'password_sealed'));
        $this->assertSame('wa-secret-value', $settings->revealForRuntime('whatsapp', 'access_token_sealed'));
    }

    public function test_whatsapp_probe_uses_saved_credentials_without_exposing_token(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'display_phone_number' => '+52 612 000 0000',
            'verified_name' => 'Anlux',
        ])]);
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)->put(route('admin.integrations.update'), $this->payload())->assertRedirect();

        $this->actingAs($admin)->post(route('admin.integrations.testWhatsapp'))
            ->assertRedirect()
            ->assertSessionHas('status');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/123456789')
            && $request->hasHeader('Authorization', 'Bearer wa-secret-value'));
        $this->assertSame('tested', app(IntegrationSettingsService::class)->publicSettings()['tests']['whatsapp']['status']);
    }

    public function test_technician_cannot_open_integrations(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.integrations.index'))
            ->assertRedirect(route('ordenes.index'));
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'app_url' => 'https://soporte.example.test',
            'mail' => [
                'enabled' => '1',
                'host' => 'smtp.example.test',
                'port' => 587,
                'scheme' => '',
                'username' => 'notificaciones@example.test',
                'password' => 'smtp-secret-value',
                'from_address' => 'notificaciones@example.test',
                'from_name' => 'Anlux',
            ],
            'whatsapp' => [
                'enabled' => '1',
                'base_url' => 'https://graph.facebook.com',
                'graph_version' => 'v20.0',
                'phone_number_id' => '123456789',
                'access_token' => 'wa-secret-value',
                'verify_token' => 'verify-secret-value',
                'app_secret' => 'app-secret-value',
                'webhook_verify_signature' => '1',
                'language' => 'es_MX',
                'default_country_code' => '52',
                'template_include_document' => '1',
                'templates' => [
                    'recepcion' => 'orden_recepcion',
                    'terminado' => 'orden_terminado',
                    'entregado' => 'orden_entregado',
                ],
            ],
        ];
    }
}
