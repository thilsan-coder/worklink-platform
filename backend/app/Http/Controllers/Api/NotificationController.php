<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List paginated notifications for the authenticated user (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications->items()),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Get unread notification count for authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $notification = Notification::find($id);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You cannot modify another user\'s notification.',
            ], 403);
        }

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Mark all notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'count' => 0,
            'updated_count' => $updated,
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $notification = Notification::find($id);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You cannot delete another user\'s notification.',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }

    /**
     * Clear all notifications for the authenticated user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $deleted = Notification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared successfully.',
            'deleted_count' => $deleted,
        ]);
    }
}
