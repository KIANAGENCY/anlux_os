<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

final class IntegrationSettingsService
{
    private const SETTING_KEY = 'runtime_integrations';

    /** @return array<string, mixed> */
    public function stored(): array
    {
        try {
            if (! Schema::hasTable('anlux_settings')) {
                return [];
            }

            $content = DB::table('anlux_settings')
                ->where('setting_key', self::SETTING_KEY)
                ->value('content');
            $decoded = json_decode((string) $content, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    public function effective(): array
    {
        $stored = $this->stored();

        return [
            'app_url' => trim((string) ($stored['app_url'] ?? config('app.url', ''))),
            'mail' => array_merge($this->mailDefaults(), is_array($stored['mail'] ?? null) ? $stored['mail'] : []),
            'whatsapp' => array_merge(
                $this->whatsappDefaults(),
                is_array($stored['whatsapp'] ?? null) ? $stored['whatsapp'] : []
            ),
            'tests' => is_array($stored['tests'] ?? null) ? $stored['tests'] : [],
            'source' => $stored === [] ? 'env' : 'panel',
            'updated_at' => $stored['updated_at'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function publicSettings(): array
    {
        $settings = $this->effective();
        $mail = $settings['mail'];
        $whatsapp = $settings['whatsapp'];

        unset($mail['password_sealed'], $whatsapp['access_token_sealed'], $whatsapp['verify_token_sealed'], $whatsapp['app_secret_sealed']);

        $mail['has_password'] = $this->secret($settings['mail']['password_sealed'] ?? null) !== '';
        $whatsapp['has_access_token'] = $this->secret($settings['whatsapp']['access_token_sealed'] ?? null) !== '';
        $whatsapp['has_verify_token'] = $this->secret($settings['whatsapp']['verify_token_sealed'] ?? null) !== '';
        $whatsapp['has_app_secret'] = $this->secret($settings['whatsapp']['app_secret_sealed'] ?? null) !== '';

        return [
            'app_url' => $settings['app_url'],
            'mail' => $mail,
            'whatsapp' => $whatsapp,
            'source' => $settings['source'],
            'updated_at' => $settings['updated_at'],
            'tests' => $settings['tests'],
        ];
    }

    /**
     * Secret keys may be omitted/blank to preserve the stored value.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(array $input): void
    {
        if (! Schema::hasTable('anlux_settings')) {
            throw new \RuntimeException('Falta la tabla anlux_settings. Ejecuta las migraciones pendientes.');
        }

        $current = $this->stored();
        $mailCurrent = is_array($current['mail'] ?? null) ? $current['mail'] : [];
        $waCurrent = is_array($current['whatsapp'] ?? null) ? $current['whatsapp'] : [];
        $mailInput = is_array($input['mail'] ?? null) ? $input['mail'] : [];
        $waInput = is_array($input['whatsapp'] ?? null) ? $input['whatsapp'] : [];

        $mail = array_merge($mailCurrent, $mailInput);
        $whatsapp = array_merge($waCurrent, $waInput);
        if (array_key_exists('enabled', $waInput)) {
            $whatsapp['notifications_enabled'] = (bool) $waInput['enabled'];
        }

        $this->applySecret($mail, $mailCurrent, 'password_sealed', $mailInput['password'] ?? null, (bool) ($mailInput['remove_password'] ?? false));
        $this->applySecret($whatsapp, $waCurrent, 'access_token_sealed', $waInput['access_token'] ?? null, (bool) ($waInput['remove_access_token'] ?? false));
        $this->applySecret($whatsapp, $waCurrent, 'verify_token_sealed', $waInput['verify_token'] ?? null, (bool) ($waInput['remove_verify_token'] ?? false));
        $this->applySecret($whatsapp, $waCurrent, 'app_secret_sealed', $waInput['app_secret'] ?? null, (bool) ($waInput['remove_app_secret'] ?? false));

        foreach (['password', 'remove_password'] as $key) {
            unset($mail[$key]);
        }
        foreach (['access_token', 'verify_token', 'app_secret', 'remove_access_token', 'remove_verify_token', 'remove_app_secret'] as $key) {
            unset($whatsapp[$key]);
        }

        $now = now();
        DB::table('anlux_settings')->updateOrInsert(
            ['setting_key' => self::SETTING_KEY],
            [
                'content' => json_encode([
                    'app_url' => rtrim(trim((string) ($input['app_url'] ?? config('app.url', ''))), '/'),
                    'mail' => $mail,
                    'whatsapp' => $whatsapp,
                    'tests' => [],
                    'updated_at' => $now->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->apply();
    }

    public function recordTest(string $group, string $status, string $message): void
    {
        if (! in_array($group, ['mail', 'whatsapp'], true) || ! Schema::hasTable('anlux_settings')) {
            return;
        }
        $stored = $this->stored();
        if ($stored === []) {
            return;
        }
        $tests = is_array($stored['tests'] ?? null) ? $stored['tests'] : [];
        $tests[$group] = [
            'status' => $status,
            'message' => mb_substr(trim($message), 0, 240),
            'tested_at' => now()->toIso8601String(),
        ];
        $stored['tests'] = $tests;
        DB::table('anlux_settings')->where('setting_key', self::SETTING_KEY)->update([
            'content' => json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function apply(): void
    {
        $settings = $this->effective();
        $appUrl = rtrim(trim((string) $settings['app_url']), '/');
        if ($appUrl !== '') {
            Config::set('app.url', $appUrl);
            URL::forceRootUrl($appUrl);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
                URL::forceScheme(strtolower($scheme));
            }
        }

        $mail = $settings['mail'];
        if ((bool) ($mail['enabled'] ?? false)) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', (string) ($mail['host'] ?? ''));
            Config::set('mail.mailers.smtp.port', (int) ($mail['port'] ?? 587));
            Config::set('mail.mailers.smtp.scheme', ($mail['scheme'] ?? '') ?: null);
            Config::set('mail.mailers.smtp.username', ($mail['username'] ?? '') ?: null);
            Config::set('mail.mailers.smtp.password', $this->secret($mail['password_sealed'] ?? null));
            Config::set('mail.from.address', (string) ($mail['from_address'] ?? ''));
            Config::set('mail.from.name', (string) ($mail['from_name'] ?? config('app.name', 'Anlux')));
        }

        $wa = $settings['whatsapp'];
        Config::set('services.whatsapp.enabled', (bool) ($wa['enabled'] ?? false));
        Config::set('anlux.whatsapp_notifications_enabled', (bool) ($wa['notifications_enabled'] ?? $wa['enabled'] ?? false));
        foreach (['base_url', 'graph_version', 'phone_number_id', 'language', 'default_country_code'] as $key) {
            Config::set('services.whatsapp.'.$key, $wa[$key] ?? null);
        }
        foreach (['template_include_document', 'webhook_verify_signature'] as $key) {
            Config::set('services.whatsapp.'.$key, (bool) ($wa[$key] ?? false));
        }
        Config::set('services.whatsapp.access_token', $this->secret($wa['access_token_sealed'] ?? null));
        Config::set('services.whatsapp.verify_token', $this->secret($wa['verify_token_sealed'] ?? null));
        Config::set('services.whatsapp.app_secret', $this->secret($wa['app_secret_sealed'] ?? null));
        Config::set('services.whatsapp.templates', is_array($wa['templates'] ?? null) ? $wa['templates'] : []);
    }

    public function revealForRuntime(string $group, string $key): string
    {
        $settings = $this->effective();

        return $this->secret($settings[$group][$key] ?? null);
    }

    /** @return array<string, mixed> */
    private function mailDefaults(): array
    {
        $password = trim((string) config('mail.mailers.smtp.password', ''));

        return [
            'enabled' => config('mail.default') === 'smtp',
            'host' => (string) config('mail.mailers.smtp.host', ''),
            'port' => (int) config('mail.mailers.smtp.port', 587),
            'scheme' => (string) config('mail.mailers.smtp.scheme', ''),
            'username' => (string) config('mail.mailers.smtp.username', ''),
            'password_sealed' => $password,
            'from_address' => (string) config('mail.from.address', ''),
            'from_name' => (string) config('mail.from.name', 'Anlux'),
        ];
    }

    /** @return array<string, mixed> */
    private function whatsappDefaults(): array
    {
        return [
            'enabled' => (bool) config('services.whatsapp.enabled', false),
            'notifications_enabled' => (bool) config('anlux.whatsapp_notifications_enabled', false),
            'base_url' => (string) config('services.whatsapp.base_url', 'https://graph.facebook.com'),
            'graph_version' => (string) config('services.whatsapp.graph_version', 'v20.0'),
            'phone_number_id' => (string) config('services.whatsapp.phone_number_id', ''),
            'access_token_sealed' => (string) config('services.whatsapp.access_token', ''),
            'verify_token_sealed' => (string) config('services.whatsapp.verify_token', ''),
            'app_secret_sealed' => (string) config('services.whatsapp.app_secret', ''),
            'webhook_verify_signature' => (bool) config('services.whatsapp.webhook_verify_signature', true),
            'language' => (string) config('services.whatsapp.language', 'es_MX'),
            'default_country_code' => (string) config('services.whatsapp.default_country_code', '52'),
            'template_include_document' => (bool) config('services.whatsapp.template_include_document', true),
            'templates' => (array) config('services.whatsapp.templates', []),
        ];
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $current
     */
    private function applySecret(array &$target, array $current, string $key, mixed $replacement, bool $remove): void
    {
        if ($remove) {
            $target[$key] = null;

            return;
        }
        $replacement = trim((string) $replacement);
        if ($replacement !== '') {
            $target[$key] = Crypt::encryptString($replacement);

            return;
        }
        if (array_key_exists($key, $current)) {
            $target[$key] = $current[$key];
        } else {
            unset($target[$key]);
        }
    }

    private function secret(mixed $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            // Values inherited from .env are plaintext; database replacements are encrypted.
            return $stored;
        }
    }
}
