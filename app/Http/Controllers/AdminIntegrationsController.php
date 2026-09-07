<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\IntegrationSettingsService;
use App\Services\SecurityActivityLogger;
use App\Support\WhatsappPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdminIntegrationsController extends Controller
{
    public function __construct(
        private readonly IntegrationSettingsService $settings,
        private readonly SecurityActivityLogger $securityLog,
    ) {}

    public function index(): View
    {
        $this->guardImpersonation();

        return view('admin.integraciones', [
            'pageTitle' => 'Dominio y comunicaciones - Anlux',
            'nav_admin_activo' => 'integraciones',
            'settings' => $this->settings->publicSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->guardImpersonation();
        $validated = $request->validate([
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'mail.enabled' => ['required', 'boolean'],
            'mail.host' => ['nullable', 'string', 'max:255'],
            'mail.port' => ['nullable', 'integer', 'between:1,65535'],
            'mail.scheme' => ['nullable', Rule::in(['', 'smtp', 'smtps'])],
            'mail.username' => ['nullable', 'string', 'max:255'],
            'mail.password' => ['nullable', 'string', 'max:1000'],
            'mail.remove_password' => ['nullable', 'boolean'],
            'mail.from_address' => ['nullable', 'email:rfc', 'max:255'],
            'mail.from_name' => ['nullable', 'string', 'max:120'],
            'whatsapp.enabled' => ['required', 'boolean'],
            'whatsapp.base_url' => ['required', 'url:https', 'max:255'],
            'whatsapp.graph_version' => ['required', 'regex:/^v\d+\.\d+$/', 'max:20'],
            'whatsapp.phone_number_id' => ['nullable', 'regex:/^\d+$/', 'max:40'],
            'whatsapp.access_token' => ['nullable', 'string', 'max:3000'],
            'whatsapp.verify_token' => ['nullable', 'string', 'max:255'],
            'whatsapp.app_secret' => ['nullable', 'string', 'max:255'],
            'whatsapp.remove_access_token' => ['nullable', 'boolean'],
            'whatsapp.remove_verify_token' => ['nullable', 'boolean'],
            'whatsapp.remove_app_secret' => ['nullable', 'boolean'],
            'whatsapp.webhook_verify_signature' => ['required', 'boolean'],
            'whatsapp.language' => ['required', 'regex:/^[a-z]{2}_[A-Z]{2}$/', 'max:10'],
            'whatsapp.default_country_code' => ['required', 'regex:/^\d{1,4}$/'],
            'whatsapp.template_include_document' => ['required', 'boolean'],
            'whatsapp.templates.recepcion' => ['nullable', 'regex:/^[a-z0-9_]+$/', 'max:512'],
            'whatsapp.templates.terminado' => ['nullable', 'regex:/^[a-z0-9_]+$/', 'max:512'],
            'whatsapp.templates.entregado' => ['nullable', 'regex:/^[a-z0-9_]+$/', 'max:512'],
        ]);

        if (app()->environment('production') && parse_url((string) $validated['app_url'], PHP_URL_SCHEME) !== 'https') {
            throw ValidationException::withMessages(['app_url' => 'En producción la URL canónica debe usar HTTPS.']);
        }

        $current = $this->settings->publicSettings();
        $mailEnabled = filter_var($validated['mail']['enabled'], FILTER_VALIDATE_BOOL);
        if ($mailEnabled) {
            foreach (['host', 'port', 'from_address', 'from_name'] as $field) {
                if (trim((string) ($validated['mail'][$field] ?? '')) === '') {
                    throw ValidationException::withMessages(['mail.'.$field => 'Este campo es obligatorio al activar SMTP.']);
                }
            }
            $mailPasswordAvailable = trim((string) ($validated['mail']['password'] ?? '')) !== ''
                || ((bool) ($current['mail']['has_password'] ?? false) && ! filter_var($validated['mail']['remove_password'] ?? false, FILTER_VALIDATE_BOOL));
            if (trim((string) ($validated['mail']['username'] ?? '')) !== '' && ! $mailPasswordAvailable) {
                throw ValidationException::withMessages(['mail.password' => 'La contraseña es obligatoria cuando SMTP usa un usuario.']);
            }
        }

        $waEnabled = filter_var($validated['whatsapp']['enabled'], FILTER_VALIDATE_BOOL);
        if ($waEnabled) {
            foreach (['phone_number_id'] as $field) {
                if (trim((string) ($validated['whatsapp'][$field] ?? '')) === '') {
                    throw ValidationException::withMessages(['whatsapp.'.$field => 'Este campo es obligatorio al activar WhatsApp.']);
                }
            }
            foreach (['recepcion', 'terminado', 'entregado'] as $status) {
                if (trim((string) ($validated['whatsapp']['templates'][$status] ?? '')) === '') {
                    throw ValidationException::withMessages(['whatsapp.templates.'.$status => 'Configura las tres plantillas antes de activar WhatsApp.']);
                }
            }
            $accessTokenAvailable = trim((string) ($validated['whatsapp']['access_token'] ?? '')) !== ''
                || ((bool) ($current['whatsapp']['has_access_token'] ?? false) && ! filter_var($validated['whatsapp']['remove_access_token'] ?? false, FILTER_VALIDATE_BOOL));
            if (! $accessTokenAvailable) {
                throw ValidationException::withMessages(['whatsapp.access_token' => 'El access token es obligatorio al activar WhatsApp.']);
            }
            $verifyTokenAvailable = trim((string) ($validated['whatsapp']['verify_token'] ?? '')) !== ''
                || ((bool) ($current['whatsapp']['has_verify_token'] ?? false) && ! filter_var($validated['whatsapp']['remove_verify_token'] ?? false, FILTER_VALIDATE_BOOL));
            if (! $verifyTokenAvailable) {
                throw ValidationException::withMessages(['whatsapp.verify_token' => 'El verify token es obligatorio al activar WhatsApp.']);
            }
            $verifySignature = filter_var($validated['whatsapp']['webhook_verify_signature'], FILTER_VALIDATE_BOOL);
            $appSecretAvailable = trim((string) ($validated['whatsapp']['app_secret'] ?? '')) !== ''
                || ((bool) ($current['whatsapp']['has_app_secret'] ?? false) && ! filter_var($validated['whatsapp']['remove_app_secret'] ?? false, FILTER_VALIDATE_BOOL));
            if ($verifySignature && ! $appSecretAvailable) {
                throw ValidationException::withMessages(['whatsapp.app_secret' => 'El app secret es obligatorio para validar la firma del webhook.']);
            }
        }

        $validated['mail']['enabled'] = $mailEnabled;
        $validated['whatsapp']['enabled'] = $waEnabled;
        $this->settings->update($validated);
        try {
            Mail::purge('smtp');
            Artisan::call('queue:restart');
        } catch (\Throwable) {
            // queue:listen reloads naturally; a missing cache driver must not undo saved settings.
        }
        $this->securityLog->log('integraciones_actualizadas', 'warning', 'Configuración de dominio/comunicaciones actualizada por un administrador.');

        return back()->with('status', 'Configuración guardada y aplicada.');
    }

    public function testMail(Request $request): RedirectResponse
    {
        $this->guardImpersonation();
        $validated = $request->validate(['test_email' => ['required', 'email:rfc', 'max:255']]);
        $this->settings->apply();

        try {
            Mail::purge('smtp');
            Mail::mailer('smtp')->raw(
                'Esta es una prueba de configuración SMTP enviada desde el panel Anlux.',
                static fn ($message) => $message->to($validated['test_email'])->subject('Prueba SMTP de Anlux')
            );
            $this->settings->recordTest('mail', 'tested', 'Correo de prueba enviado correctamente.');
            $this->securityLog->log('integraciones_test_mail', 'info', 'Prueba SMTP completada.');

            return back()->with('status', 'Correo de prueba enviado a '.$validated['test_email'].'.');
        } catch (\Throwable $e) {
            report($e);
            $this->settings->recordTest('mail', 'error', $this->safeError($e));
            $this->securityLog->log('integraciones_test_mail', 'warning', 'Prueba SMTP fallida.');

            return back()->with('error', 'No se pudo enviar el correo: '.$this->safeError($e));
        }
    }

    public function testWhatsapp(Request $request): RedirectResponse
    {
        $this->guardImpersonation();
        $validated = $request->validate([
            'test_phone' => ['nullable', 'string', 'max:30'],
            'test_status' => ['nullable', Rule::in(['recepcion', 'terminado', 'entregado'])],
        ]);
        $this->settings->apply();

        $base = rtrim((string) config('services.whatsapp.base_url'), '/');
        $version = trim((string) config('services.whatsapp.graph_version'));
        $phoneId = trim((string) config('services.whatsapp.phone_number_id'));
        $token = trim((string) config('services.whatsapp.access_token'));
        if ($phoneId === '' || $token === '') {
            return back()->with('error', 'Faltan Phone Number ID o access token.');
        }

        try {
            $probe = Http::withToken($token)->timeout(30)
                ->get($base.'/'.$version.'/'.$phoneId, ['fields' => 'display_phone_number,verified_name,quality_rating']);
            if (! $probe->successful()) {
                throw new \RuntimeException('Meta respondió HTTP '.$probe->status().'.');
            }

            $phone = WhatsappPhone::normalize($validated['test_phone'] ?? '');
            if ($phone !== '') {
                $status = (string) ($validated['test_status'] ?? 'recepcion');
                $template = trim((string) config('services.whatsapp.templates.'.$status));
                if ($template === '') {
                    throw new \RuntimeException('La plantilla de prueba no está configurada.');
                }
                $send = Http::withToken($token)->timeout(60)->post($base.'/'.$version.'/'.$phoneId.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => [
                        'name' => $template,
                        'language' => ['code' => (string) config('services.whatsapp.language', 'es_MX')],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => 'PRUEBA'],
                                ['type' => 'text', 'text' => ucfirst($status)],
                            ],
                        ]],
                    ],
                ]);
                if (! $send->successful()) {
                    throw new \RuntimeException('Meta rechazó el mensaje de prueba (HTTP '.$send->status().').');
                }
            }

            $this->settings->recordTest('whatsapp', 'tested', $phone !== '' ? 'Cuenta verificada y mensaje de prueba aceptado.' : 'Cuenta de WhatsApp verificada.');
            $this->securityLog->log('integraciones_test_whatsapp', 'info', 'Conexión WhatsApp verificada'.($phone !== '' ? ' y prueba enviada.' : '.'));

            return back()->with('status', $phone !== '' ? 'WhatsApp verificado y mensaje de prueba enviado.' : 'Conexión con WhatsApp Cloud verificada.');
        } catch (\Throwable $e) {
            report($e);
            $this->settings->recordTest('whatsapp', 'error', $this->safeError($e));
            $this->securityLog->log('integraciones_test_whatsapp', 'warning', 'Prueba WhatsApp fallida.');

            return back()->with('error', 'No se pudo verificar WhatsApp: '.$this->safeError($e));
        }
    }

    private function guardImpersonation(): void
    {
        abort_if((bool) session('anlux_impersonating', false), 403, 'Sal de la cuenta impersonada para cambiar integraciones.');
    }

    private function safeError(\Throwable $e): string
    {
        $message = preg_replace('/(Bearer|token|password|secret)[^\\s,;]*/i', '$1 [oculto]', $e->getMessage()) ?? '';

        return mb_substr(trim($message), 0, 240);
    }
}
