<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Schema helpers that survive older SQLite builds (e.g. vercel-php)
 * where pragma_table_xinfo(table, schema) is unsupported.
 */
final class SafeSchema
{
    /** @var array<string, array<string, bool>> */
    private static array $columnCache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table;
        if (isset(self::$columnCache[$key][$column])) {
            return self::$columnCache[$key][$column];
        }

        try {
            $exists = Schema::hasColumn($table, $column);
            self::$columnCache[$key][$column] = $exists;

            return $exists;
        } catch (Throwable) {
            // Fallback: PRAGMA table_info (single-arg) works on older SQLite.
            try {
                $rows = DB::select('PRAGMA table_info('.$table.')');
                foreach ($rows as $row) {
                    $name = (string) ($row->name ?? '');
                    self::$columnCache[$key][$name] = true;
                }
                if (! isset(self::$columnCache[$key])) {
                    self::$columnCache[$key] = [];
                }

                return self::$columnCache[$key][$column] ?? false;
            } catch (Throwable) {
                return false;
            }
        }
    }
}
