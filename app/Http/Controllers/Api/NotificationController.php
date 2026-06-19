<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'message' => 'ok',
            'data'    => $notifications,
            'meta'    => [
                'non_lues' => $notifications->where('lue', false)->count(),
            ],
        ]);
    }

    public function marquerLue(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $notification->update(['lue' => true]);

        return response()->json(['message' => 'Notification marquée comme lue.', 'data' => $notification]);
    }

    public function toutMarquerLues(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('lue', false)
            ->update(['lue' => true]);

        return response()->json(['message' => 'Toutes les notifications marquées comme lues.', 'data' => null]);
    }
}
