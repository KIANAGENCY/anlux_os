<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'enabled' => filter_var(env('WHATSAPP_CLOUD_ENABLED', false), FILTER_VALIDATE_BOOL),
        'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        /** App Secret de Meta para validar la firma X-Hub-Signature-256 del webhook (opcional pero recomendado). */
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        /** ID numerico de la app en developers.facebook.com (Settings > Basic > App ID). Para og:fb:app_id. */
        'app_id' => preg_replace('/\D+/', '', (string) env('FACEBOOK_APP_ID', env('META_APP_ID', ''))),
        /** Si false, no valida firma aunque haya app_secret (solo para depurar; no dejar en producción). */
        'webhook_verify_signature' => filter_var(env('WHATSAPP_WEBHOOK_VERIFY_SIGNATURE', true), FILTER_VALIDATE_BOOL),
        'language' => env('WHATSAPP_LANGUAGE', 'es_MX'),
        'default_country_code' => preg_replace('/\D+/', '', (string) env('WHATSAPP_DEFAULT_COUNTRY_CODE', '52')) ?: '52',
        'templates' => [
            'recepcion' => env('WHATSAPP_TEMPLATE_RECEPCION'),
            'terminado' => env('WHATSAPP_TEMPLATE_TERMINADO'),
            'entregado' => env('WHATSAPP_TEMPLATE_ENTREGADO'),
        ],
        /** Plantillas Meta con encabezado tipo Documento (PDF de la orden). */
        'template_include_document' => filter_var(env('WHATSAPP_TEMPLATE_INCLUDE_DOCUMENT', true), FILTER_VALIDATE_BOOL),
        /** link = Meta descarga PDF desde URL firmada (recomendado). upload = subir a /media. */
        'document_delivery' => in_array(strtolower((string) env('WHATSAPP_DOCUMENT_DELIVERY', 'link')), ['upload'], true)
            ? 'upload'
            : 'link',
        /** Minutos de validez del enlace firmado del PDF para Meta. */
        'pdf_link_ttl_minutes' => max(60, (int) env('WHATSAPP_PDF_LINK_TTL_MINUTES', 2880)),
        /** Si falla la subida del PDF, enviar plantilla solo con texto (body). */
        'fallback_without_document' => filter_var(env('WHATSAPP_FALLBACK_WITHOUT_DOCUMENT', true), FILTER_VALIDATE_BOOL),
        /** Segundos de espera al subir PDF y enviar a graph.facebook.com (hosting lento suele necesitar 120+). */
        'http_timeout' => max(30, (int) env('WHATSAPP_HTTP_TIMEOUT', 180)),
        'http_connect_timeout' => max(10, (int) env('WHATSAPP_HTTP_CONNECT_TIMEOUT', 60)),
    ],

];
