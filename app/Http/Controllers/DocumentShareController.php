<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogActionTrait;
use Illuminate\Support\Str;

class DocumentShareController extends Controller
{
    use LogActionTrait;
    /**
     * Affiche la liste des documents partagés avec l'utilisateur connecté.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sharedWithMe()
    {
        try {
            // Vérifier si l'utilisateur est authentifié
            if (!auth()->check()) {
                \Log::warning('Tentative d\'accès aux documents partagés sans authentification');
                return response()->json(['message' => 'Non authentifié.'], 401);
            }
            
            $user = Auth::user();
            \Log::info('Récupération des documents partagés pour l\'utilisateur', ['user_id' => $user->id]);
            
            // Récupérer tous les partages où l'utilisateur est le destinataire
            $shares = DocumentShare::where('user_id', $user->id)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->with(['document', 'sharedBy:id,name,email'])
                ->get();
            
            \Log::info('Nombre de partages trouvés', ['count' => $shares->count()]);
            
            // Vérifier si des documents sont associés aux partages
            $validShares = $shares->filter(function($share) {
                return !is_null($share->document);
            });
            
            // Extraire les documents de ces partages
            $documents = $validShares->map(function($share) {
                $document = $share->document;
                $document->shared_by = $share->sharedBy;
                $document->permission_level = $share->permission_level;
                $document->share_id = $share->id;
                $document->expires_at = $share->expires_at;
                return $document;
            });
            
            return response()->json(['data' => $documents]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des documents partagés', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur lors de la récupération des documents partagés: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Affiche la liste des partages pour un document spécifique.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(string $uuid)
    {
        try {
            // Vérifier si l'utilisateur est authentifié
            if (!Auth::check()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            $document = Auth::user()->documents()
                ->where('uuid', $uuid)
                ->firstOrFail();
            
            $shares = $document->shares()->with(['user:id,name,email', 'sharedBy:id,name,email'])->get();
            
            return response()->json(['data' => $shares]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé ou vous n\'avez pas la permission d\'y accéder.'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des partages du document', [
                'uuid' => $uuid,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur serveur lors de la récupération des partages.'], 500);
        }
    }

    /**
     * Partage un document avec un utilisateur spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function share(Request $request, string $uuid)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'permission_level' => 'required|in:read,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);
        
        // Récupérer le document
        $document = Auth::user()->documents()
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        // Récupérer l'utilisateur avec qui partager
        $user = User::where('email', $request->email)->first();
        
        // Vérifier que l'utilisateur ne partage pas avec lui-même
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas partager un document avec vous-même.'
            ], 400);
        }
        
        // Vérifier si le partage existe déjà
        $existingShare = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existingShare) {
            // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
            // $this->logAction('mise à jour du partage', $document, ['user_id' => $user->id, 'permission' => $request->permission_level], $existingShare->toArray());

            // Mettre à jour le partage existant
            $existingShare->update([
                'permission_level' => $request->permission_level,
                'expires_at' => $request->expires_at,
                'shared_by' => Auth::id(),
            ]);
            
            return response()->json([
                'message' => 'Partage mis à jour avec succès',
                'data' => $existingShare->load(['user:id,name,email', 'sharedBy:id,name,email'])
            ]);
        }
        
        // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
        // $this->logAction('partage', $document, ['user_id' => $user->id, 'permission' => $request->permission_level]);

        // Créer un nouveau partage
        $share = DocumentShare::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'shared_by' => Auth::id(),
            'permission_level' => $request->permission_level,
            'expires_at' => $request->expires_at,
        ]);
        
        return response()->json([
            'message' => 'Document partagé avec succès',
            'data' => $share->load(['user:id,name,email', 'sharedBy:id,name,email'])
        ], 201);
    }

    /**
     * Génère un lien de partage pour un document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateShareLink(Request $request, string $uuid)
    {
        $request->validate([
            'permission_level' => 'required|in:read,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);
        
        // Récupérer le document
        $document = Auth::user()->documents()
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        // Générer un token unique
        $token = Str::random(32);
        
        // Créer un lien de partage dans la table dédiée
        $shareLink = ShareLink::create([
            'document_id' => $document->id,
            'token' => $token,
            'shared_by' => Auth::id(),
            'permission_level' => $request->permission_level,
            'expires_at' => $request->expires_at,
        ]);
        
        // Générer l'URL de partage
        $shareUrl = url('/api/shared-documents/' . $token);
        
        return response()->json([
            'message' => 'Lien de partage généré avec succès',
            'data' => [
                'share' => $shareLink->load('sharedBy:id,name,email'),
                'share_url' => $shareUrl
            ]
        ], 201);
    }

    /**
     * Accède à un document partagé via un token et le télécharge directement.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function accessSharedDocument(string $token)
    {
        try {
            // Trouver le lien de partage par token
            $shareLink = ShareLink::where('token', $token)->first();
            
            if (!$shareLink) {
                return response()->json(['message' => 'Lien de partage invalide ou expiré'], 404);
            }
            
            // Vérifier si le lien est expiré
            if ($shareLink->isExpired()) {
                return response()->json(['message' => 'Ce lien de partage a expiré'], 403);
            }
            
            // Récupérer le document
            $document = $shareLink->document;
            
            if (!$document) {
                return response()->json(['message' => 'Document non trouvé'], 404);
            }
            
            // Vérifier que le fichier existe sur le serveur
            if (!\Storage::disk('private')->exists($document->file_path)) {
                return response()->json(['message' => 'Fichier non trouvé sur le serveur'], 404);
            }
            
            // Journaliser l'accès via lien de partage
            \Log::info('Accès via lien de partage', [
                'token' => $token,
                'document_id' => $document->id,
                'document_name' => $document->name,
                'shared_by' => $shareLink->shared_by
            ]);
            
            // Retourner le fichier en téléchargement direct
            return \Storage::disk('private')->download($document->file_path, $document->file_name);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'accès au document partagé', [
                'token' => $token,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'accès au document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un partage de document.
     *
     * @param  string  $uuid
     * @param  int  $shareId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeShare(string $uuid, int $shareId)
    {
        // Récupérer le document
        $document = Auth::user()->documents()
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        // Trouver et supprimer le partage
        $share = $document->shares()->findOrFail($shareId);
        // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
        // $this->logAction('suppression du partage', $share->document, ['user_id' => $share->user_id], $share->toArray());

        $share->delete();
        
        return response()->json([
            'message' => 'Partage supprimé avec succès'
        ]);
    }
}
