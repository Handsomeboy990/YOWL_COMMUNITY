<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List the notifications of the authenticated user, most recent first.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
            'unread' => 'nullable|boolean',
        ]);

        $query = $request->user()->notifications();
        if ($request->boolean('unread')) {
            $query = $request->user()->unreadNotifications();
        }

        $notifications = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'message' => 'Notifications récupérées avec succès',
        ]);
    }

    /**
     * The number of unread notifications, for the bell badge.
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $request->user()->unreadNotifications()->count()],
            'message' => 'Compteur récupéré avec succès',
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();
        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $request->user()->unreadNotifications()->count()],
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * Mark every unread notification of the user as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => 0],
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }

    /**
     * Delete a notification belonging to the authenticated user.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();
        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée avec succès',
        ]);
    }
}
