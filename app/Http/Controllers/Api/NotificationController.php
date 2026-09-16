<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate($request->get('per_page', 15));

        return ResponseHelper::success(
            NotificationResource::collection($notifications)->response()->getData(true),
            'Notifications retrieved successfully.'
        );
    }

    
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->count();

        return ResponseHelper::success(
            ['unread_count' => $count],
            'Unread notifications count retrieved successfully.'
        );
    }

    
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::find($id);

        if (! $notification) {
            return ResponseHelper::error(null, 'Notification not found.', 404);
        }

        if ($notification->user_id !== $request->user()->id) {
            return ResponseHelper::error(null, 'Unauthorized access to this notification.', 403);
        }

        $notification->update(['is_read' => true]);

        return ResponseHelper::success(
            new NotificationResource($notification),
            'Notification marked as read successfully.'
        );
    }

    
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return ResponseHelper::success(
            null,
            'All notifications marked as read successfully.'
        );
    }
}