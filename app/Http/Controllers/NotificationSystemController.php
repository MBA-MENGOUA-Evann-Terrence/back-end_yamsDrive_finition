<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSystemController extends Controller
{
    /**
     * Récupère les notifications de l'utilisateur authentifié.
     */
    public function index(Request $request)
    {
        $query = auth()->user()->notifications()->with(['sender:id,name', 'document:id,uuid,nom']);

        // Option pour ne récupérer que les notifications non lues
        if ($request->query('status') === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(15);

        return response()->json($notifications);
    }

    /**
     * Test simple sans authentification.
     */
    public function test()
    {
        return response()->json(['message' => 'Test réussi', 'timestamp' => now()]);
    }

    /**
     * Test de la base de données sans authentification.
     */
    public function testDatabase()
    {
        try {
            // Test 1: Compter toutes les notifications
            $totalNotifications = Notification::count();
            
            // Test 2: Compter les notifications non lues
            $unreadNotifications = Notification::whereNull('read_at')->count();
            
            return response()->json([
                'message' => 'Test base de données réussi',
                'total_notifications' => $totalNotifications,
                'unread_notifications' => $unreadNotifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur base de données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de la relation user->notifications avec un utilisateur spécifique.
     */
    public function testUserRelation()
    {
        try {
            // Prendre le premier utilisateur disponible
            $user = \App\Models\User::first();
            
            if (!$user) {
                return response()->json(['message' => 'Aucun utilisateur trouvé'], 404);
            }
            
            // Test de la relation
            $userNotifications = $user->notifications()->count();
            $userUnreadNotifications = $user->notifications()->whereNull('read_at')->count();
            
            return response()->json([
                'message' => 'Test relation utilisateur réussi',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_notifications' => $userNotifications,
                'user_unread_notifications' => $userUnreadNotifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur relation utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compte le nombre de notifications non lues.
     */
    public function unreadCount(Request $request)
    {
        try {
            // Récupération manuelle du token
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['error' => 'Token non fourni'], 401);
            }

            // Validation manuelle du token
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$personalAccessToken) {
                return response()->json(['error' => 'Token invalide'], 401);
            }

            // Récupération de l'utilisateur
            $user = $personalAccessToken->tokenable;

            // Comptage des notifications non lues
            $count = $user->notifications()->whereNull('read_at')->count();

            return response()->json(['unread_count' => $count]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Marque une notification spécifique comme lue.
     */
    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marque toutes les notifications non lues comme lues.
     */
    public function markAllAsRead()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }

    /**
     * Supprime une notification.
     */
    public function destroy($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->delete();

        return response()->json(null, 204);
    }
}
