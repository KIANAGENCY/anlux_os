<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PdfCondicionesService
{
    public const SETTING_KEY = 'pdf_condiciones_garantia';

    /** @var list<string> */
    public const DEFAULT_LINES = [
        'Para hacer válida la garantía de una compra es indispensable presentar su Factura o Ticket dentro de los 90 días naturales.',
        'Las garantias de servicios o reparaciones tienen una vigencia de 90 días naturales.',
        'Después del periodo de garantía, cualquier revisión o servicio generará costo adicional.',
        'No nos hacemos responsables por pérdida parcial o total de información, software, licencias o configuraciones del equipo.',
        'El cliente autoriza pruebas, instalación/desinstalación de software, formateo y apertura del equipo para diagnóstico o reparación.',
        'Los equipos no recogidos dentro de los 15 días naturales posteriores a la notificación de entrega podrán generar cargos por resguardo; después de ese plazo no nos hacemos responsables por el equipo.',
        'Toda revisión o diagnóstico sin reparación podrá generar cargos.',
        'La garantía no aplica por daños causados por golpes, humedad, variaciones de voltaje, mal uso o intervención de terceros.',
        'Al recibir el equipo, el cliente acepta las presentes condiciones.',
    ];

    public function ensureTable(): void
    {
        if (! Schema::hasTable('anlux_settings')) {
            throw new \RuntimeException('Falta la tabla anlux_settings. Ejecuta las migraciones pendientes.');
        }
    }

    public function defaultText(): string
    {
        return implode("\n", array_map(static fn (string $l): string => '* '.$l, self::DEFAULT_LINES));
    }

    public function get(): string
    {
        if (! Schema::hasTable('anlux_settings')) {
            return $this->defaultText();
        }

        $row = DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->first();
        $raw = trim((string) ($row->content ?? ''));
        if ($raw === '') {
            return $this->defaultText();
        }

        return $raw;
    }

    public function save(string $content): void
    {
        $this->ensureTable();
        $now = now();
        if (DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->exists()) {
            DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->update([
                'content' => $content,
                'updated_at' => $now,
            ]);

            return;
        }
        DB::table('anlux_settings')->insert([
            'setting_key' => self::SETTING_KEY,
            'content' => $content,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Líneas de texto para mostrar en la orden de servicio (web), sin el prefijo *.
     *
     * @return list<string>
     */
    public function linesForDisplay(): array
    {
        $text = $this->get();
        $lines = preg_split('/\R/u', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '*')) {
                $line = trim(substr($line, 1));
            }
            if ($line !== '') {
                $out[] = $line;
            }
        }

        if ($out === []) {
            return self::DEFAULT_LINES;
        }

        return $out;
    }

    /** HTML seguro para el PDF (mismo contenido que la orden web). */
    public function toPdfPanelHtml(): string
    {
        $paras = [];
        foreach ($this->linesForDisplay() as $line) {
            $paras[] = '<p class="cond-par">&bull; '.e($line).'</p>';
        }
        $body = implode('', $paras);

        return '<div class="cond-panel"><div class="cond-panel-head"><span class="cond-panel-icon">i</span>'
            .e('CONDICIONES DE ENTREGA DEL EQUIPO')
            .'</div><div class="cond-panel-body">'.$body.'</div></div>';
    }
}
