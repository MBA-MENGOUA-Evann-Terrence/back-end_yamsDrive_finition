<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Affiche la liste de tous les utilisateurs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        try {
            // On récupère tous les utilisateurs avec leur service ET on les trie par ID croissant pour éviter les incohérences
            $users = User::with('service')->orderBy('id', 'asc')->get();

            // Ajouter un indicateur clair pour les comptes gelés
            $users = $users->map(function($user) {
                $userData = $user->toArray();
                $userData['is_frozen'] = ($user->statut === 'suspendu' || $user->statut === 'inactif');
                return $userData;
            });

            return response()->json(['data' => $users]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des utilisateurs', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors de la récupération des utilisateurs'], 500);
        }
    }

    /**
     * Affiche les détails d'un utilisateur spécifique.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        // Journaliser l'appel à la méthode show
        \Log::info('Tentative d\'accès à l\'utilisateur', ['id' => $id]);
        
        try {
            // Vérifier si l'utilisateur existe d'abord
            if (!User::where('id', $id)->exists()) {
                \Log::warning('Utilisateur non trouvé', ['id' => $id]);
                return response()->json([
                    'message' => 'Utilisateur non trouvé', 
                    'success' => false,
                    'debug_info' => 'L\'ID ' . $id . ' ne correspond à aucun utilisateur dans la base de données'
                ], 404);
            }
            
            // Récupérer l'utilisateur avec ses documents
            $user = User::with(['documents' => function($query) {
                $query->select('id', 'uuid', 'nom', 'type', 'taille', 'description', 'user_id', 'created_at', 'updated_at');
            }])->findOrFail($id);
            
            // Récupérer le nombre de documents partagés avec cet utilisateur
            $sharedWithUserCount = \App\Models\DocumentShare::where('user_id', $id)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->count();
            
            // Récupérer le nombre de documents que l'utilisateur a partagés
            $sharedByUserCount = \App\Models\DocumentShare::where('shared_by', $id)->count();
            
            // Ajouter ces informations à la réponse
            $userData = $user->toArray();
            $userData['shared_with_user_count'] = $sharedWithUserCount;
            $userData['shared_by_user_count'] = $sharedByUserCount;
            
            return response()->json([
                'data' => $userData,
                'success' => true
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Utilisateur non trouvé', 
                'success' => false
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'utilisateur', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'utilisateur: ' . $e->getMessage(), 
                'success' => false
            ], 500);
        }
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        
        try {
            
            Log::info('DEBUT - Création utilisateur');

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'name' => 'nullable|string|max:255', // Maintenant nullable
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                Log::error('ECHEC - Validation échouée', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            Log::info('ETAPE 1 - Validation réussie');

            $user = User::create([
                'nom' => $request->nom,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->input('role', 'user'), 
                'statut' => $request->input('statut', 'actif'),
            ]);

            Log::info('ETAPE 2 - Utilisateur créé en base de données', ['user_id' => $user->id]);

            // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
            // event(new \App\Events\UserActionLogged(
            //     'création',
            //     $user,
            //     $user->getChanges(),
            //     [],
            //     $request->user()
            // ));

            Log::info('ETAPE 3 - Événement de log envoyé');

            return response()->json(['data' => $user, 'message' => 'Utilisateur créé avec succès'], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors de la création de l\'utilisateur'], 500);
        }
    }

    /**
     * Met à jour les informations d'un utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // La logique existante vérifie déjà si l'utilisateur est propriétaire ou admin, c'est suffisant.
        // Pas besoin de rajouter une vérification ici.
        try {
            $user = User::findOrFail($id);
            $authenticatedUser = $request->user();

            // Vérifier si l'utilisateur est authentifié
            if (!$authenticatedUser) {
                return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
            }

            // Security Check: Only the owner or an admin can update the profile.
            if ($authenticatedUser->id != $user->id && $authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé.'], 403);
            }

            $oldValues = $user->getOriginal();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => 'sometimes|required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
            // event(new \App\Events\UserActionLogged(
            //     'mise à jour',
            //     $user,
            //     $user->getChanges(),
            //     $oldValues,
            //     $authenticatedUser
            // ));

            return response()->json(['data' => $user, 'message' => 'Utilisateur mis à jour avec succès']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de l\'utilisateur', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors de la mise à jour de l\'utilisateur'], 500);
        }
    }

    /**
     * Gèle/Suspend un compte utilisateur (au lieu de le supprimer).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
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

            // Récupération de l'utilisateur authentifié
            $authenticatedUser = $personalAccessToken->tokenable;

            // Vérifier que l'utilisateur authentifié est admin
            if ($authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs peuvent geler des comptes.'], 403);
            }

            // Récupérer l'utilisateur à geler
            $user = User::findOrFail($id);

            // Empêcher un admin de geler son propre compte
            if ($user->id === $authenticatedUser->id) {
                return response()->json(['message' => 'Vous ne pouvez pas geler votre propre compte.'], 403);
            }

            // Geler le compte en changeant le statut
            $user->statut = 'suspendu';
            $user->save();

            // Révoquer tous les tokens de l'utilisateur pour le déconnecter immédiatement
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Compte utilisateur gelé avec succès. L\'utilisateur ne pourra plus se connecter.',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'statut' => $user->statut
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du gel du compte utilisateur', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dégèle/Réactive un compte utilisateur suspendu.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfreeze(Request $request, $id)
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

            // Récupération de l'utilisateur authentifié
            $authenticatedUser = $personalAccessToken->tokenable;

            // Vérifier que l'utilisateur authentifié est admin
            if ($authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs peuvent dégeler des comptes.'], 403);
            }

            // Récupérer l'utilisateur à dégeler
            $user = User::findOrFail($id);

            // Vérifier si le compte est bien suspendu
            if ($user->statut !== 'suspendu' && $user->statut !== 'inactif') {
                return response()->json(['message' => 'Ce compte n\'est pas suspendu.'], 400);
            }

            // Dégeler le compte en changeant le statut
            $user->statut = 'actif';
            $user->save();

            return response()->json([
                'message' => 'Compte utilisateur dégelé avec succès. L\'utilisateur peut maintenant se connecter.',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'statut' => $user->statut
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du dégel du compte utilisateur', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialise/Modifie le mot de passe d'un utilisateur (Admin uniquement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request, $id)
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

            // Récupération de l'utilisateur authentifié
            $authenticatedUser = $personalAccessToken->tokenable;

            // Vérifier que l'utilisateur authentifié est admin
            if ($authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs peuvent réinitialiser les mots de passe.'], 403);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed', // confirmed vérifie password_confirmation
            ], [
                'password.required' => 'Le mot de passe est requis',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
                'password.confirmed' => 'Les mots de passe ne correspondent pas',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Récupérer l'utilisateur à modifier
            $user = User::findOrFail($id);

            // Modifier le mot de passe
            $user->password = Hash::make($request->password);
            $user->save();

            // Révoquer tous les tokens de l'utilisateur pour le forcer à se reconnecter
            $user->tokens()->delete();

            return response()->json([
                'message' => "Mot de passe réinitialisé avec succès. L'utilisateur devra se reconnecter avec le nouveau mot de passe.",
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la réinitialisation du mot de passe', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attribue un rôle à un utilisateur (admin ou utilisateur).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request, $id)
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

            // Récupération de l'utilisateur authentifié
            $authenticatedUser = $personalAccessToken->tokenable;

            // Vérifier que l'utilisateur authentifié est admin
            if ($authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs peuvent attribuer des rôles.'], 403);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:0,1', // 0 = utilisateur, 1 = admin
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Récupérer l'utilisateur à modifier
            $user = User::findOrFail($id);

            // Empêcher un admin de se retirer ses propres droits
            if ($user->id === $authenticatedUser->id && $request->role == 0) {
                return response()->json(['message' => 'Vous ne pouvez pas retirer vos propres droits d\'administrateur.'], 403);
            }

            $oldRole = $user->role;
            $user->role = $request->role;
            $user->save();

            $roleName = $request->role == 1 ? 'Administrateur' : 'Utilisateur';

            return response()->json([
                'message' => "Rôle attribué avec succès",
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'old_role' => $oldRole,
                    'new_role' => $user->role,
                    'role_name' => $roleName
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'attribution du rôle', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affecte un utilisateur à un service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignService(Request $request, $id)
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

            // Récupération de l'utilisateur authentifié
            $authenticatedUser = $personalAccessToken->tokenable;

            // Vérifier que l'utilisateur authentifié est admin
            if ($authenticatedUser->role != 1) {
                return response()->json(['message' => 'Accès non autorisé. Seuls les administrateurs peuvent affecter des services.'], 403);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Récupérer l'utilisateur à modifier
            $user = User::findOrFail($id);
            $oldServiceId = $user->service_id;

            $user->service_id = $request->service_id;
            $user->save();

            // Charger la relation service pour retourner les détails
            $user->load('service');

            return response()->json([
                'message' => "Service affecté avec succès",
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'old_service_id' => $oldServiceId,
                    'new_service_id' => $user->service_id,
                    'service' => $user->service
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affectation du service', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
