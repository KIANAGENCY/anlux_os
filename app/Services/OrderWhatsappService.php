<?php



declare(strict_types=1);



namespace App\Services;



use App\Http\Controllers\OrderPdfController;

use App\Jobs\SendOrderWhatsappJob;

use App\Support\WhatsappPhone;

use App\Support\OrderStatus;

use Illuminate\Http\Client\PendingRequest;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\URL;



final class OrderWhatsappService

{

    public function __construct(

        private readonly ExactoVaultService $vault

    ) {}



    public function sendForStatus(int $idOrdenC, string $estatus): void

    {

        $this->queueForStatusWithResult($idOrdenC, $estatus);

    }



    /**

     * @param  array<string, mixed>|null  $orderPayload

     * @return array{sent: bool, status: string, message: string, phone: string, notification_id: int|null}

     */

    public function queueForStatusWithResult(int $idOrdenC, string $estatus, ?array $orderPayload = null, bool $sendNow = false, bool $force = false): array

    {

        $estatusCanon = OrderStatus::map($estatus);

        if (! in_array($estatusCanon, ['Recepción', 'Terminado', 'Entregado'], true)) {

            return [

                'sent' => false,

                'status' => 'skipped',

                'message' => 'Este estatus no envía WhatsApp automático.',

                'phone' => '',

                'notification_id' => null,

            ];

        }



        $payload = is_array($orderPayload) && $orderPayload !== []

            ? $orderPayload

            : $this->buildOrderPayload($idOrdenC, $estatusCanon);

        $telefonoRaw = trim((string) ($payload['telefono'] ?? ''));

        $phone = $this->normalizeWhatsappPhone($telefonoRaw !== '' ? $telefonoRaw : null);

        if ($phone === '') {

            $message = $telefonoRaw === ''

                ? 'WhatsApp no enviado. No se registró un teléfono válido para enviar la notificación.'

                : 'WhatsApp no enviado. El teléfono registrado no es válido para WhatsApp.';



            return [

                'sent' => false,

                'status' => 'rejected',

                'message' => $message,

                'phone' => '',

                'notification_id' => null,

            ];

        }



        if (! $this->enabled()) {

            return [

                'sent' => false,

                'status' => 'skipped',

                'message' => 'WhatsApp Cloud API no está configurado.',

                'phone' => $phone,

                'notification_id' => null,

            ];

        }



        $templateName = $this->templateNameForStatus($estatusCanon);

        if ($templateName === '') {

            return [

                'sent' => false,

                'status' => 'rejected',

                'message' => 'WhatsApp no enviado. Falta configurar la plantilla para este estatus.',

                'phone' => $phone,

                'notification_id' => null,

            ];

        }



        $existing = DB::table('order_whatsapp_notifications')

            ->where('id_orden_c', $idOrdenC)

            ->where('estatus', $estatusCanon)

            ->whereIn('status', ['queued', 'accepted', 'delivered', 'read'])

            ->orderByDesc('id')

            ->first();



        if ($existing && ! $force) {

            $existingStatus = trim((string) ($existing->status ?? ''));

            $existingId = (int) ($existing->id ?? 0);



            if ($existingStatus === 'queued' && $sendNow && $existingId > 0) {

                $sendResult = $this->sendQueuedNotification($existingId);

                $sendResult['notification_id'] = $sendResult['notification_id'] ?? $existingId;

                if (trim((string) ($sendResult['phone'] ?? '')) === '') {

                    $sendResult['phone'] = $phone;

                }



                return $sendResult;

            }



            if (in_array($existingStatus, ['accepted', 'delivered', 'read'], true)) {

                return [

                    'sent' => false,

                    'status' => 'duplicate',

                    'message' => 'WhatsApp ya enviado para este estatus.',

                    'phone' => $phone,

                    'notification_id' => $existingId,

                ];

            }



            if ($existingStatus === 'queued' && $existingId > 0) {

                return [

                    'sent' => false,

                    'status' => 'queued',

                    'message' => 'WhatsApp en cola para '.$phone.' (con PDF de la orden).',

                    'phone' => $phone,

                    'notification_id' => $existingId,

                ];

            }

        }



        $notificationId = $this->createQueuedNotification($idOrdenC, $estatusCanon, $phone, $templateName, $payload);

        if ($sendNow) {
            $sendResult = $this->sendQueuedNotification($notificationId);
            $sendResult['notification_id'] = $sendResult['notification_id'] ?? $notificationId;
            if (trim((string) ($sendResult['phone'] ?? '')) === '') {
                $sendResult['phone'] = $phone;
            }

            return $sendResult;
        }

        try {
            SendOrderWhatsappJob::dispatch($notificationId)->onConnection('database');
        } catch (\Throwable $e) {
            Log::channel('exacto_ops')->warning('order_whatsapp_dispatch_failed', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
        }



        return [

            'sent' => false,

            'status' => 'queued',

            'message' => 'WhatsApp en cola para '.$phone.' (con PDF de la orden).',

            'phone' => $phone,

            'notification_id' => $notificationId,

        ];

    }



    /**

     * @return array{sent: bool, status: string, message: string, phone: string, notification_id: int|null}

     */

    public function sendQueuedNotification(int $notificationId): array

    {

        $row = DB::table('order_whatsapp_notifications')->where('id', $notificationId)->first();

        if (! $row) {

            return [

                'sent' => false,

                'status' => 'failed',

                'message' => 'Notificación de WhatsApp no encontrada.',

                'phone' => '',

                'notification_id' => null,

            ];

        }

        if (! $this->enabled()) {

            $this->markNotificationFailed($notificationId, 'WhatsApp Cloud API no está configurado.');



            return [

                'sent' => false,

                'status' => 'failed',

                'message' => 'WhatsApp Cloud API no está configurado.',

                'phone' => (string) ($row->telefono ?? ''),

                'notification_id' => $notificationId,

            ];

        }



        try {

            $payload = json_decode((string) ($row->payload_json ?? '[]'), true);

            if (! is_array($payload)) {

                $payload = [];

            }



            $idOrdenC = (int) ($row->id_orden_c ?? $payload['id_orden_c'] ?? 0);

            $mediaId = null;

            $pdfFilename = null;

            $documentLink = null;



            if ($this->shouldAttachDocument()) {

                if ($idOrdenC <= 0) {

                    $this->markNotificationFailed($notificationId, 'WhatsApp no enviado. No se encontró la orden para generar el PDF.');



                    return [

                        'sent' => false,

                        'status' => 'failed',

                        'message' => 'WhatsApp no enviado. No se encontró la orden para generar el PDF.',

                        'phone' => (string) ($row->telefono ?? ''),

                        'notification_id' => $notificationId,

                    ];

                }



                $pdfFilename = $this->pdfFilenameForPayload($payload, $idOrdenC);

                if ($this->documentDeliveryUsesLink()) {

                    if ($this->resolveOrderPdfBinary($idOrdenC, $payload) === null) {

                        $this->markNotificationFailed($notificationId, 'WhatsApp no enviado. No se pudo generar el PDF de la orden.');



                        return [

                            'sent' => false,

                            'status' => 'failed',

                            'message' => 'WhatsApp no enviado. No se pudo generar el PDF de la orden.',

                            'phone' => (string) ($row->telefono ?? ''),

                            'notification_id' => $notificationId,

                        ];

                    }

                    $documentLink = $this->signedPdfUrlForOrder($idOrdenC, $notificationId, $payload);

                } else {

                    $pdfBinary = $this->resolveOrderPdfBinary($idOrdenC, $payload);

                    if ($pdfBinary === null) {

                        $this->markNotificationFailed($notificationId, 'WhatsApp no enviado. No se pudo generar el PDF de la orden.');



                        return [

                            'sent' => false,

                            'status' => 'failed',

                            'message' => 'WhatsApp no enviado. No se pudo generar el PDF de la orden.',

                            'phone' => (string) ($row->telefono ?? ''),

                            'notification_id' => $notificationId,

                        ];

                    }



                    try {
                        $mediaId = $this->uploadPdfMedia($pdfBinary, $pdfFilename);
                    } catch (\Throwable $uploadError) {
                        if ($this->shouldFallbackWithoutDocument()) {
                            Log::channel('exacto_ops')->warning('order_whatsapp_pdf_upload_fallback', [
                                'notification_id' => $notificationId,
                                'filename' => $pdfFilename,
                                'pdf_bytes' => strlen($pdfBinary),
                                'error' => $uploadError->getMessage(),
                            ]);
                            $mediaId = null;
                            $pdfFilename = null;
                        } elseif ($this->resolveOrderPdfBinary($idOrdenC, $payload) !== null) {
                            Log::channel('exacto_ops')->warning('order_whatsapp_pdf_upload_link_fallback', [
                                'notification_id' => $notificationId,
                                'error' => $uploadError->getMessage(),
                            ]);
                            $documentLink = $this->signedPdfUrlForOrder($idOrdenC, $notificationId, $payload);
                            $mediaId = null;
                        } else {
                            throw $uploadError;
                        }
                    }

                    if ($mediaId === null && $documentLink === null && ! $this->shouldFallbackWithoutDocument()) {

                        $this->markNotificationFailed($notificationId, 'WhatsApp no enviado. Meta no aceptó la subida del PDF.');



                        return [

                            'sent' => false,

                            'status' => 'failed',

                            'message' => 'WhatsApp no enviado. Meta no aceptó la subida del PDF.',

                            'phone' => (string) ($row->telefono ?? ''),

                            'notification_id' => $notificationId,

                        ];

                    }
                }

            }



            $requestPayload = $this->buildCloudApiPayload(

                (string) ($row->telefono ?? ''),

                (string) ($row->template_name ?? ''),

                $payload,

                (string) ($row->estatus ?? ''),

                $mediaId,

                $pdfFilename,

                $documentLink

            );



            $response = $this->whatsappHttp()

                ->acceptJson()

                ->post($this->messagesUrl(), $requestPayload);



            $body = $response->json();

            if (! is_array($body)) {

                $body = [];

            }



            if ($response->successful() && isset($body['messages'][0]['id'])) {

                $messageId = (string) $body['messages'][0]['id'];

                $acceptedMessage = $mediaId !== null

                    ? 'WhatsApp aceptado por Meta (mensaje con PDF de la orden).'

                    : ($documentLink !== null

                        ? 'WhatsApp aceptado por Meta (PDF por enlace).'

                        : ($this->shouldAttachDocument()

                            ? 'WhatsApp aceptado por Meta (sin PDF; la subida a Meta falló).'

                            : 'WhatsApp aceptado por Meta.'));

                DB::table('order_whatsapp_notifications')

                    ->where('id', $notificationId)

                    ->update([

                        'provider_message_id' => $messageId,

                        'status' => 'accepted',

                        'message' => $acceptedMessage,

                        'response_json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                        'sent_at' => now(),

                        'updated_at' => now(),

                    ]);

                return [

                    'sent' => true,

                    'status' => 'accepted',

                    'message' => $acceptedMessage,

                    'phone' => (string) ($row->telefono ?? ''),

                    'notification_id' => $notificationId,

                ];

            }



            $message = $this->responseErrorMessage($body) ?: 'WhatsApp no enviado. Meta rechazó la solicitud.';

            $this->markNotificationFailed($notificationId, $message, $body);



            return [

                'sent' => false,

                'status' => 'failed',

                'message' => $message,

                'phone' => (string) ($row->telefono ?? ''),

                'notification_id' => $notificationId,

            ];

        } catch (\Throwable $e) {

            Log::channel('exacto_ops')->warning('order_whatsapp_failed', [

                'notification_id' => $notificationId,

                'error' => $e->getMessage(),

                'exception' => $e::class,

                'file' => $e->getFile(),

                'line' => $e->getLine(),

            ]);

            $detail = trim(substr($e->getMessage(), 0, 280));
            $userMessage = $detail !== ''
                ? 'WhatsApp no enviado. Error: '.$detail
                : 'WhatsApp no enviado. Ocurrió un error al contactar Meta.';

            $this->markNotificationFailed($notificationId, $userMessage, [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);



            return [

                'sent' => false,

                'status' => 'failed',

                'message' => $userMessage,

                'phone' => (string) ($row->telefono ?? ''),

                'notification_id' => $notificationId,

            ];

        }

    }



    public function handleWebhook(array $payload): void

    {

        foreach (($payload['entry'] ?? []) as $entry) {

            foreach (($entry['changes'] ?? []) as $change) {

                $value = $change['value'] ?? [];

                if (! is_array($value)) {

                    continue;

                }

                if (! Schema::hasTable('order_whatsapp_notifications')) {
                    continue;
                }

                foreach (($value['statuses'] ?? []) as $statusRow) {

                    if (! is_array($statusRow)) {

                        continue;

                    }



                    $providerMessageId = trim((string) ($statusRow['id'] ?? ''));

                    if ($providerMessageId === '') {

                        continue;

                    }



                    $status = trim((string) ($statusRow['status'] ?? ''));

                    $message = $this->statusMessageFromWebhook($statusRow);

                    $updates = [

                        'message' => $message,

                        'webhook_json' => json_encode($statusRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                        'updated_at' => now(),

                    ];



                    if ($status === 'delivered') {

                        $updates['status'] = 'delivered';

                        $updates['delivered_at'] = now();

                    } elseif ($status === 'read') {

                        $updates['status'] = 'read';

                        $updates['read_at'] = now();

                    } elseif ($status === 'failed') {

                        $updates['status'] = 'failed';

                        $updates['failed_at'] = now();

                    } elseif ($status === 'sent') {

                        $updates['status'] = 'accepted';

                    } else {

                        $updates['status'] = $status !== '' ? $status : 'accepted';

                    }



                    DB::table('order_whatsapp_notifications')

                        ->where('provider_message_id', $providerMessageId)

                        ->update($updates);

                }

            }

        }

    }



    public function verifyWebhook(string $mode, string $verifyToken, string $challenge): ?string

    {

        if ($mode !== 'subscribe') {

            return null;

        }



        return hash_equals((string) config('services.whatsapp.verify_token'), $verifyToken)

            ? $challenge

            : null;

    }



    /**

     * @return array{status: string, phone: string, message: string}

     */

    public function probePhone(?string $phone): array

    {

        $raw = trim((string) $phone);

        if ($raw === '') {

            return [

                'status' => 'missing',

                'phone' => '',

                'message' => 'No se registró un teléfono válido para enviar la notificación.',

            ];

        }



        $normalized = $this->normalizeWhatsappPhone($raw);

        if ($normalized === '') {

            return [

                'status' => 'invalid',

                'phone' => '',

                'message' => 'El teléfono registrado no es válido para WhatsApp.',

            ];

        }



        return [

            'status' => 'valid',

            'phone' => $normalized,

            'message' => 'Teléfono válido para WhatsApp.',

        ];

    }



    private function enabled(): bool

    {

        return (bool) config('services.whatsapp.enabled')

            && trim((string) config('services.whatsapp.phone_number_id')) !== ''

            && trim((string) config('services.whatsapp.access_token')) !== '';

    }

    private function httpTimeoutSeconds(): int

    {

        return max(30, (int) config('services.whatsapp.http_timeout', 180));

    }

    private function httpConnectTimeoutSeconds(): int

    {

        return max(10, (int) config('services.whatsapp.http_connect_timeout', 60));

    }

    private function whatsappHttp(): PendingRequest

    {

        return Http::withToken((string) config('services.whatsapp.access_token'))

            ->connectTimeout($this->httpConnectTimeoutSeconds())

            ->timeout($this->httpTimeoutSeconds());

    }



    private function shouldAttachDocument(): bool

    {

        return filter_var(config('services.whatsapp.template_include_document', true), FILTER_VALIDATE_BOOL);

    }

    private function shouldFallbackWithoutDocument(): bool

    {

        return filter_var(config('services.whatsapp.fallback_without_document', true), FILTER_VALIDATE_BOOL);

    }

    private function documentDeliveryUsesLink(): bool

    {

        return strtolower((string) config('services.whatsapp.document_delivery', 'link')) === 'link';

    }

    private function signedPdfUrlForOrder(int $idOrdenC, int $notificationId, ?array $payload = null): string

    {

        $ttlMinutes = max(60, (int) config('services.whatsapp.pdf_link_ttl_minutes', 2880));

        $params = ['id' => $idOrdenC, 'n' => $notificationId];

        $equipoIndice = (int) ($payload['equipo_indice'] ?? 0);

        if ($equipoIndice > 0) {

            $params['eq'] = $equipoIndice;

        }

        return URL::temporarySignedRoute(

            'pdf.orden.wa',

            now()->addMinutes($ttlMinutes),

            $params

        );

    }



    private function messagesUrl(): string

    {

        $base = rtrim((string) config('services.whatsapp.base_url'), '/');

        $version = trim((string) config('services.whatsapp.graph_version'));

        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));



        return $base.'/'.$version.'/'.$phoneNumberId.'/messages';

    }



    private function mediaUrl(): string

    {

        $base = rtrim((string) config('services.whatsapp.base_url'), '/');

        $version = trim((string) config('services.whatsapp.graph_version'));

        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));



