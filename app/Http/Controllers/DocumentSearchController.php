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
     * - Filtres: type, user_id (expéditeur), service_id, q (mot-clé), date_from, date_to
     * - Tri: sort (nom|created_at|updated_at|taille), order (asc|desc)
     * - Pagination: per_page
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
            'q' => 'sometimes|string|max:255',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'sort' => 'sometimes|in:nom,created_at,updated_at,taille',
            'order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = (int)($validated['per_page'] ?? 15);
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        // Récupérer les IDs des documents partagés avec l'utilisateur (non expirés)
        $sharedIds = DocumentShare::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('document_id');

        // Base: documents de l'utilisateur OU partagés avec lui
        $query = Document::query()
            ->with(['user:id,name', 'service:id,nom'])
            ->where(function ($q) use ($user, $sharedIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('id', $sharedIds);
            });

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
            $query->where('service_id', $validated['service_id']);
        }

        if (!empty($validated['q'])) {
            $q = $validated['q'];
            $query->where('nom', 'like', "%{$q}%");
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Tri et pagination
        $documents = $query->orderBy($sort, $order)->paginate($perPage);

        return response()->json($documents);
    }
}
