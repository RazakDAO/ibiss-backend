<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitierPaiementRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function initier(InitierPaiementRequest $request): JsonResponse
    {
        try {
            $resultat = $this->paymentService->initier(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'message' => 'Paiement initié. Veuillez compléter la transaction.',
                'data'    => $resultat,
            ]);
        } catch (PaymentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        $provider = $request->query('provider', 'orange_money');

        // Validation signature HMAC (sécurité webhook)
        if (!$this->paymentService->validerHmac($request, $provider)) {
            Log::warning('Callback paiement : signature HMAC invalide', [
                'provider' => $provider,
                'ip'       => $request->ip(),
            ]);
            // Retourner 200 pour éviter les renotifications répétées
            return response()->json(['message' => 'OK'], 200);
        }

        try {
            $payload  = $request->all();
            $payment  = $this->paymentService->traiterCallback($payload, $provider);

            Log::info('Callback paiement traité', [
                'reference' => $payment->reference,
                'status'    => $payment->status,
                'provider'  => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur traitement callback paiement', [
                'provider' => $provider,
                'erreur'   => $e->getMessage(),
                'payload'  => $request->all(),
            ]);
        }

        // Toujours retourner 200 (sinon l'opérateur renotifie indéfiniment)
        return response()->json(['message' => 'OK'], 200);
    }
}
