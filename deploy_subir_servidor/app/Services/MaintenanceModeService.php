<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MaintenanceModeService
{
    private const SETTING_KEY = 'maintenance_mode';

    public function status(): array
    {
        try {
            if (! Schema::hasTable('anlux_settings')) {
                return $this->disabledStatus();
            }
            $row = DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->first();
            $content = $row ? json_decode((string) ($row->content ?? ''), true) : null;
            if (! is_array($content)) {
                return $this->disabledStatus();
            }

            return [
                'enabled' => filter_var($content['enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'message' => $this->normalizeMessage((string) ($content['message'] ?? '')),
                'updated_at' => isset($row->updated_at) ? (string) $row->updated_at : null,
            ];
        } catch (\Throwable) {
            return $this->disabledStatus();
        }
    }

    public function enabled(): bool
    {
        return $this->status()['enabled'];
    }

    public function update(bool $enabled, string $message): void
    {
        if (! Schema::hasTable('anlux_settings')) {
            throw new \RuntimeException('Falta la tabla anlux_settings. Ejecuta las migraciones pendientes.');
        }
        $now = now();
        DB::table('anlux_settings')->updateOrInsert(
            ['setting_key' => self::SETTING_KEY],
            [
                'content' => json_encode(['enabled' => $enabled, 'message' => $this->normalizeMessage($message)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function disabledStatus(): array
    {
        return ['enabled' => false, 'message' => $this->defaultMessage(), 'updated_at' => null];
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
