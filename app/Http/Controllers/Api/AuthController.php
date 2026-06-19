<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Recruiter;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private SmsService $sms) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|unique:users,email',
            'phone'    => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:candidate,recruiter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        if ($request->role === 'candidate') {
            Candidate::create(['user_id' => $user->id]);
        } else {
            Recruiter::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie.',
            'data'    => ['token' => $token, 'user' => $user],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'nullable|email',
            'phone'    => 'nullable|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $user = $request->email
            ? User::where('email', $request->email)->first()
            : User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
                'data'    => null,
            ], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'data'    => ['token' => $token, 'user' => $user],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
            'data'    => null,
        ]);
    }

    public function envoyerOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Numéro invalide.',
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $code = (string) random_int(100000, 999999);
        Cache::put("otp:{$request->phone}", $code, now()->addMinutes(10));

        $this->sms->envoyer(
            $request->phone,
            "Votre code de vérification Ibissé : {$code}"
        );

        return response()->json([
            'message' => 'Code OTP envoyé.',
            'data'    => null,
        ]);
    }

    public function verifierOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code'  => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $codeEnCache = Cache::get("otp:{$request->phone}");

        if (!$codeEnCache || $codeEnCache !== $request->code) {
            return response()->json([
                'message' => 'Code OTP invalide ou expiré.',
                'data'    => null,
            ], 422);
        }

        Cache::forget("otp:{$request->phone}");

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Aucun compte associé à ce numéro.',
                'data'    => null,
            ], 404);
        }

        $user->update(['phone_verified_at' => now()]);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Téléphone vérifié.',
            'data'    => ['token' => $token, 'user' => $user],
        ]);
    }
}
