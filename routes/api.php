<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DomainController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProspectController;

Route::post('/utilisateurs/new', [AuthController::class, 'registerUser']);
Route::post('/token', [AuthController::class, 'authenticate']);

// Route de déconnexion protégée
Route::middleware('auth:sanctum')->post('/logout', [LogoutController::class, 'logout']);

// Route pour récupérer le service de l'utilisateur connecté
// Route pour récupérer le service de l'utilisateur (vérification manuelle dans le contrôleur)
Route::get('/user/service', [App\Http\Controllers\ServiceController::class, 'getUserService']);

// Cette route est protégée et renvoie l'utilisateur actuellement authentifié
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    try {
        // Accès direct au token de l'utilisateur pour éviter les problèmes de chargement de modèle
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token non fourni'], 401);
        }
        
        // Trouver le token dans la table personal_access_tokens
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$tokenModel) {
            return response()->json(['error' => 'Token invalide'], 401);
        }
        
        // Récupérer l'ID de l'utilisateur depuis le token
        $userId = $tokenModel->tokenable_id;
        
        // Faire une requête directe à la base de données pour éviter de charger le modèle complet
        $userData = \Illuminate\Support\Facades\DB::table('users')
            ->select('id', 'name', 'email', 'role', 'statut')
            ->where('id', $userId)
            ->first();
            
        if (!$userData) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        // Convertir l'objet stdClass en tableau pour une meilleure compatibilité
        return response()->json((array) $userData);
    } catch (\Throwable $e) {
        // Amélioration de la journalisation avec plus de détails
        \Log::error('Erreur critique dans /api/user: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Une erreur serveur est survenue.',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});
// vous utilisez routes/api.php, l'URL sera http://localhost/api/register.




Route::get('/test', function () {
    return true;
});

// Routes pour la gestion des documents (temporairement sans authentification pour diagnostic)
// Anciennement protégées par middleware auth:sanctum
Route::get('/documents', [App\Http\Controllers\DocumentController::class, 'index']);
Route::post('/documents', [App\Http\Controllers\DocumentController::class, 'store']);
// Route pour récupérer les documents partagés avec l'utilisateur connecté
// IMPORTANT: Cette route doit être placée AVANT les routes avec des paramètres {uuid}
Route::get('/documents/shared-with-me', [App\Http\Controllers\DocumentShareController::class, 'sharedWithMe']);

    // Nouvelle route unifiée pour tous les documents récents
    Route::get('/documents/recent', [App\Http\Controllers\DocumentController::class, 'getRecentDocuments']);
    Route::get('/documents/recent/search', [App\Http\Controllers\RecentDocumentSearchController::class, 'index']);
    // Recherche/filtrage avancé (inclut mes documents et ceux partagés avec moi)
    Route::get('/documents/search', [App\Http\Controllers\DocumentSearchController::class, 'index']);
    // Options pour les filtres avancés (types, personnes, services)
    Route::get('/documents/filters/options', [App\Http\Controllers\DocumentFilterController::class, 'getFilterOptions']);

// Routes pour le partage de documents (AVANT les routes génériques pour éviter les conflits)
Route::get('/documents/{uuid}/shares', [App\Http\Controllers\DocumentShareController::class, 'index']);
Route::post('/documents/{uuid}/share', [App\Http\Controllers\DocumentShareController::class, 'share']);
Route::post('/documents/{uuid}/share-by-service', [App\Http\Controllers\ServiceShareController::class, 'shareByService']);
Route::post('/documents/{uuid}/share-link', [App\Http\Controllers\DocumentShareController::class, 'generateShareLink']);
Route::delete('/shares/{shareId}', [App\Http\Controllers\DocumentShareController::class, 'removeShare']);
Route::delete('/documents/{uuid}/shares/{shareId}', [App\Http\Controllers\DocumentShareController::class, 'removeShare']);

Route::get('/documents/{uuid}', [App\Http\Controllers\DocumentController::class, 'show'])->whereUuid('uuid');
Route::get('/documents/{uuid}/preview', [App\Http\Controllers\DocumentController::class, 'preview'])->whereUuid('uuid');
Route::get('/documents/{uuid}/download', [App\Http\Controllers\DocumentController::class, 'download'])->whereUuid('uuid');
Route::post('/documents/{uuid}/update', [App\Http\Controllers\DocumentController::class, 'update'])->whereUuid('uuid');
Route::delete('/documents/{uuid}', [App\Http\Controllers\DocumentController::class, 'destroy'])->whereUuid('uuid');
Route::post('/documents/{uuid}/send-email', [App\Http\Controllers\DocumentController::class, 'sendByEmail'])->middleware('auth:sanctum')->whereUuid('uuid');

// Corbeille (soft deletes) des documents
Route::get('/documents/trash', [App\Http\Controllers\DocumentController::class, 'trash']);
Route::post('/documents/{uuid}/restore', [App\Http\Controllers\DocumentController::class, 'restore'])->whereUuid('uuid');
Route::delete('/documents/{uuid}/force', [App\Http\Controllers\DocumentController::class, 'forceDelete'])->whereUuid('uuid');

