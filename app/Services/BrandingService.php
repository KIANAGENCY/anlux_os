<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class BrandingService
{
    private const SETTING_KEY = 'appearance';

    /** @var array<string, mixed>|null */
    private ?array $memoized = null;

    /** @return array<string, array<string, string>> */
    public function fontCatalog(): array
    {
        return [
            'system' => ['label' => 'Sistema (recomendada)', 'css' => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif', 'pdf' => 'DejaVu Sans'],
            'arial' => ['label' => 'Arial', 'css' => 'Arial, Helvetica, sans-serif', 'pdf' => 'Arial'],
            'verdana' => ['label' => 'Verdana', 'css' => 'Verdana, Geneva, sans-serif', 'pdf' => 'DejaVu Sans'],
            'trebuchet' => ['label' => 'Trebuchet', 'css' => '"Trebuchet MS", Arial, sans-serif', 'pdf' => 'DejaVu Sans'],
            'georgia' => ['label' => 'Georgia', 'css' => 'Georgia, "Times New Roman", serif', 'pdf' => 'DejaVu Serif'],
        ];
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        if ($this->memoized !== null) {
            return $this->memoized;
        }
        $defaults = $this->defaults();
        try {
            if (! Schema::hasTable('anlux_settings')) {
                return $this->memoized = $defaults;
            }
            $content = DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->value('content');
            $stored = json_decode((string) $content, true);
            if (! is_array($stored)) {
                return $this->memoized = $defaults;
            }

            $stored['colors'] = array_merge($defaults['colors'], is_array($stored['colors'] ?? null) ? $stored['colors'] : []);

            return $this->memoized = array_merge($defaults, $stored);
        } catch (\Throwable) {
            return $this->memoized = $defaults;
        }
    }

    /** @param array<string, mixed> $input */
    public function update(array $input, ?UploadedFile $logo = null): void
    {
        if (! Schema::hasTable('anlux_settings')) {
            throw new \RuntimeException('Falta la tabla anlux_settings. Ejecuta las migraciones pendientes.');
        }

        $current = $this->get();
        $logoPath = (string) ($current['logo_path'] ?? '');
        $logoMime = (string) ($current['logo_mime'] ?? '');

        if ($logo !== null) {
            Storage::disk('local')->makeDirectory('branding');
            $extension = strtolower($logo->guessExtension() ?: $logo->extension() ?: 'png');
            $newPath = $logo->storeAs('branding', 'logo-'.bin2hex(random_bytes(8)).'.'.$extension, 'local');
            if (! is_string($newPath) || $newPath === '') {
                throw new \RuntimeException('No fue posible guardar el logo.');
            }
            if ($logoPath !== '' && str_starts_with($logoPath, 'branding/')) {
                Storage::disk('local')->delete($logoPath);
            }
            $logoPath = $newPath;
            $logoMime = (string) ($logo->getMimeType() ?: 'image/png');
        }

        if ((bool) ($input['remove_logo'] ?? false)) {
            if ($logoPath !== '' && str_starts_with($logoPath, 'branding/')) {
                Storage::disk('local')->delete($logoPath);
            }
            $logoPath = '';
            $logoMime = '';
        }

        $colors = [];
        foreach (array_keys($this->defaults()['colors']) as $key) {
            $colors[$key] = strtoupper((string) ($input['colors'][$key] ?? $current['colors'][$key]));
        }

        $font = (string) ($input['font'] ?? $current['font']);
        if (! isset($this->fontCatalog()[$font])) {
            $font = 'system';
        }

        $now = now();
        DB::table('anlux_settings')->updateOrInsert(
            ['setting_key' => self::SETTING_KEY],
            [
                'content' => json_encode([
                    'colors' => $colors,
                    'font' => $font,
                    'logo_path' => $logoPath,
                    'logo_mime' => $logoMime,
                    'updated_at' => $now->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $this->memoized = null;
    }

    public function reset(): void
    {
        $current = $this->get();
        $path = (string) ($current['logo_path'] ?? '');
        if ($path !== '' && str_starts_with($path, 'branding/')) {
            Storage::disk('local')->delete($path);
        }
        if (Schema::hasTable('anlux_settings')) {
            DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->delete();
        }
        $this->memoized = null;
    }

    public function logoAbsolutePath(): string
    {
        $settings = $this->get();
        $path = (string) ($settings['logo_path'] ?? '');
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        return public_path('legacy/public/img/logo.jpeg');
    }

    public function logoMime(): string
    {
        $settings = $this->get();
        $path = (string) ($settings['logo_path'] ?? '');

        return $path !== '' && Storage::disk('local')->exists($path)
            ? (string) ($settings['logo_mime'] ?? 'image/png')
            : 'image/jpeg';
    }

    public function logoVersion(): string
    {
        $settings = $this->get();

        return substr(hash('sha256', (string) ($settings['logo_path'] ?? '').'|'.(string) ($settings['updated_at'] ?? 'default')), 0, 12);
    }

    public function logoUrl(): string
    {
        $settings = $this->get();
        $customPath = (string) ($settings['logo_path'] ?? '');
        if ($customPath !== '' && Storage::disk('local')->exists($customPath)) {
            return route('branding.logo', ['v' => $this->logoVersion()]);
        }
        $path = $this->logoAbsolutePath();

        return asset('legacy/public/img/logo.jpeg').'?v='.(is_file($path) ? filemtime($path) : 1);
    }

    /** @return array<string, string> */
    public function colors(): array
    {
        return $this->get()['colors'];
    }

    public function fontCss(): string
    {
        $font = (string) $this->get()['font'];

        return $this->fontCatalog()[$font]['css'] ?? $this->fontCatalog()['system']['css'];
    }

    public function pdfFont(): string
    {
        $font = (string) $this->get()['font'];

        return $this->fontCatalog()[$font]['pdf'] ?? 'DejaVu Sans';
    }

    public function cssVariables(): string
    {
        $colors = $this->colors();
        $variables = [
            '--anlux-primary' => $colors['primary'],
            '--anlux-primary-hover' => $this->mix($colors['primary'], '#000000', 0.16),
            '--anlux-deep' => $colors['secondary'],
            '--anlux-pale' => $this->mix($colors['primary'], '#FFFFFF', 0.91),
            '--anlux-canvas' => $colors['background'],
            '--anlux-surface' => $colors['surface'],
            '--anlux-text' => $colors['text'],
            '--anlux-text-muted' => $this->mix($colors['text'], $colors['background'], 0.42),
            '--anlux-border' => $this->mix($colors['text'], $colors['background'], 0.73),
            '--anlux-border-soft' => $this->mix($colors['text'], $colors['background'], 0.86),
            '--anlux-font-family' => $this->fontCss(),
        ];

        foreach ($this->scale($colors['primary']) as $shade => $rgb) {
            $variables['--anlux-blue-'.$shade] = $rgb;
        }
        foreach ($this->scale($colors['accent']) as $shade => $rgb) {
            $variables['--anlux-cyan-'.$shade] = $rgb;
        }
        foreach ($this->neutralScale($colors['background'], $colors['text']) as $shade => $rgb) {
            $variables['--anlux-slate-'.$shade] = $rgb;
        }

        return ':root{'.implode('', array_map(
            static fn (string $key, string $value): string => $key.':'.$value.';',
            array_keys($variables),
            array_values($variables)
        )).'}';
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'colors' => [
                'primary' => '#2563EB',
                'secondary' => '#1E3A8A',
                'accent' => '#06B6D4',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'text' => '#0F172A',
            ],
            'font' => 'system',
            'logo_path' => '',
            'logo_mime' => 'image/jpeg',
            'updated_at' => null,
        ];
    }

    /** @return array<int, string> */
    private function scale(string $base): array
    {
        return [
            50 => $this->rgb($this->mix($base, '#FFFFFF', 0.95)),
            100 => $this->rgb($this->mix($base, '#FFFFFF', 0.89)),
            200 => $this->rgb($this->mix($base, '#FFFFFF', 0.75)),
            300 => $this->rgb($this->mix($base, '#FFFFFF', 0.56)),
            400 => $this->rgb($this->mix($base, '#FFFFFF', 0.30)),
            500 => $this->rgb($this->mix($base, '#FFFFFF', 0.10)),
            600 => $this->rgb($base),
            700 => $this->rgb($this->mix($base, '#000000', 0.16)),
            800 => $this->rgb($this->mix($base, '#000000', 0.31)),
            900 => $this->rgb($this->mix($base, '#000000', 0.46)),
            950 => $this->rgb($this->mix($base, '#000000', 0.62)),
        ];
    }

    /** @return array<int, string> */
    private function neutralScale(string $background, string $text): array
    {
        $weights = [50 => 0.02, 100 => 0.07, 200 => 0.14, 300 => 0.24, 400 => 0.42, 500 => 0.58, 600 => 0.70, 700 => 0.80, 800 => 0.90, 900 => 1.0, 950 => 1.0];
        $out = [];
        foreach ($weights as $shade => $weight) {
            $out[$shade] = $this->rgb($this->mix($background, $text, $weight));
        }

        return $out;
    }

    private function mix(string $from, string $to, float $weight): string
    {
        [$fr, $fg, $fb] = $this->hexParts($from);
        [$tr, $tg, $tb] = $this->hexParts($to);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($fr + (($tr - $fr) * $weight)),
            (int) round($fg + (($tg - $fg) * $weight)),
            (int) round($fb + (($tb - $fb) * $weight))
        );
    }

    private function rgb(string $hex): string
    {
        return implode(' ', $this->hexParts($hex));
    }

    /** @return array{int, int, int} */
    private function hexParts(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            $hex = '2563EB';
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
