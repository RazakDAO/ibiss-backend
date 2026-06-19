<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private string $telephone,
        private string $message
    ) {}

    public function handle(SmsService $sms): void
    {
        $sms->envoyer($this->telephone, $this->message);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Job SMS échoué définitivement', [
            'telephone' => $this->telephone,
            'erreur'    => $e->getMessage(),
        ]);
    }
}
