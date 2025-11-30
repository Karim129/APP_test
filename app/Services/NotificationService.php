<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function createNotification(int $userId, string $type, string $title, string $message, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read' => false,
        ]);
    }

    public function getUserNotifications(int $userId, bool $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId);

        if ($unreadOnly) {
            $query->where('read', false);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function markAsRead(int $notificationId): Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->read = true;
        $notification->save();

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->update(['read' => true]);
    }
}