// Routes pour la gestion des favoris
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favoris', [App\Http\Controllers\FavoriController::class, 'index']);
    Route::post('/favoris', [App\Http\Controllers\FavoriController::class, 'store']);
    Route::delete('/favoris/{document}', [App\Http\Controllers\FavoriController::class, 'destroy']);
});

// Routes pour la gestion des utilisateurs
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}/assign-role', [UserController::class, 'assignRole']);
Route::patch('/users/{id}/assign-service', [UserController::class, 'assignService']);
Route::patch('/users/{id}/reset-password', [UserController::class, 'resetPassword']); // Réinitialiser le mot de passe
Route::delete('/users/{id}', [UserController::class, 'destroy']); // Geler le compte
Route::patch('/users/{id}/unfreeze', [UserController::class, 'unfreeze']); // Dégeler le compte

// Route pour les statistiques
Route::get('/statistiques/globales', [App\Http\Controllers\StatistiqueController::class, 'getGlobalStats']);
Route::get('/statistiques/activite-documents', [App\Http\Controllers\StatistiqueController::class, 'getDocumentActivity']);
Route::get('/statistiques/repartition-stockage', [App\Http\Controllers\StatistiqueController::class, 'getStorageBreakdown']);
Route::get('/statistiques/activite-utilisateurs', [App\Http\Controllers\StatistiqueController::class, 'getUserActivity']);
Route::get('/statistiques/actions-recentes', [App\Http\Controllers\StatistiqueController::class, 'getRecentActions']);
Route::get('/statistiques/actions-utilisateurs', [App\Http\Controllers\StatistiqueController::class, 'getUserActionsChart']);
Route::get('/statistiques/partages-documents', [App\Http\Controllers\StatistiqueController::class, 'getDocumentSharingStats']);


// Routes publiques pour les documents partagés via un token
Route::get('/shared-documents/{token}/info', [App\Http\Controllers\DocumentShareController::class, 'getSharedDocumentInfo']); // Récupérer les infos
Route::get('/shared-documents/{token}', [App\Http\Controllers\DocumentShareController::class, 'accessSharedDocument']); // Télécharger le fichier

// Route pour la déconnexion (Sanctum)
Route::post('/logout', [App\Http\Controllers\LogoutController::class, 'logout'])->middleware('auth:sanctum');




// Routes pour la gestion des utilisateurs
Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
Route::get('/users/{id}', [App\Http\Controllers\UserController::class, 'show']);
Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
Route::post('/users/{id}/update', [App\Http\Controllers\UserController::class, 'update']);
Route::delete('/users/{id}', [App\Http\Controllers\UserController::class, 'destroy']); 


// Route::get('/clients', [App\Http\Controllers\ClientController::class, 'index']);
// Route::post('/clients', [App\Http\Controllers\ClientController::class, 'store']);
// Route::get('/clients/{id}', [App\Http\Controllers\ClientController::class, 'show']);
// Route::post('/clients/{id}/update', [App\Http\Controllers\ClientController::class, 'update']);
// Route::post('/clients/{id}/destroy', [App\Http\Controllers\ClientController::class, 'destroy']);
// Route::post('/clients/destroy-group', [App\Http\Controllers\ClientController::class, 'destroy_group']); 




Route::get('/services', [App\Http\Controllers\ServiceController::class, 'index']);
Route::post('/services', [App\Http\Controllers\ServiceController::class, 'store']);
Route::get('/services/{id}', [App\Http\Controllers\ServiceController::class, 'show']);
Route::post('/services/{id}/update', [App\Http\Controllers\ServiceController::class, 'update']);
Route::post('/services/{id}/destroy', [App\Http\Controllers\ServiceController::class, 'destroy']);
Route::post('/services/destroy-group', [App\Http\Controllers\ServiceController::class, 'destroy_group']); 








