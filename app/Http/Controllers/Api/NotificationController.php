<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = $request->get('unread_only', false);
        $notifications = $this->notificationService->getUserNotifications($request->user()->id, $unreadOnly);

        return response()->json(['notifications' => $notifications], 200);
    }

    public function markAsRead(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($id);
            return response()->json(['message' => 'Notification marked as read', 'notification' => $notification], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'MARK_READ_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);
        return response()->json(['message' => 'All notifications marked as read', 'count' => $count], 200);
    }
}
