<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class LoginIdSequenceService
{
    private const SEQUENCE_KEY = 'login_id_tecnico';

    public function reserveNextId(): int
    {
        return DB::transaction(function (): int {
            $driver = DB::getDriverName();
            $sql = 'SELECT next_id FROM exacto_login_sequences WHERE sequence_key = ?';
            if ($driver === 'mysql') {
                $sql .= ' FOR UPDATE';
            }

            $row = DB::selectOne($sql, [self::SEQUENCE_KEY]);
            $currentMax = (int) (DB::scalar('SELECT MAX(id_tecnico) FROM login') ?? 0) + 1;
            if (! $row) {
                $next = max(1, $currentMax);
                DB::insert(
                    'INSERT INTO exacto_login_sequences (sequence_key, next_id, created_at, updated_at) VALUES (?, ?, ?, ?)',
                    [self::SEQUENCE_KEY, $next + 1, now(), now()]
                );

                return $next;
            }

            $next = max((int) ($row->next_id ?? 1), $currentMax);
            DB::update(
                'UPDATE exacto_login_sequences SET next_id = ?, updated_at = ? WHERE sequence_key = ?',
                [$next + 1, now(), self::SEQUENCE_KEY]
            );

            return $next;
        });
    }
}
