<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\OrderWhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendOrderWhatsappJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $notificationId
    ) {
        $this->afterCommit();
    }

    public function handle(OrderWhatsappService $orderWhatsapp): void
    {
        $orderWhatsapp->sendQueuedNotification($this->notificationId);
    }
}
