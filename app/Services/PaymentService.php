<?php

namespace App\Services;

use App\Exceptions\PaymentException;
use App\Models\Offre;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function initier(array $donnees, User $user): array
    {
        $montant   = $this->calculerMontant($donnees);
        $reference = strtoupper(substr($donnees['provider'], 0, 2)) . '-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'user_id'   => $user->id,
            'type'      => $donnees['type'],
            'amount'    => $montant,
            'currency'  => 'XOF',
            'provider'  => $donnees['provider'],
            'reference' => $reference,
            'status'    => Payment::STATUS_PENDING,
            'metadata'  => $donnees,
        ]);

        $urlPaiement = match ($donnees['provider']) {
            'orange_money' => $this->initierOrangeMoney($payment, $donnees['phone']),
            'moov_money'   => $this->initierMoovMoney($payment, $donnees['phone']),
        };

        return [
            'payment_id'   => $payment->id,
            'reference'    => $reference,
            'payment_url'  => $urlPaiement,
            'amount'       => $montant,
            'currency'     => 'XOF',
        ];
    }

    private function initierOrangeMoney(Payment $payment, string $telephone): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getOrangeToken(),
                'Content-Type'  => 'application/json',
            ])->post(config('services.orange_money.url') . '/webpayment', [
                'merchant_key' => config('services.orange_money.merchant_key'),
                'currency'     => 'OUV',
                'order_id'     => $payment->reference,
                'amount'       => $payment->amount,
                'return_url'   => config('app.frontend_url') . '/paiement/retour',
                'cancel_url'   => config('app.frontend_url') . '/paiement/annule',
                'notif_url'    => config('app.url') . '/api/v1/payments/callback?provider=orange_money',
            ]);

            if (!$response->successful()) {
                throw new PaymentException('Erreur Orange Money : ' . $response->body());
            }

            return $response->json('payment_url') ?? $response->json('data.payment_url', '#');
        } catch (\Exception $e) {
            Log::error('Échec initiation Orange Money', [
                'reference' => $payment->reference,
                'erreur'    => $e->getMessage(),
            ]);
            throw new PaymentException('Impossible d\'initier le paiement Orange Money.');
        }
    }

    private function initierMoovMoney(Payment $payment, string $telephone): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.moov_money.api_key'),
                'Content-Type'  => 'application/json',
            ])->post(config('services.moov_money.url') . '/payment/request', [
                'amount'      => $payment->amount,
                'msisdn'      => $telephone,
                'description' => 'Ibissé — ' . $payment->type,
                'reference'   => $payment->reference,
                'callback'    => config('app.url') . '/api/v1/payments/callback?provider=moov_money',
            ]);

            if (!$response->successful()) {
                throw new PaymentException('Erreur Moov Money : ' . $response->body());
            }

            return $response->json('ussd_string') ?? '#';
        } catch (\Exception $e) {
            Log::error('Échec initiation Moov Money', [
                'reference' => $payment->reference,
                'erreur'    => $e->getMessage(),
            ]);
            throw new PaymentException('Impossible d\'initier le paiement Moov Money.');
        }
    }

    private function getOrangeToken(): string
    {
        $response = Http::asForm()->post(config('services.orange_money.url') . '/token', [
            'client_id'     => config('services.orange_money.client_id'),
            'client_secret' => config('services.orange_money.client_secret'),
            'grant_type'    => 'client_credentials',
        ]);

        return $response->json('access_token', '');
    }

    public function validerHmac(Request $request, string $provider): bool
    {
        $secret    = match ($provider) {
            'orange_money' => config('services.orange_money.client_secret'),
            'moov_money'   => config('services.moov_money.api_key'),
            default        => null,
        };

        if (!$secret) {
            return false;
        }

        $headerName = match ($provider) {
            'orange_money' => 'X-Orange-Signature',
            'moov_money'   => 'X-Moov-Signature',
        };

        $signature = $request->header($headerName, '');
        $calcule   = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($calcule, $signature);
    }

    public function traiterCallback(array $payload, string $provider): Payment
    {
        $reference = $payload['order_id'] ?? $payload['reference'] ?? null;

        $payment = Payment::where('reference', $reference)->firstOrFail();

        // Idempotence
        if ($payment->estComplet()) {
            return $payment;
        }

        $statut = $this->mapperStatut($payload, $provider);
        $payment->update([
            'status'   => $statut,
            'metadata' => array_merge($payment->metadata ?? [], ['callback' => $payload]),
        ]);

        if ($statut === Payment::STATUS_COMPLETED) {
            $this->activerApresSucces($payment);
        }

        return $payment;
    }

    private function mapperStatut(array $payload, string $provider): string
    {
        $statutOperateur = match ($provider) {
            'orange_money' => $payload['status'] ?? '',
            'moov_money'   => $payload['transaction_status'] ?? '',
            default        => '',
        };

        return match (strtoupper($statutOperateur)) {
            'SUCCESS', 'SUCCESSFULL', 'SUCCESSFUL' => Payment::STATUS_COMPLETED,
            'FAILED', 'FAILURE', 'ERROR'            => Payment::STATUS_FAILED,
            default                                 => Payment::STATUS_PENDING,
        };
    }

    private function activerApresSucces(Payment $payment): void
    {
        $metadata = $payment->metadata ?? [];

        match ($payment->type) {
            'plan'      => $this->activerPlan($payment, $metadata['plan'] ?? null),
            'sponsored' => $this->activerSponsoring($payment, $metadata['offre_id'] ?? null),
            default     => null,
        };
    }

    private function activerPlan(Payment $payment, ?string $plan): void
    {
        if (!$plan) {
            return;
        }

        $duree     = Payment::DUREES_PLAN[$plan] ?? 30;
        $recruteur = $payment->user->recruiter;

        if ($recruteur) {
            $recruteur->update([
                'plan'            => $plan,
                'plan_expires_at' => now()->addDays($duree),
            ]);
        }
    }

    private function activerSponsoring(Payment $payment, ?int $offreId): void
    {
        if (!$offreId) {
            return;
        }

        Offre::where('id', $offreId)->update([
            'is_sponsored'    => true,
            'sponsored_until' => now()->addDays(7),
        ]);
    }

    private function calculerMontant(array $donnees): int
    {
        return match ($donnees['type']) {
            'plan'      => Payment::TARIFS['plan'][$donnees['plan']] ?? 0,
            'sponsored' => Payment::TARIFS['sponsored'],
            'urgent'    => Payment::TARIFS['urgent'],
            'pack'      => Payment::TARIFS['pack'],
            default     => 0,
        };
    }
}
