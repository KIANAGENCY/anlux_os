<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BrandingService;
use App\Services\SecurityActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdminAppearanceController extends Controller
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly SecurityActivityLogger $securityLog,
    ) {}

    public function index(): View
    {
        $this->guardImpersonation();

        return view('admin.apariencia', [
            'pageTitle' => 'Apariencia - Anlux',
            'nav_admin_activo' => 'apariencia',
            'appearance' => $this->branding->get(),
            'fonts' => $this->branding->fontCatalog(),
            'logoUrl' => $this->branding->logoUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->guardImpersonation();
        $colorRule = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $validated = $request->validate([
            'colors.primary' => $colorRule,
            'colors.secondary' => $colorRule,
            'colors.accent' => $colorRule,
            'colors.background' => $colorRule,
            'colors.surface' => $colorRule,
            'colors.text' => $colorRule,
            'font' => ['required', Rule::in(array_keys($this->branding->fontCatalog()))],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=32,max_width=3000,max_height=2000'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $colors = $validated['colors'];
        if ($this->contrast($colors['text'], $colors['surface']) < 4.5) {
            throw ValidationException::withMessages(['colors.text' => 'Texto y superficie necesitan contraste WCAG AA (mínimo 4.5:1).']);
        }
        if ($this->contrast($colors['primary'], '#FFFFFF') < 4.5) {
            throw ValidationException::withMessages(['colors.primary' => 'El color primario debe ser legible con texto blanco (mínimo 4.5:1).']);
        }
        if ($this->contrast($colors['secondary'], '#FFFFFF') < 4.5) {
            throw ValidationException::withMessages(['colors.secondary' => 'El color secundario debe ser legible con texto blanco (mínimo 4.5:1).']);
        }

        $this->branding->update($validated, $request->file('logo'));
        $this->securityLog->log('apariencia_actualizada', 'info', 'Paleta, tipografía o logo actualizados.');

        return back()->with('status', 'Apariencia guardada. El cambio ya está activo.');
    }

    public function reset(): RedirectResponse
    {
        $this->guardImpersonation();
        $this->branding->reset();
        $this->securityLog->log('apariencia_restablecida', 'warning', 'Apariencia restablecida a valores Anlux.');

        return back()->with('status', 'Apariencia restablecida a los valores Anlux.');
    }

    private function guardImpersonation(): void
    {
        abort_if((bool) session('anlux_impersonating', false), 403, 'Sal de la cuenta impersonada para cambiar la apariencia.');
    }

    private function contrast(string $a, string $b): float
    {
        $lighter = max($this->luminance($a), $this->luminance($b));
        $darker = min($this->luminance($a), $this->luminance($b));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }
}
