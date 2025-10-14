<?php

namespace App\Http\Controllers;

use App\Events\UserActionLogged;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentSent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Log;

class DocumentController extends Controller
{
    /**
     * Affiche la liste des documents de l'utilisateur connecté.
     */
    public function index()
    {
        $user = Auth::user();
        $documents = $user->documents()->with('service')->get();
        return response()->json(['data' => $documents]);
    }

    /**
     * Stocke un nouveau document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240', // 10MB max
            'service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string|max:1000',
        ]);

        $fichier = $request->file('fichier');
        $nomFichier = Str::uuid() . '.' . $fichier->getClientOriginalExtension();
        $chemin = $fichier->storeAs('documents', $nomFichier, 'public');
        $fullPath = Storage::disk('public')->path($chemin);
        // Log::info('Fichier stocké', ['chemin_relatif' => $chemin, 'chemin_absolu' => $fullPath]);

        $user = $this->getUser();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 401);
        }

        $document = Document::create([
            'uuid' => Str::uuid(),
            'nom' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'type' => $fichier->getMimeType(),
            'taille' => $fichier->getSize(),
            'description' => $request->input('description'),
            'user_id' => $user->id,
            'service_id' => $request->input('service_id'),
        ]);

        // Déclenche l'événement de journalisation
        event(new UserActionLogged('création', $document, $document->toArray()));

        return response()->json([
            'message' => 'Document créé avec succès',
            'data' => $document
        ], 201);
    }

    /**
     * Affiche les détails d'un document spécifique.
     */
    public function show(string $uuid)
    {
        $document = Auth::user()->documents()->where('uuid', $uuid)->with('service')->firstOrFail();
        return response()->json(['data' => $document]);
    }

