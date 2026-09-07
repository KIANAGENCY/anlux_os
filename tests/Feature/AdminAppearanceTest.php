<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_palette_and_font(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('admin.appearance.update'), $this->payload([
            'primary' => '#075985',
            'secondary' => '#164E63',
        ]))->assertRedirect();

        $branding = app(BrandingService::class);
        $this->assertSame('#075985', $branding->colors()['primary']);
        $this->assertSame('verdana', $branding->get()['font']);
        $this->assertStringContainsString('--anlux-primary:#075985', $branding->cssVariables());
    }

    public function test_logo_is_private_and_served_through_versioned_route(): void
    {
        Storage::fake('local');
        $admin = User::factory()->administrador()->create();
        $payload = $this->payload();
        $payload['logo'] = UploadedFile::fake()->image('marca.png', 600, 200);

        $this->actingAs($admin)->put(route('admin.appearance.update'), $payload)->assertRedirect();

        $stored = json_decode((string) DB::table('anlux_settings')->where('setting_key', 'appearance')->value('content'), true);
        Storage::disk('local')->assertExists($stored['logo_path']);
        $this->get(route('branding.logo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_low_contrast_palette_is_rejected(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('admin.appearance.update'), $this->payload([
            'primary' => '#FFFFFF',
        ]))->assertSessionHasErrors('colors.primary');
    }

    public function test_technician_cannot_change_appearance(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.appearance.update'), $this->payload())
            ->assertRedirect(route('ordenes.index'));
    }

    /** @param array<string, string> $overrides */
    private function payload(array $overrides = []): array
    {
        return [
            'colors' => array_merge([
                'primary' => '#2563EB',
                'secondary' => '#1E3A8A',
                'accent' => '#06B6D4',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#0F172A',
            ], $overrides),
            'font' => 'verdana',
        ];
    }
}
