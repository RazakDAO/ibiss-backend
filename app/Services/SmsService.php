<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function envoyer(string $telephone, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.sms.api_key'),
                'Content-Type'  => 'application/json',
            ])->post(config('services.sms.endpoint'), [
                'to'      => $telephone,
                'message' => $message,
                'sender'  => 'Ibissé',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Échec envoi SMS', ['telephone' => $telephone, 'erreur' => $e->getMessage()]);
            return false;
        }
    }
}
