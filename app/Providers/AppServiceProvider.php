<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Builder::defaultStringLength(191);

        $this->configurerRateLimiting();
    }

    private function configurerRateLimiting(): void
    {
        // 5 tentatives de connexion par minute par IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Trop de tentatives de connexion. Réessayez dans 1 minute.',
                    'data'    => null,
                ], 429);
            });
        });

        // 3 envois OTP par minute par IP + téléphone
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip() . ':' . $request->phone)->response(function () {
                return response()->json([
                    'message' => 'Trop de demandes OTP. Attendez avant de réessayer.',
                    'data'    => null,
                ], 429);
            });
        });

        // 10 candidatures par minute par utilisateur
        RateLimiter::for('apply', function (Request $request) {
            return Limit::perMinute(10)->by('apply:' . ($request->user()?->id ?? $request->ip()))->response(function () {
                return response()->json([
                    'message' => 'Trop de candidatures en peu de temps. Patientez un moment.',
                    'data'    => null,
                ], 429);
            });
        });
    }
}
