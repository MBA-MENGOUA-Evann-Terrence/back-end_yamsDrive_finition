<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DocumentSearchController extends Controller
{
    /**
     * Recherche/filtrage des documents accessibles à l'utilisateur
     * - Inclut les documents de l'utilisateur et ceux qui lui sont partagés
     * - Filtres: type, user_id (expéditeur), service_id, q (mot-clé), date_from, date_to,
     *            extension, taille_min, taille_max, favoris, corbeille, shared_only
     * - Tri: sort (nom|created_at|updated_at|taille), order (asc|desc)
     * - Pagination: per_page
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validation légère des paramètres
        $validated = $request->validate([
            'type' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|integer|exists:users,id', // expéditeur
            'service_id' => 'sometimes|integer|exists:services,id',
            'q' => 'sometimes|string|max:255', // recherche dans nom et chemin
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'extension' => 'sometimes|string|max:10', // ex: pdf, docx, jpg
            'taille_min' => 'sometimes|integer|min:0', // en octets
            'taille_max' => 'sometimes|integer|min:0', // en octets
            'favoris' => 'sometimes|boolean', // true = uniquement favoris
            'corbeille' => 'sometimes|boolean', // true = uniquement corbeille, false = actifs
            'shared_only' => 'sometimes|boolean', // true = uniquement documents partagés avec moi
            'sort' => 'sometimes|in:nom,created_at,updated_at,taille',
            'order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = (int)($validated['per_page'] ?? 15);
        $sort = $validated['sort'] ?? 'updated_at';
        $order = $validated['order'] ?? 'desc';

        // Récupérer les IDs des documents partagés avec l'utilisateur (non expirés)
        $sharedIds = DocumentShare::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('document_id');

        // Base: documents de l'utilisateur OU partagés avec lui
        $query = Document::query()
            ->with(['user:id,name', 'service:id,nom']);

        // Filtre shared_only (uniquement documents partagés avec moi)
        if (isset($validated['shared_only']) && $validated['shared_only']) {
            $query->whereIn('id', $sharedIds);
        } else {
            // Par défaut: mes documents OU partagés avec moi
            $query->where(function ($q) use ($user, $sharedIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('id', $sharedIds);
            });
        }

        // Filtre corbeille (soft deletes)
        if (isset($validated['corbeille'])) {
            if ($validated['corbeille']) {
                // Uniquement les documents supprimés (corbeille)
                $query->onlyTrashed();
            } else {
                // Uniquement les documents actifs (non supprimés)
                $query->whereNull('deleted_at');
            }
        } else {
            // Par défaut: uniquement les documents actifs
            $query->whereNull('deleted_at');
        }

        // Filtres
        if (!empty($validated['type'])) {
            // Autoriser soit MIME complet, soit extension. Si extension (ex: pdf), matcher par LIKE
            $type = $validated['type'];
            if (strpos($type, '/') !== false) {
                $query->where('type', $type);
            } else {
                $query->where('type', 'like', "%.$type");
            }
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['service_id'])) {
            // Vérifier si l'utilisateur appartient au service demandé
            if ($user->service_id !== (int)$validated['service_id']) {
                return response()->json([
                    'message' => 'Vous n\'appartenez pas à ce service. Vous ne pouvez accéder qu\'aux documents de votre propre service.',
                    'your_service_id' => $user->service_id,
                    'requested_service_id' => $validated['service_id'],
                ], 403);
            }
            $query->where('service_id', $validated['service_id']);
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            // Recherche globale: nom, chemin, service.nom, user.name
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nom', 'like', "%{$q}%")
                         ->orWhere('chemin', 'like', "%{$q}%")
                         ->orWhereHas('service', function ($sq) use ($q) {
                             $sq->where('nom', 'like', "%{$q}%");
                         })
                         ->orWhereHas('user', function ($uq) use ($q) {
                             $uq->where('name', 'like', "%{$q}%");
                         });
            });
        }

        // Filtre par extension (ex: pdf, docx, jpg)
        if (!empty($validated['extension'])) {
            $ext = strtolower($validated['extension']);
            $query->where('chemin', 'like', "%.{$ext}");
        }

        // Filtre par taille min
        if (isset($validated['taille_min'])) {
            $query->where('taille', '>=', $validated['taille_min']);
        }

        // Filtre par taille max
        if (isset($validated['taille_max'])) {
            $query->where('taille', '<=', $validated['taille_max']);
        }

        // Filtre favoris
        if (isset($validated['favoris']) && $validated['favoris']) {
            // Joindre la table favoris pour ne récupérer que les documents favoris de l'utilisateur
            $query->whereHas('favoris', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Tri et pagination
        $documents = $query->orderBy($sort, $order)->paginate($perPage);

        // Ajouter le champ 'source' (my/shared) à chaque document
        $documents->getCollection()->transform(function ($doc) use ($user, $sharedIds) {
            $doc->source = $doc->user_id === $user->id ? 'my' : 'shared';
            return $doc;
        });

        return response()->json($documents);
    }
}
