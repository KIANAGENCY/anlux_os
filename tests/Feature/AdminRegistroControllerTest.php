<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminRegistroControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registration_uses_sequence_without_reusing_existing_ids(): void
    {
        $admin = User::factory()->administrador()->create([
            'id_tecnico' => 10,
            'correo' => 'admin@example.test',
        ]);

        $this->actingAs($admin)->post(route('admin.registro.store'), [
            'nombre' => 'Tecnico Nuevo 1',
            'email' => 'nuevo1@example.test',
            'password' => 'Password1!',
            'confirm_password' => 'Password1!',
            'celular' => '5512345678',
            'perfil' => 'tecnico',
        ])->assertRedirect(route('admin.registro.create'));

        $this->actingAs($admin)->post(route('admin.registro.store'), [
            'nombre' => 'Tecnico Nuevo 2',
            'email' => 'nuevo2@example.test',
            'password' => 'Password1!',
            'confirm_password' => 'Password1!',
            'celular' => '5512345679',
            'perfil' => 'tecnico',
        ])->assertRedirect(route('admin.registro.create'));

        $this->assertDatabaseHas('login', [
            'id_tecnico' => 11,
            'correo' => 'nuevo1@example.test',
        ]);
        $this->assertDatabaseHas('login', [
            'id_tecnico' => 12,
            'correo' => 'nuevo2@example.test',
        ]);
    }
}
