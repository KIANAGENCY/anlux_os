<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OrderWhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class WhatsappWebhookController extends Controller
{
    public function __construct(
        private readonly OrderWhatsappService $orderWhatsapp
    ) {}

    public function verify(Request $request): Response
    {
        $mode = trim((string) $request->query('hub_mode', ''));

        // Abrir la URL en el navegador (sin parámetros de Meta) no es un error del servidor.
        if ($mode === '') {
            return response(
                'Webhook de WhatsApp activo. Verifícalo desde Meta (Callback URL + Verify token), no abriendo este enlace a mano.',
                200,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $challenge = $this->orderWhatsapp->verifyWebhook(
            $mode,
            trim((string) $request->query('hub_verify_token', '')),
            (string) $request->query('hub_challenge', '')
        );

        abort_if($challenge === null, 403);

        return response($challenge, 200, ['Content-Type' => 'text/plain']);
    }

    public function receive(Request $request): JsonResponse
    {
        if (! $this->signatureValid($request)) {
            $reason = trim((string) $request->header('X-Hub-Signature-256', '')) === ''
                ? 'missing_signature_header'
                : 'invalid_signature';
            Log::channel('anlux_ops')->warning('wa_webhook_signature_rejected', [
                'reason' => $reason,
                'has_app_secret' => trim((string) config('services.whatsapp.app_secret', '')) !== '',
            ]);

            return response()->json(['success' => false], 403);
        }

        $payload = $request->all();
        if (! is_array($payload) || $payload === []) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (is_array($payload)) {
            $fields = $this->webhookFields($payload);
            $inboundCount = $this->countInboundMessages($payload);
            Log::channel('anlux_ops')->info('wa_webhook_post', [
                'fields' => $fields,
                'inbound_messages' => $inboundCount,
                'statuses' => $this->countStatuses($payload),
            ]);
            $this->orderWhatsapp->handleWebhook($payload);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function countInboundMessages(array $payload): int
    {
        $count = 0;
        foreach (($payload['entry'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach (($entry['changes'] ?? []) as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = $change['value'] ?? [];
                if (is_array($value) && is_array($value['messages'] ?? null)) {
                    $count += count($value['messages']);
                }
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function webhookFields(array $payload): array
    {
        $fields = [];
        foreach (($payload['entry'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach (($entry['changes'] ?? []) as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $field = trim((string) ($change['field'] ?? ''));
                if ($field !== '') {
                    $fields[] = $field;
                }
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function countStatuses(array $payload): int
    {
        $count = 0;
        foreach (($payload['entry'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach (($entry['changes'] ?? []) as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = $change['value'] ?? [];
                if (is_array($value) && is_array($value['statuses'] ?? null)) {
                    $count += count($value['statuses']);
                }
            }
        }

        return $count;
    }

    /**
     * Valida la firma X-Hub-Signature-256 de Meta sobre el cuerpo crudo.
     * Si no hay WHATSAPP_APP_SECRET configurado, no se valida (compatibilidad).
     */
    private function signatureValid(Request $request): bool
    {
        if (! filter_var(config('services.whatsapp.webhook_verify_signature', true), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        $appSecret = $this->normalizedAppSecret();
        if ($appSecret === '') {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $rawBody = $request->getContent();
        if ($rawBody === '' && $request->header('Content-Length')) {
            $rawBody = (string) file_get_contents('php://input');
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $header);
    }

    private function normalizedAppSecret(): string
    {
        $secret = trim((string) config('services.whatsapp.app_secret', ''));

        return trim($secret, " \t\n\r\0\x0B\"'");
    }
}
