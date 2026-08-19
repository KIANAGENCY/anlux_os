<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendOrderStatusEmailJob;
use App\Services\OrderEmailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class OrderEmailServiceTest extends TestCase
{
    public function test_send_for_status_dispatches_background_job(): void
    {
        Queue::fake();

        app(OrderEmailService::class)->sendForStatus(55, 'Terminado');

        Queue::assertPushed(SendOrderStatusEmailJob::class, function (SendOrderStatusEmailJob $job): bool {
            return $job->idOrdenC === 55 && $job->estatus === 'Terminado';
        });
    }

    public function test_queue_for_status_with_result_returns_honest_queued_state(): void
    {
        Queue::fake();

        $result = app(OrderEmailService::class)->queueForStatusWithResult(
            55,
            'Recepción',
            [
                'status' => 'verified',
                'email' => 'cliente@example.test',
                'message' => 'Direccion confirmada.',
            ],
            [
                'folio' => 'OS-2026-055',
                'correo' => 'cliente@example.test',
                'nombre_cliente' => 'Cliente',
                'equipos' => [],
            ]
        );

        $this->assertFalse($result['sent']);
        $this->assertSame('queued_confirmed', $result['status']);
        Queue::assertPushed(SendOrderStatusEmailJob::class, function (SendOrderStatusEmailJob $job): bool {
            return $job->idOrdenC === 55
                && $job->estatus === 'Recepción'
                && ($job->probeResult['status'] ?? null) === 'verified';
        });
    }

    public function test_send_for_status_with_result_marks_verified_recipient_as_confirmed(): void
    {
        Mail::fake();

        $result = app(OrderEmailService::class)->sendForStatusWithResult(
            99,
            'Terminado',
            [
                'status' => 'verified',
                'email' => 'cliente@example.test',
                'message' => 'Dirección confirmada.',
            ],
            [
                'folio' => 'OS-2026-099',
                'correo' => 'cliente@example.test',
                'nombre_cliente' => 'Cliente',
                'equipos' => [],
            ]
        );

        $this->assertTrue($result['sent']);
        $this->assertSame('confirmed', $result['status']);
        $this->assertStringContainsString('confirmado', mb_strtolower($result['message'], 'UTF-8'));
    }

    public function test_send_for_status_with_result_rejects_invalid_recipient_before_sending(): void
    {
        Mail::fake();

        $result = app(OrderEmailService::class)->sendForStatusWithResult(
            99,
            'Terminado',
            [
                'status' => 'rejected',
                'email' => 'cliente@example.test',
                'message' => 'Dirección rechazada.',
            ],
            [
                'folio' => 'OS-2026-099',
                'correo' => 'cliente@example.test',
                'nombre_cliente' => 'Cliente',
                'equipos' => [],
            ]
        );

        $this->assertFalse($result['sent']);
        $this->assertSame('rejected', $result['status']);
        Mail::assertNothingSent();
    }
}
