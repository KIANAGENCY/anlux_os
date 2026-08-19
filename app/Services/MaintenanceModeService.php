<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MaintenanceModeService
{
    private const SETTING_KEY = 'maintenance_mode';

    /** @return array{enabled: bool, message: string, updated_at: ?string} */
    public function status(): array
    {
        try {
            if (! Schema::hasTable('exacto_settings')) {
                return $this->disabledStatus();
            }

            $row = DB::table('exacto_settings')->where('setting_key', self::SETTING_KEY)->first();
            if (! $row) {
                return $this->disabledStatus();
            }

            $content = json_decode((string) ($row->content ?? ''), true);
            if (! is_array($content)) {
                return $this->disabledStatus();
            }

            return [
                'enabled' => filter_var($content['enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'message' => $this->normalizeMessage((string) ($content['message'] ?? '')),
                'updated_at' => isset($row->updated_at) ? (string) $row->updated_at : null,
            ];
        } catch (\Throwable) {
            // Si la tabla o la conexión no está disponible, no bloquear toda la aplicación.
            return $this->disabledStatus();
        }
    }

    public function enabled(): bool
    {
        return $this->status()['enabled'];
    }

    public function update(bool $enabled, string $message): void
    {
        if (! Schema::hasTable('exacto_settings')) {
            throw new \RuntimeException('Falta la tabla exacto_settings. Ejecuta las migraciones pendientes.');
        }

        $now = now();
        DB::table('exacto_settings')->updateOrInsert(
            ['setting_key' => self::SETTING_KEY],
            [
                'content' => json_encode([
                    'enabled' => $enabled,
                    'message' => $this->normalizeMessage($message),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /** @return array{enabled: false, message: string, updated_at: null} */
    private function disabledStatus(): array
    {
        return [
            'enabled' => false,
            'message' => $this->defaultMessage(),
            'updated_at' => null,
        ];
    }

    private function normalizeMessage(string $message): string
    {
        $message = trim($message);

        return $message !== '' ? $message : $this->defaultMessage();
    }

    private function defaultMessage(): string
    {
        return 'Estamos actualizando el sistema. Por favor, vuelve a intentarlo en unos minutos.';
    }
}