    /**
     * Met à jour les métadonnées d'un document.
     */
    public function update(Request $request, string $uuid)
    {
        $document = Auth::user()->documents()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'service_id' => 'nullable|exists:services,id',
        ]);

        $originalData = $document->toArray();
        $document->update($validated);

        // Déclenche l'événement de journalisation
        event(new UserActionLogged('mise à jour', $document, $document->getChanges(), $originalData));

        return response()->json([
            'message' => 'Document mis à jour avec succès',
            'data' => $document->load('service')
        ]);
    }

    /**
     * Supprime un document.
     */
    public function destroy(string $uuid)
    {
        $document = Auth::user()->documents()->where('uuid', $uuid)->firstOrFail();
        $originalData = $document->toArray();

        // Ne pas supprimer le fichier physique ici: on effectue un soft delete uniquement.
        // Le fichier sera supprimé définitivement dans forceDelete().

        // Déclenche l'événement de journalisation avant la suppression (soft)
        event(new UserActionLogged('suppression', $document, null, $originalData));

        $document->delete();

        // Rafraîchir le modèle pour récupérer deleted_at
        $document->refresh();
        // Log::info('Soft delete effectué', [
        //     'uuid' => $document->uuid,
        //     'deleted_at' => $document->deleted_at,
        //     'trashed' => $document->trashed(),
        // ]);

        return response()->json([
            'message' => 'Document supprimé avec succès',
            'data' => [
                'uuid' => $document->uuid,
                'trashed' => $document->trashed(),
                'deleted_at' => $document->deleted_at,
            ]
        ]);
    }

    /**
     * Prévisualise le contenu d'un document.
     */
    public function preview(string $uuid)
    {
        try {
            // Log::info('Tentative de prévisualisation du document', ['uuid' => $uuid]);
            
            // Récupérer le document
            $document = $this->getDocument($uuid);
            
            // Log::info('Document trouvé', [
            //     'uuid' => $document->uuid,
            //     'chemin' => $document->chemin,
            //     'type' => $document->type
            // ]);
            
            // Vérifier si le fichier existe dans le stockage public
            $publicPath = public_path('storage/' . $document->chemin);
            // Log::info('Chemin public du fichier', ['publicPath' => $publicPath]);
            
            if (file_exists($publicPath)) {
                // Rediriger vers l'URL publique du fichier
                return redirect(asset('storage/' . $document->chemin));
            }
            
            // Sinon essayer le chemin de stockage privé
            $filePath = storage_path('app/public/' . $document->chemin);
            // Log::info('Chemin privé du fichier', ['filePath' => $filePath]);
            
            if (!file_exists($filePath)) {
                // Log::error('Fichier non trouvé', ['path' => $filePath]);
                return response()->json([
                    'message' => 'Fichier non trouvé', 
                    'path' => $document->chemin,
                    'debug' => 'Le fichier n\'existe ni dans le stockage public ni dans le stockage privé'
                ], 404);
            }
            
            // Déterminer le type de contenu
            $contentType = $document->type;
            
            // Pour les PDF, forcer le type MIME correct
            if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
                $contentType = 'application/pdf';
            }
            
            // Log::info('Envoi du fichier', ['contentType' => $contentType]);
            
            // Retourner le fichier avec le bon type MIME
            return response()->file($filePath, ['Content-Type' => $contentType]);
        } catch (Exception $e) {
            // Log::error('Erreur lors de la prévisualisation du document', [
            //     'message' => $e->getMessage(),
            //     'uuid' => $uuid
            // ]);
            return response()->json(['message' => 'Erreur lors de la prévisualisation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Télécharge un document.
     */
     public function download(string $uuid)
    {
        try {
            // Log::info('Tentative de téléchargement du document', ['uuid' => $uuid]);

            // Récupérer le document
            $document = $this->getDocument($uuid, false);

            // Log::info('Document trouvé pour téléchargement', [
            //     'uuid' => $document->uuid,
            //     'chemin' => $document->chemin,
            //     'nom' => $document->nom
            // ]);

            // Construire le chemin absolu vers le fichier dans le stockage
            $filePath = storage_path('app/public/' . $document->chemin);
            // Log::info('Tentative de lecture du fichier', ['chemin_construit' => $filePath]);

            // Vérifier si le fichier existe physiquement
            if (!file_exists($filePath)) {
                // Log::error('ECHEC: Fichier non trouvé à l\'emplacement attendu', ['chemin_db' => $document->chemin, 'full_path_checked' => $filePath]);
                return response()->json(['message' => 'Fichier non disponible sur le site.'], 404);
            }

            // Journaliser l'action de téléchargement
            event(new UserActionLogged('telechargement', $document));

            // Retourner le fichier en tant que téléchargement
            return response()->download($filePath, $document->nom);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        } catch (Exception $e) {
            // Log::error('Erreur lors du téléchargement du document', [
            //     'message' => $e->getMessage(),
            //     'uuid' => $uuid
            // ]);
            return response()->json(['message' => 'Erreur lors du téléchargement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les documents récemment publiés par l'utilisateur.
     */
    public function getRecentPublishedDocuments(Request $request)
    {
        try {
            // Log::info('Accès à getRecentPublishedDocuments');
            
            $limit = $request->query('limit', 10);
            $user = $this->getUser();

            $documents = $user->documents()
                ->with('service')
                ->latest()
                ->take($limit)
                ->get();
            
            // Log::info('Documents récupérés avec succès', ['count' => $documents->count()]);

            return response()->json(['data' => $documents]);
        } catch (Exception $e) {
            // Log::error('Erreur dans getRecentPublishedDocuments', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return response()->json(['message' => 'Erreur lors de la récupération des documents récents: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les documents récemment reçus (partagés avec l'utilisateur).
     */
    public function getRecentReceivedDocuments(Request $request)
    {
        try {
            // Log::info('Accès à getRecentReceivedDocuments');
            
            $limit = $request->query('limit', 10);
            $user = $this->getUser();

            // Récupérer les IDs des documents partagés avec l'utilisateur
            $documentIds = \App\Models\DocumentShare::where('user_id', $user->id)
                ->where(function ($query) {
                    // Inclure les partages non expirés ou sans date d'expiration
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('created_at')
                ->pluck('document_id');
            
            // Log::info('Nombre de partages trouvés', ['count' => $documentIds->count()]);

            // Récupérer les documents correspondants
            $documents = Document::whereIn('id', $documentIds)
                ->with(['user', 'service'])
                ->latest()
                ->take($limit)
                ->get();
                
            // Log::info('Documents reçus récupérés avec succès', ['count' => $documents->count()]);

            return response()->json(['data' => $documents]);
        } catch (Exception $e) {
            // Log::error('Erreur dans getRecentReceivedDocuments', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return response()->json(['message' => 'Erreur lors de la récupération des documents reçus: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Récupère une liste combinée et triée des documents récemment publiés et reçus.
     */
    public function getRecentDocuments(Request $request)
    {
        try {
            // Log::info('Accès à getRecentDocuments');
            $limit = $request->query('limit', 10);
            $user = $this->getUser();

            if (!$user) {
                return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
            }

            // 1. Récupérer les documents publiés
            $published = $user->documents()
                ->with('service')
                ->latest()
                ->take($limit)
                ->get()
                ->map(function ($doc) {
                    $doc->source = 'published';
                    $doc->action_date = $doc->created_at;
                    return $doc;
                });

            // 2. Récupérer les documents reçus
            $sharedDocumentIds = \App\Models\DocumentShare::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest('created_at')
                ->pluck('document_id');

            $received = Document::whereIn('id', $sharedDocumentIds)
                ->with(['user', 'service'])
                ->latest()
                ->take($limit)
                ->get()
                ->map(function ($doc) {
                    $doc->source = 'received';
                    $doc->action_date = $doc->created_at;
                    return $doc;
                });

            // 3. Fusionner, trier et limiter
            $allDocuments = $published->merge($received)
                ->sortByDesc('action_date')
                ->take($limit);

            return response()->json(['data' => $allDocuments->values()->all()]);

        } catch (Exception $e) {
            // Log::error('Erreur dans getRecentDocuments', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return response()->json(['message' => 'Erreur lors de la récupération des documents récents: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Envoie un document par e-mail.
     */
    public function sendByEmail(Request $request, string $uuid)
    {
        $validator = Validator::make($request->all(), [
            'recipient_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $document = $user->documents()->where('uuid', $uuid)->firstOrFail();

            if (!Storage::disk('public')->exists($document->chemin)) {
                return response()->json(['message' => 'Le fichier du document est introuvable.'], 404);
            }

            Mail::to($request->input('recipient_email'))
                ->send(new DocumentSent($document, $request->input('subject'), $request->input('body')));

            // Optionnel: logger l'action
            event(new UserActionLogged('envoi_email', $document, ['recipient' => $request->input('recipient_email')]));

            return response()->json(['message' => 'Document envoyé par e-mail avec succès.']);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        } catch (Exception $e) {
            // Log::error('Erreur lors de l\'envoi de l\'e-mail du document: ' . $e->getMessage());
            return response()->json(['message' => 'Une erreur est survenue lors de l\'envoi de l\'e-mail.'], 500);
        }
    }

    /**
     * Liste uniquement les documents supprimés (corbeille) de l'utilisateur connecté.
     */
    public function trash(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = $user->documents()
            ->onlyTrashed()
            ->select(['id','uuid','nom','deleted_at','service_id'])
            ->with(['service:id,nom'])
            ->latest('deleted_at');

        $keyword = trim((string) $request->get('search_by_keyword', ''));
        if ($keyword !== '') {
            $query->where('nom', 'like', "%{$keyword}%");
        }

        $rows = (int) $request->get('rows', 10);
        if ($rows <= 0) { $rows = 10; }

        // Use simplePaginate to avoid heavy COUNT(*) queries and reduce memory
        $documents = $query->simplePaginate($rows);

        return response()->json($documents);
    }

    /**
     * Restaure un document soft-supprimé.
     */
    public function restore(string $uuid)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $document = $user->documents()->withTrashed()->where('uuid', $uuid)->firstOrFail();

            if (!$document->trashed()) {
                return response()->json(['message' => 'Ce document n\'est pas dans la corbeille.'], 400);
            }

            // Utiliser une requête brute pour éviter de déclencher des événements Eloquent
            $restored = \Illuminate\Support\Facades\DB::table('documents')
                ->where('id', $document->id)
                ->update(['deleted_at' => null]);

            if ($restored) {
                // Déclencher l'événement après la restauration réussie
                event(new UserActionLogged('restauration', $document));
                // Log::info('Document restauré via requête brute', ['uuid' => $uuid]);
                return response()->json(['message' => 'Document restauré avec succès']);
            } else {
                return response()->json(['message' => 'La restauration a échoué.'], 500);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        } catch (Exception $e) {
            // Log::error('Erreur lors de la restauration du document: ' . $e->getMessage());
            return response()->json(['message' => 'Une erreur est survenue lors de la restauration.'], 500);
        }
    }

    /**
     * Supprime définitivement un document (purge).
     */
    public function forceDelete(string $uuid)
    {
        try {
            // \Log::info("=== DEBUT forceDelete pour UUID: $uuid ===");
            
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $document = $user->documents()->withTrashed()->where('uuid', $uuid)->firstOrFail();
            // \Log::info("Document trouvé: " . $document->nom);

            $originalData = $document->toArray();
            $documentPath = $document->chemin;

            // Utiliser une transaction pour garantir que le fichier et l'enregistrement sont supprimés ensemble
            \Illuminate\Support\Facades\DB::transaction(function () use ($document, $documentPath, $originalData) {
                // 1. Supprimer le fichier physique
                // \Log::info("Début suppression fichier physique...");
                if ($documentPath && Storage::disk('public')->exists($documentPath)) {
                    Storage::disk('public')->delete($documentPath);
                    // \Log::info("Fichier physique supprimé: {$documentPath}");
                }

                // 2. Supprimer l'enregistrement de la base de données en utilisant une requête brute pour contourner les événements Eloquent
                // \Log::info("Début suppression BDD par requête brute...");
                $deletedRows = \Illuminate\Support\Facades\DB::table('documents')->where('id', $document->id)->delete();

                if ($deletedRows > 0) {
                    // \Log::info("Enregistrement BDD supprimé pour document ID: {$document->id}");
                    // Déclencher l'événement de suppression définitive
                    event(new UserActionLogged('suppression_definitive', $document));
                    // \Log::info("Suppression définitive terminée avec succès.");
                } else {
                    // \Log::warning("La suppression BDD par requête brute n'a affecté aucune ligne.", ['id' => $document->id]);
                }
            });

            return response()->json(['message' => 'Document supprimé définitivement.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        } catch (Exception $e) {
            // Log::error('Erreur lors de la suppression définitive du document: ' . $e->getMessage());
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression définitive.'], 500);
        }
    }


    /**
     * Méthode helper pour récupérer un document avec gestion flexible de l'authentification
     */
    private function getDocument(string $uuid, bool $checkOwnership = true)
    {
        if ($checkOwnership && Auth::check()) {
            return Auth::user()->documents()->where('uuid', $uuid)->firstOrFail();
        }
        
        return Document::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Méthode helper pour récupérer l'utilisateur avec fallback
     */
    private function getUser()
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('Utilisateur authentifié', ['user_id' => $user->id]);
            return $user;
        }
        
        // Fallback temporaire pour le diagnostic
        $user = User::find(1);
        if ($user) {
            Log::warning('Utilisateur non authentifié, utilisation de l\'utilisateur 1');
        }
        return $user;
    }

    /**
     * Méthode helper pour vérifier l'existence d'un fichier
     */
    private function fileExists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }
}