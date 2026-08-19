<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Asigna folios OS-{anio}-{nnn} reutilizando el menor hueco libre del año.
 */
final class FolioSequenceService
{
    /**
     * Próximo folio que se asignaría (sin reservar / sin lock).
     */
    public function peekNextFolio(?int $anio = null): string
    {
        $anio = $anio ?? (int) date('Y');
        $num = $this->findLowestFreeNumber($anio);

        return $this->formatFolio($anio, $num);
    }

    /**
     * Debe llamarse dentro de una transacción activa (FOR UPDATE).
     *
     * @return array{folio: string, numero: int, anio: int, reutilizado: bool}
     */
    public function reservarFolio(int $anio): array
    {
        $row = $this->lockSequenceRow($anio);
        $usados = $this->usedNumbers($anio);
        $maxUsado = $usados === [] ? 0 : max($usados);

        if (! $row) {
            DB::insert('INSERT INTO orden_folio_sequence (anio, next_num) VALUES (?, ?)', [$anio, max(1, $maxUsado + 1)]);
            $row = $this->lockSequenceRow($anio);
        }

        $nextNum = max(1, (int) ($row->next_num ?? 1));
        $ceiling = max($nextNum, $maxUsado + 1);
        $libre = $this->lowestMissing($usados, $ceiling);
        $reutilizado = $libre < $nextNum;
        $desired = $reutilizado ? max($nextNum, $maxUsado + 1) : ($libre + 1);
        DB::update('UPDATE orden_folio_sequence SET next_num = ? WHERE anio = ?', [$desired, $anio]);

        return [
            'folio' => $this->formatFolio($anio, $libre),
            'numero' => $libre,
            'anio' => $anio,
            'reutilizado' => $reutilizado,
        ];
    }

    /**
     * @return list<int>
     */
    public function usedNumbers(int $anio): array
    {
        $rows = DB::select(
            'SELECT folio FROM orden_servicio_c WHERE folio LIKE ?',
            ['OS-'.$anio.'-%']
        );

        $nums = [];
        $pattern = '/^OS-'.preg_quote((string) $anio, '/').'-(\d+)$/';
        foreach ($rows as $r) {
            $folio = (string) ($r->folio ?? '');
            if (preg_match($pattern, $folio, $m)) {
                $n = (int) $m[1];
                if ($n > 0) {
                    $nums[$n] = $n;
                }
            }
        }

        return array_values($nums);
    }

    /**
     * @return list<string> Folios hueco p.ej. OS-2026-003
     */
    public function listGaps(?int $anio = null, int $limit = 50): array
    {
        $anio = $anio ?? (int) date('Y');
        $usados = $this->usedNumbers($anio);
        $maxUsado = $usados === [] ? 0 : max($usados);
        $nextNum = (int) (DB::scalar('SELECT next_num FROM orden_folio_sequence WHERE anio = ?', [$anio]) ?? 0);
        $ceiling = max($maxUsado, max(0, $nextNum - 1));
        if ($ceiling < 1) {
            return [];
        }

        $set = array_fill_keys($usados, true);
        $gaps = [];
        for ($i = 1; $i <= $ceiling && count($gaps) < $limit; $i++) {
            if (! isset($set[$i])) {
                $gaps[] = $this->formatFolio($anio, $i);
            }
        }

        return $gaps;
    }

    /**
     * @return array{anio: int, next_num: int, max_usado: int, proximo_folio: string, huecos: list<string>}
     */
    public function status(?int $anio = null): array
    {
        $anio = $anio ?? (int) date('Y');
        $usados = $this->usedNumbers($anio);
        $maxUsado = $usados === [] ? 0 : max($usados);
        $nextNum = (int) (DB::scalar('SELECT next_num FROM orden_folio_sequence WHERE anio = ?', [$anio]) ?? 0);
        if ($nextNum <= 0) {
            $nextNum = $maxUsado + 1;
        }

        return [
            'anio' => $anio,
            'next_num' => $nextNum,
            'max_usado' => $maxUsado,
            'proximo_folio' => $this->peekNextFolio($anio),
            'huecos' => $this->listGaps($anio),
        ];
    }

    /**
     * Ajusta next_num = max(usados)+1.
     *
     * @return array{success: bool, message: string, next_num?: int, anterior?: int}
     */
    public function syncNextNum(?int $anio = null): array
    {
        $anio = $anio ?? (int) date('Y');

        return DB::transaction(function () use ($anio) {
            $row = $this->lockSequenceRow($anio);
            $usados = $this->usedNumbers($anio);
            $maxUsado = $usados === [] ? 0 : max($usados);
            $desired = $maxUsado + 1;
            $anterior = $row ? (int) $row->next_num : 0;

            if (! $row) {
                DB::insert('INSERT INTO orden_folio_sequence (anio, next_num) VALUES (?, ?)', [$anio, $desired]);
            } else {
                DB::update('UPDATE orden_folio_sequence SET next_num = ? WHERE anio = ?', [$desired, $anio]);
            }

            return [
                'success' => true,
                'message' => 'Contador sincronizado a '.$desired.' (próximo folio '.$this->formatFolio($anio, $this->findLowestFreeNumber($anio)).').',
                'next_num' => $desired,
                'anterior' => $anterior,
            ];
        });
    }

    public function formatFolio(int $anio, int $numero): string
    {
        return 'OS-'.$anio.'-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }

    private function findLowestFreeNumber(int $anio): int
    {
        $usados = $this->usedNumbers($anio);
        $maxUsado = $usados === [] ? 0 : max($usados);
        $nextNum = (int) (DB::scalar('SELECT next_num FROM orden_folio_sequence WHERE anio = ?', [$anio]) ?? 0);
        $ceiling = max($nextNum > 0 ? $nextNum : 1, $maxUsado + 1);

        return $this->lowestMissing($usados, $ceiling);
    }

    /**
     * @param  list<int>  $usados
     */
    private function lowestMissing(array $usados, int $ceiling): int
    {
        $set = array_fill_keys($usados, true);
        $ceiling = max(1, $ceiling);
        for ($i = 1; $i <= $ceiling; $i++) {
            if (! isset($set[$i])) {
                return $i;
            }
        }

        return $ceiling;
    }

    private function lockSequenceRow(int $anio): ?object
    {
        // SQLite de pruebas no soporta FOR UPDATE.
        if (DB::getDriverName() === 'sqlite') {
            return DB::selectOne('SELECT next_num FROM orden_folio_sequence WHERE anio = ?', [$anio]);
        }

        return DB::selectOne('SELECT next_num FROM orden_folio_sequence WHERE anio = ? FOR UPDATE', [$anio]);
    }
}
