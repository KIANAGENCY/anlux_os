<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\MaintenanceModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_activate_maintenance_mode(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->post(route('admin.maintenance.update'), [
            'enabled' => '1',
            'message' => 'Estamos actualizando archivos.',
        ])->assertRedirect(route('admin.index'));

        $status = app(MaintenanceModeService::class)->status();
        $this->assertTrue($status['enabled']);
        $this->assertSame('Estamos actualizando archivos.', $status['message']);
    }

    public function test_technician_sees_maintenance_window_but_admin_keeps_access(): void
    {
        $admin = User::factory()->administrador()->create();
        $technician = User::factory()->create();
        app(MaintenanceModeService::class)->update(true, 'Regresamos en unos minutos.');

        $this->actingAs($technician)
            ->get(route('ordenes.index'))
            ->assertRedirect(route('maintenance.show'));

        $this->actingAs($technician)
            ->get(route('maintenance.show'))
            ->assertOk()
            ->assertSee('Regresamos en unos minutos.')
            ->assertSee('Regresar al inicio de sesión', false);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Modo de mantenimiento');

        $this->actingAs($admin)
            ->get(route('maintenance.show'))
            ->assertOk()
            ->assertSee('Desactivar mantenimiento')
            ->assertSee('Volver al panel de administración');
    }

    public function test_maintenance_api_requests_return_503(): void
    {
        $technician = User::factory()->create();
        app(MaintenanceModeService::class)->update(true, 'Mantenimiento activo.');

        $this->actingAs($technician)
            ->getJson(route('ordenes.list'))
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'maintenance' => true,
                'message' => 'Mantenimiento activo.',
            ]);
    }

    public function test_admin_view_has_a_safe_default_during_a_partial_deployment(): void
    {
        View::share('errors', new ViewErrorBag);
        $html = View::make('admin.index', [
            'nombreTecnico' => 'Administrador',
            'nav_admin_activo' => 'admin',
        ])->render();

        $this->assertStringContainsString('Modo de mantenimiento', $html);
        $this->assertStringContainsString('INACTIVO', $html);
    }

    public function test_maintenance_view_has_a_safe_default_during_a_partial_deployment(): void
    {
        View::share('errors', new ViewErrorBag);
        $html = View::make('maintenance', [
            'pageTitle' => 'Sistema en mantenimiento - Anlux',
        ])->render();

        $this->assertStringContainsString('Sistema en mantenimiento', $html);
        $this->assertStringContainsString('Estamos actualizando el sistema.', $html);
    }

    public function test_technician_cannot_disable_maintenance_through_admin_route(): void
    {
        $technician = User::factory()->create();
        app(MaintenanceModeService::class)->update(true, 'Mantenimiento activo.');

        $this->actingAs($technician)
            ->post(route('admin.maintenance.update'), ['enabled' => '0'])
            ->assertRedirect(route('maintenance.show'));

        $this->assertTrue(app(MaintenanceModeService::class)->enabled());
    }
}