Route::get('/emails', [App\Http\Controllers\EmailController::class, 'index']);
Route::post('/emails', [App\Http\Controllers\EmailController::class, 'store']);
Route::get('/emails/{id}', [App\Http\Controllers\EmailController::class, 'show']);
Route::post('/emails/{id}/update', [App\Http\Controllers\EmailController::class, 'update']);
Route::post('/emails/{id}/destroy', [App\Http\Controllers\EmailController::class, 'destroy']);
Route::post('/emails/destroy-group', [App\Http\Controllers\EmailController::class, 'destroy_group']); 


Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
Route::post('/notifications', [App\Http\Controllers\NotificationController::class, 'store']);
Route::get('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'show']);
Route::post('/notifications/{id}/update', [App\Http\Controllers\NotificationController::class, 'update']);
Route::post('/notifications/{id}/destroy', [App\Http\Controllers\NotificationController::class, 'destroy']);
Route::post('/notifications/destroy-group', [App\Http\Controllers\NotificationController::class, 'destroy_group']); 


Route::get('/log_actions', [App\Http\Controllers\log_actionController::class, 'index']);
Route::post('/log_actions', [App\Http\Controllers\log_actionController::class, 'store']);
Route::get('/log_actions/{id}', [App\Http\Controllers\log_actionController::class, 'show']);
Route::post('/log_actions/{id}/update', [App\Http\Controllers\log_actionController::class, 'update']);
Route::post('/log_actions/{id}/destroy', [App\Http\Controllers\log_actionController::class, 'destroy']);
Route::post('/log_actions/destroy-group', [App\Http\Controllers\log_actionController::class, 'destroy_group']); 




Route::get('/piece_jointes', [App\Http\Controllers\PieceJointeController::class, 'index']);
Route::post('/piece_jointes', [App\Http\Controllers\PieceJointeController::class, 'store']);
Route::get('/piece_jointes/{id}', [App\Http\Controllers\PieceJointeController::class, 'show']);
Route::post('/piece_jointes/{id}/update', [App\Http\Controllers\PieceJointeController::class, 'update']);
Route::post('/piece_jointes/{id}/destroy', [App\Http\Controllers\PieceJointeController::class, 'destroy']);
Route::post('/piece_jointes/destroy-group', [App\Http\Controllers\PieceJointeController::class, 'destroy_group']); 







Route::get('/log_actions', [App\Http\Controllers\LogActionController::class, 'index']);
Route::post('/log_actions', [App\Http\Controllers\LogActionController::class, 'store']);
Route::get('/log_actions/{id}', [App\Http\Controllers\LogActionController::class, 'show']);
Route::post('/log_actions/{id}/update', [App\Http\Controllers\LogActionController::class, 'update']);
Route::post('/log_actions/{id}/destroy', [App\Http\Controllers\LogActionController::class, 'destroy']);
Route::post('/log_actions/destroy-group', [App\Http\Controllers\LogActionController::class, 'destroy_group']); 


Route::get('/share_links', [App\Http\Controllers\ShareLinkController::class, 'index']);
Route::post('/share_links', [App\Http\Controllers\ShareLinkController::class, 'store']);
Route::get('/share_links/{id}', [App\Http\Controllers\ShareLinkController::class, 'show']);
Route::post('/share_links/{id}/update', [App\Http\Controllers\ShareLinkController::class, 'update']);
Route::post('/share_links/{id}/destroy', [App\Http\Controllers\ShareLinkController::class, 'destroy']);
Route::post('/share_links/destroy-group', [App\Http\Controllers\ShareLinkController::class, 'destroy_group']); 





Route::get('/utilisateurs', [App\Http\Controllers\UtilisateurController::class, 'index']);
Route::post('/utilisateurs', [App\Http\Controllers\UtilisateurController::class, 'store']);
Route::get('/utilisateurs/{id}', [App\Http\Controllers\UtilisateurController::class, 'show']);
Route::post('/utilisateurs/{id}/update', [App\Http\Controllers\UtilisateurController::class, 'update']);
Route::post('/utilisateurs/{id}/destroy', [App\Http\Controllers\UtilisateurController::class, 'destroy']);
Route::post('/utilisateurs/destroy-group', [App\Http\Controllers\UtilisateurController::class, 'destroy_group']);

// Routes de test (sans authentification)
Route::get('/test-notifications', [App\Http\Controllers\NotificationSystemController::class, 'test']);
Route::get('/test-database', [App\Http\Controllers\NotificationSystemController::class, 'testDatabase']);
Route::get('/test-user-relation', [App\Http\Controllers\NotificationSystemController::class, 'testUserRelation']);
Route::get('/test-unread-count/{userId}', function($userId) {
    $user = \App\Models\User::find($userId);
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    }
    $count = $user->notifications()->whereNull('read_at')->count();
    return response()->json(['user_id' => $userId, 'user_name' => $user->name, 'unread_count' => $count]);
});

Route::get('/check-token/{tokenId}', function($tokenId) {
    $token = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
    if (!$token) {
        return response()->json(['error' => 'Token non trouvé'], 404);
    }
    
    $user = $token->tokenable;
    if (!$user) {
        return response()->json(['error' => 'Utilisateur associé au token non trouvé'], 404);
    }
    
    return response()->json([
        'token_id' => $token->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'token_created' => $token->created_at,
        'token_updated' => $token->updated_at
    ]);
});

// Routes pour le nouveau système de notifications
Route::prefix('system/notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\NotificationSystemController::class, 'index']);
    Route::get('/unread-count', [App\Http\Controllers\NotificationSystemController::class, 'unreadCount']);
    Route::patch('/{notificationId}/read', [App\Http\Controllers\NotificationSystemController::class, 'markAsRead']);
    Route::post('/mark-all-read', [App\Http\Controllers\NotificationSystemController::class, 'markAllAsRead']);
    Route::delete('/{notificationId}', [App\Http\Controllers\NotificationSystemController::class, 'destroy']);
});

// Routes pour la gestion des noms de domaine
Route::get('/domains', [DomainController::class, 'index']);
Route::post('/domains', [DomainController::class, 'store']);

// Routes de test supprimées - La solution finale est implémentée dans NotificationSystemController
 