        return $base.'/'.$version.'/'.$phoneNumberId.'/media';

    }



    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveOrderPdfBinary(int $idOrdenC, ?array $payload = null): ?string

    {

        try {

            $equipoIndice = (int) ($payload['equipo_indice'] ?? 0);

            $content = app(OrderPdfController::class)->renderOrderPdfBinary(

                $idOrdenC,

                true,

                $equipoIndice > 0 ? $equipoIndice : null

            );



            return $content !== '' ? $content : null;

        } catch (\Throwable $e) {

            Log::channel('exacto_ops')->warning('order_whatsapp_pdf_failed', [

                'id_orden_c' => $idOrdenC,

                'equipo_indice' => (int) ($payload['equipo_indice'] ?? 0),

                'error' => $e->getMessage(),

            ]);



            return null;

        }

    }



    /**

     * @param  array<string, mixed>  $orderPayload

     */

    private function pdfFilenameForPayload(array $orderPayload, int $idOrdenC): string

    {

        $folio = trim((string) ($orderPayload['folio'] ?? ''));

        $safeFolio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $folio);



        return ($safeFolio !== '' ? $safeFolio : 'orden_'.$idOrdenC).'.pdf';

    }



    private function uploadPdfMedia(string $pdfBinary, string $filename): ?string

    {

        try {

            $response = $this->whatsappHttp()

                ->retry(2, 3000, throw: false)

                ->attach('file', $pdfBinary, $filename, ['Content-Type' => 'application/pdf'])

                ->post($this->mediaUrl(), [

                    'messaging_product' => 'whatsapp',

                    'type' => 'application/pdf',

                ]);
        } catch (\Throwable $e) {

            Log::channel('exacto_ops')->warning('order_whatsapp_media_upload_failed', [

                'filename' => $filename,

                'pdf_bytes' => strlen($pdfBinary),

                'error' => $e->getMessage(),

            ]);

            throw $e;

        }



        $body = $response->json();

        if (! is_array($body)) {

            $body = [];

        }



        if ($response->successful() && isset($body['id'])) {

            return (string) $body['id'];

        }



        Log::channel('exacto_ops')->warning('order_whatsapp_media_upload_failed', [

            'filename' => $filename,

            'status' => $response->status(),

            'response' => $body,

        ]);



        return null;

    }



    /**

     * @param  array<string, mixed>  $orderPayload

     * @return array<string, mixed>

     */

    private function buildCloudApiPayload(

        string $phone,

        string $templateName,

        array $orderPayload,

        string $estatus,

        ?string $mediaId = null,

        ?string $pdfFilename = null,

        ?string $documentLink = null

    ): array {
        $phone = $this->normalizeWhatsappPhone($phone);
        if ($phone === '') {
            throw new \InvalidArgumentException('Teléfono inválido para plantilla de WhatsApp.');
        }

        $components = [];

        $filename = $pdfFilename !== null && $pdfFilename !== '' ? $pdfFilename : 'orden.pdf';



        if ($mediaId !== null && $mediaId !== '') {

            $components[] = [

                'type' => 'header',

                'parameters' => [

                    [

                        'type' => 'document',

                        'document' => [

                            'id' => $mediaId,

                            'filename' => $filename,

                        ],

                    ],

                ],

            ];

        } elseif ($documentLink !== null && $documentLink !== '') {

            $components[] = [

                'type' => 'header',

                'parameters' => [

                    [

                        'type' => 'document',

                        'document' => [

                            'link' => $documentLink,

                            'filename' => $filename,

                        ],

                    ],

                ],

            ];

        }



        $components[] = [

            'type' => 'body',

            'parameters' => [

                ['type' => 'text', 'text' => trim((string) ($orderPayload['folio'] ?? '')) ?: 'SIN-FOLIO'],

                ['type' => 'text', 'text' => $estatus],

            ],

        ];



        return [

            'messaging_product' => 'whatsapp',

            'to' => $phone,

            'type' => 'template',

            'template' => [

                'name' => $templateName,

                'language' => [

                    'code' => (string) config('services.whatsapp.language', 'es_MX'),

                ],

                'components' => $components,

            ],

        ];

    }



    private function createQueuedNotification(int $idOrdenC, string $estatus, string $phone, string $templateName, array $payload): int

    {

        DB::table('order_whatsapp_notifications')->insert([

            'id_orden_c' => $idOrdenC,

            'folio' => trim((string) ($payload['folio'] ?? '')) ?: null,

            'estatus' => $estatus,

            'telefono' => $phone,

            'template_name' => $templateName,

            'status' => 'queued',

            'message' => 'WhatsApp en cola (PDF de la orden).',

            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

            'queued_at' => now(),

            'created_at' => now(),

            'updated_at' => now(),

        ]);



        return (int) DB::getPdo()->lastInsertId();

    }



    private function markNotificationFailed(int $notificationId, string $message, ?array $response = null): void

    {

        DB::table('order_whatsapp_notifications')

            ->where('id', $notificationId)

            ->update([

                'status' => 'failed',

                'message' => $message,

                'response_json' => $response ? json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,

                'failed_at' => now(),

                'updated_at' => now(),

            ]);

    }



    private function responseErrorMessage(array $body): string

    {

        $error = $body['error'] ?? null;

        if (! is_array($error)) {

            return '';

        }



        $title = trim((string) ($error['message'] ?? ''));

        $details = trim((string) ($error['error_user_msg'] ?? ''));



        return trim($title.($details !== '' ? ' '.$details : ''));

    }



    private function statusMessageFromWebhook(array $statusRow): string

    {

        $status = trim((string) ($statusRow['status'] ?? ''));

        $errors = $statusRow['errors'] ?? [];

        if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {

            $errorTitle = trim((string) ($errors[0]['title'] ?? $errors[0]['message'] ?? ''));

            if ($errorTitle !== '') {

                return $errorTitle;

            }

        }



        return match ($status) {

            'delivered' => 'WhatsApp entregado.',

            'read' => 'WhatsApp leído.',

            'failed' => 'WhatsApp fallido.',

            'sent' => 'WhatsApp enviado a Meta.',

            default => 'WhatsApp actualizado.',

        };

    }



    private function templateNameForStatus(string $estatus): string

    {

        return match ($estatus) {

            'Recepción' => trim((string) config('services.whatsapp.templates.recepcion', '')),

            'Terminado' => trim((string) config('services.whatsapp.templates.terminado', '')),

            'Entregado' => trim((string) config('services.whatsapp.templates.entregado', '')),

            default => '',

        };

    }



    private function normalizeWhatsappPhone(mixed $phone): string

    {

        if (class_exists('App\\Support\\WhatsappPhone', true)) {

            return WhatsappPhone::normalize($phone);

        }



        $digits = preg_replace('/\D+/', '', trim((string) $phone)) ?? '';

        if ($digits === '' || preg_match('/^(\d)\1+$/', $digits) === 1) {

            return '';

        }

        if (strlen($digits) === 10) {

            return '521'.$digits;

        }

        if (preg_match('/^52(?:1)?(\d{10})$/', $digits, $m)) {

            return '521'.$m[1];

        }

        if (preg_match('/^1(\d{10})$/', $digits, $m)) {

            return '521'.$m[1];

        }



        return (strlen($digits) >= 11 && strlen($digits) <= 15) ? $digits : '';

    }



    /**

     * @return array<string, mixed>

     */

    private function buildOrderPayload(int $idOrdenC, string $estatus): array

    {

        $cabecera = DB::selectOne('SELECT * FROM orden_servicio_c WHERE id_orden_c = ?', [$idOrdenC]);

        if (! $cabecera) {

            throw new \RuntimeException('Orden no encontrada para envío de WhatsApp.');

        }



        $cab = (array) $cabecera;



        return [

            'id_orden_c' => $idOrdenC,

            'folio' => trim((string) ($cab['folio'] ?? '')),

            'estatus' => $estatus,

            'nombre_cliente' => $this->vault->nombreClienteReveal($cab['nombre_cliente'] ?? null),

            'telefono' => $this->vault->telefonoReveal($cab['telefono'] ?? null),

            'correo' => $this->vault->correoReveal($cab['correo'] ?? null),

        ];

    }

}
