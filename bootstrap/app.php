<?php

use App\Http\Middleware\EnsureApplicationIsAvailable;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\AnluxSecurityHeadersMiddleware;
use App\Http\Middleware\AnluxUpdatePresence;
use App\Http\Middleware\LegacyRememberMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'anlux.admin' => EnsureUserIsAdmin::class,
        ]);
        // Meta/WhatsApp webhook: POST sin sesión ni CSRF (PowerShell, Postman, Meta).
        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp/cloud',
        ]);
        $middleware->web(append: [
            LegacyRememberMiddleware::class,
            AnluxUpdatePresence::class,
            EnsureApplicationIsAvailable::class,
            AnluxSecurityHeadersMiddleware::class,
            \App\Http\Middleware\PreventStaleHtmlCacheMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
