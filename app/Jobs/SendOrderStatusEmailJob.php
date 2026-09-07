<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\IntegrationSettingsService;
use App\Services\OrderEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendOrderStatusEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $idOrdenC,
        public readonly string $estatus,
        public readonly ?array $probeResult = null,
        public readonly ?array $orderPayload = null
    ) {
        $this->afterCommit();
    }

    public function handle(OrderEmailService $orderEmail, IntegrationSettingsService $settings): void
    {
        $settings->apply();
        $orderEmail->sendForStatusWithResult($this->idOrdenC, $this->estatus, $this->probeResult, $this->orderPayload);
    }
}
