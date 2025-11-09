<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentFilterController extends Controller
{
    /**
     * Retourne les options disponibles pour les filtres avancés
     * - Types de documents (basés sur les documents accessibles)
     * - Personnes (propriétaires des documents accessibles)
     * - Services (tous les services, avec indication du service de l'utilisateur)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Récupérer les IDs des documents accessibles (mes docs + partagés avec moi)
        $sharedIds = DocumentShare::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('document_id');

        $accessibleDocIds = Document::where('user_id', $user->id)
            ->orWhereIn('id', $sharedIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        // 1. Types de documents disponibles (basés sur les documents accessibles)
        $types = Document::whereIn('id', $accessibleDocIds)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                // Extraire une catégorie lisible du MIME type
                $mimeType = $item->type;
                $category = $this->getCategoryFromMimeType($mimeType);
                
                return [
                    'mime_type' => $mimeType,
                    'category' => $category,
                    'count' => $item->count,
                ];
            })
            // Grouper par catégorie pour éviter les doublons
            ->groupBy('category')
            ->map(function ($group, $category) {
                return [
                    'category' => $category,
                    'count' => $group->sum('count'),
                    'mime_types' => $group->pluck('mime_type')->unique()->values(),
                ];
            })
            ->values();

        // 2. Personnes (propriétaires des documents accessibles)
        $userIds = Document::whereIn('id', $accessibleDocIds)
            ->distinct()
            ->pluck('user_id');

        $people = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($person) use ($accessibleDocIds) {
                $docCount = Document::where('user_id', $person->id)
                    ->whereIn('id', $accessibleDocIds)
                    ->count();
                
                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'email' => $person->email,
                    'document_count' => $docCount,
                ];
            });

        // 3. Services (tous les services, avec indication du service de l'utilisateur)
        $services = Service::select('id', 'nom', 'description')
            ->orderBy('nom')
            ->get()
            ->map(function ($service) use ($user, $accessibleDocIds) {
                $isUserService = $user->service_id === $service->id;
                
                // Compter les documents du service parmi les accessibles
                $docCount = Document::where('service_id', $service->id)
                    ->whereIn('id', $accessibleDocIds)
                    ->count();
                
                return [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'description' => $service->description,
                    'is_user_service' => $isUserService,
                    'document_count' => $docCount,
                    'accessible' => $isUserService, // Seul le service de l'utilisateur est accessible
                ];
            });

        return response()->json([
            'types' => $types,
            'people' => $people,
            'services' => $services,
            'user_service_id' => $user->service_id,
        ]);
    }

    /**
     * Extrait une catégorie lisible à partir d'un MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function getCategoryFromMimeType($mimeType)
    {
        // Mapping des MIME types vers des catégories lisibles
        $categories = [
            'image' => 'Image',
            'video' => 'Vidéo',
            'audio' => 'Audio',
            'application/pdf' => 'PDF',
            'application/msword' => 'Word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
            'application/vnd.ms-excel' => 'Excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'application/vnd.ms-powerpoint' => 'PowerPoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
            'text/plain' => 'Texte',
            'application/zip' => 'Archive',
            'application/x-rar-compressed' => 'Archive',
            'application/x-7z-compressed' => 'Archive',
        ];

        // Vérifier les correspondances exactes
        if (isset($categories[$mimeType])) {
            return $categories[$mimeType];
        }

        // Vérifier les préfixes (ex: image/*, video/*)
        foreach ($categories as $pattern => $category) {
            if (strpos($mimeType, $pattern) === 0) {
                return $category;
            }
        }

        // Par défaut, retourner "Autre"
        return 'Autre';
    }
}
