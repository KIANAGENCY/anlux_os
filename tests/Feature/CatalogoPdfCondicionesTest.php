<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfCondicionesService;
use App\Services\SersopCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoPdfCondicionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_pdf_conditions_with_catalog(): void
    {
        $admin = User::factory()->administrador()->create();
        $catalog = app(SersopCatalogService::class)->all();
        $this->assertNotEmpty($catalog);
        $first = $catalog[0];

        $nuevoTexto = "* Primera condición de prueba\n* Segunda condición";

        $servicio = [
            'clave' => $first['clave'],
            'descripcion' => $first['descripcion'],
            'precio' => number_format((float) $first['precio'], 2, '.', ''),
        ];
        if ($first['editable']) {
            $servicio['editable'] = 'on';
        }
        if ($first['activo']) {
            $servicio['activo'] = 'on';
        }

        $response = $this->actingAs($admin)->post(route('admin.catalogo.update'), [
            'servicios' => [$servicio],
            'condiciones_pdf' => $nuevoTexto,
        ]);

        $response->assertRedirect(route('admin.catalogo.index'));
        $this->assertSame($nuevoTexto, app(PdfCondicionesService::class)->get());
    }
}
