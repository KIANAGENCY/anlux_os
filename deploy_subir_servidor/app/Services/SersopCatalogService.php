<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SersopCatalogService
{
    public function ensureTable(): void
    {
        if (Schema::hasTable('sersop_catalog')) {
            return;
        }

        Schema::create('sersop_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 20)->unique();
            $table->string('descripcion', 255);
            $table->decimal('precio', 12, 2)->default(0);
            $table->boolean('editable')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    private function normalizeClave(string $clave): string
    {
        $clave = trim($clave);
        $clave = preg_replace('/\s+/', '', $clave) ?? '';

        return mb_strtoupper($clave, 'UTF-8');
    }

    /**
     * @return array<int, array{clave:string, descripcion:string, precio:float, editable:bool, activo:bool}>
     */
    public function all(): array
    {
        $this->ensureTable();
        $rows = DB::select('SELECT clave, descripcion, precio, editable, activo FROM sersop_catalog ORDER BY id ASC');
        if (! empty($rows)) {
            return array_map(static function ($r): array {
                $a = (array) $r;

                return [
                    'clave' => (string) ($a['clave'] ?? ''),
                    'descripcion' => (string) ($a['descripcion'] ?? ''),
                    'precio' => (float) ($a['precio'] ?? 0),
                    'editable' => (int) ($a['editable'] ?? 0) === 1,
                    'activo' => (int) ($a['activo'] ?? 0) === 1,
                ];
            }, $rows);
        }

        $fallback = config('servicios_sersop', []);
        $out = [];
        foreach ($fallback as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'clave' => $this->normalizeClave((string) ($item['clave'] ?? '')),
                'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                'precio' => round((float) ($item['precio'] ?? 0), 2),
                'editable' => ! empty($item['editable']),
                'activo' => ! array_key_exists('activo', $item) || ! empty($item['activo']),
            ];
        }
        if (! empty($out)) {
            $this->replaceAll($out);
        }

        return $out;
    }

    /**
     * @return array<int, array{clave:string, descripcion:string, precio:float, editable:bool, activo:bool}>
     */
    public function active(): array
    {
        return array_values(array_filter($this->all(), static fn (array $s): bool => ! empty($s['activo']) && ! empty($s['clave'])));
    }

    /**
     * @param  array<int, array{clave:string, descripcion:string, precio:float, editable:bool, activo:bool}>  $rows
     */
    public function replaceAll(array $rows): void
    {
        $this->ensureTable();
        DB::transaction(function () use ($rows): void {
            DB::delete('DELETE FROM sersop_catalog');
            $now = now()->format('Y-m-d H:i:s');
            foreach ($rows as $row) {
                DB::insert(
                    'INSERT INTO sersop_catalog (clave, descripcion, precio, editable, activo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $this->normalizeClave((string) ($row['clave'] ?? '')),
                        trim((string) ($row['descripcion'] ?? '')),
                        round((float) ($row['precio'] ?? 0), 2),
                        ! empty($row['editable']) ? 1 : 0,
                        ! empty($row['activo']) ? 1 : 0,
                        $now,
                        $now,
                    ]
                );
            }
        });
    }

    /**
     * Reemplaza el catálogo en BD con los PRECIOS SIN IVA de config/servicios_sersop.php.
     *
     * @return array<int, array{clave:string, descripcion:string, precio:float, editable:bool, activo:bool}>
     */
    public function syncFromConfig(): array
    {
        $fallback = config('servicios_sersop', []);
        $out = [];
        foreach ($fallback as $item) {
            if (! is_array($item)) {
                continue;
            }
            $clave = $this->normalizeClave((string) ($item['clave'] ?? ''));
            if ($clave === '') {
                continue;
            }
            $out[] = [
                'clave' => $clave,
                'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                'precio' => round((float) ($item['precio'] ?? 0), 2),
                'editable' => ! empty($item['editable']),
                'activo' => ! array_key_exists('activo', $item) || ! empty($item['activo']),
            ];
        }
        if ($out === []) {
            throw new \RuntimeException('config/servicios_sersop.php no tiene servicios válidos.');
        }
        $this->replaceAll($out);

        return $out;
    }
}
