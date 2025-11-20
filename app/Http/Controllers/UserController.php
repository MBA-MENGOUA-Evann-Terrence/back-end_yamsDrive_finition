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
     * Gèle le compte d'un utilisateur (admin uniquement).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        try {
            $user = User::findOrFail($id);
            
            // Geler le compte au lieu de le supprimer
            $user->statut = 'gelé';
            $user->save();
            
            // Révoquer tous les tokens actifs pour déconnecter l'utilisateur
            $user->tokens()->delete();
            
            return response()->json([
                'message' => 'Compte gelé avec succès',
                'data' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du gel du compte', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors du gel du compte'], 500);
        }
    }

    /**
     * Assigne un rôle à un utilisateur (admin uniquement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'role' => 'required|integer|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->role = $request->role;
            $user->save();

            return response()->json([
                'message' => 'Rôle assigné avec succès.',
                'data' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'assignation du rôle', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Erreur lors de l\'assignation du rôle'], 500);
        }
    }

    /**
     * Assigne un service à un utilisateur (admin uniquement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignService(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'service_id' => 'required|integer|exists:services,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->service_id = $request->service_id;
            $user->save();

            return response()->json([
                'message' => 'Service assigné avec succès.',
                'data' => $user->load('service')
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'assignation du service', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Erreur lors de l\'assignation du service'], 500);
        }
    }

    /**
     * Réinitialise le mot de passe d'un utilisateur (admin uniquement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request, $id)
    {
        // Vérifier que l'utilisateur connecté est admin
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        try {
            // Vérifier que l'utilisateur existe
            $user = User::findOrFail($id);

            // Générer un nouveau mot de passe (aléatoire ou fourni)
            $newPassword = $request->input('password') ?? str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Mettre à jour le mot de passe
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'message' => 'Mot de passe réinitialisé avec succès.',
                'user_id' => $user->id,
                'new_password' => $newPassword
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la réinitialisation du mot de passe', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors de la réinitialisation du mot de passe'], 500);
        }
    }

    /**
     * Dégèle le compte d'un utilisateur (admin uniquement).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfreeze(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        try {
            $user = User::findOrFail($id);
            $user->statut = 'actif';
            $user->save();

            return response()->json([
                'message' => 'Compte dégelé avec succès.',
                'data' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du dégel du compte', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Erreur lors du dégel du compte'], 500);
        }
    }
}
