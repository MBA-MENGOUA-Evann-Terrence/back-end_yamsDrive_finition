<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentShare;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    /**
     * Récupère les statistiques globales de l'application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalStats()
    {
        try {
            $totalUsers = User::count();
            $totalDocuments = Document::count();
            $totalSharedDocuments = DocumentShare::count();

            return response()->json([
                'data' => [
                    'total_users' => $totalUsers,
                    'total_documents' => $totalDocuments,
                    'total_shared_documents' => $totalSharedDocuments,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques globales: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Récupère les statistiques d'activité des documents sur une période donnée.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocumentActivity(Request $request)
    {
        try {
            \Carbon\Carbon::setLocale('fr');
            $startDate = now()->subMonths(5)->startOfMonth();

            // Requête pour les documents créés
            $createdCounts = Document::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('year', 'month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . $item->month;
                });

            // Requête pour les documents partagés
            $sharedCounts = DocumentShare::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('year', 'month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . $item->month;
                });

            $labels = [];
            $createdData = [];
            $sharedData = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-n');
                $labels[] = ucfirst($date->translatedFormat('F'));

                $createdData[] = $createdCounts->get($monthKey)->count ?? 0;
                $sharedData[] = $sharedCounts->get($monthKey)->count ?? 0;
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Documents Créés',
                        'data' => $createdData,
                        'backgroundColor' => '#42A5F5',
                        'borderColor' => '#1E88E5',
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Documents Partagés',
                        'data' => $sharedData,
                        'backgroundColor' => '#9CCC65',
                        'borderColor' => '#7CB342',
                        'tension' => 0.4
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'activité des documents: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Récupère la répartition du stockage par type de fichier.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStorageBreakdown()
    {
        try {
            $storageByType = Document::select(
                'type',
                DB::raw('SUM(taille) as total_size')
            )
            ->groupBy('type')
            ->get();

            $breakdown = [
                'images' => 0,
                'videos' => 0,
                'audio' => 0,
                'documents' => 0,
                'archives' => 0,
                'autres' => 0,
            ];

            foreach ($storageByType as $item) {
                if (str_starts_with($item->type, 'image/')) {
                    $breakdown['images'] += $item->total_size;
                } elseif (str_starts_with($item->type, 'video/')) {
                    $breakdown['videos'] += $item->total_size;
                } elseif (str_starts_with($item->type, 'audio/')) {
                    $breakdown['audio'] += $item->total_size;
                } elseif (in_array($item->type, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/vnd.ms-excel'])) {
                    $breakdown['documents'] += $item->total_size;
                } elseif (in_array($item->type, ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'])) {
                    $breakdown['archives'] += $item->total_size;
                } else {
                    $breakdown['autres'] += $item->total_size;
                }
            }

            return response()->json(['data' => $breakdown]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la répartition du stockage: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Récupère le nombre d'utilisateurs actifs sur une période donnée.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActivity(Request $request)
    {
        try {
            \Carbon\Carbon::setLocale('fr');
            $startDate = now()->subMonths(5)->startOfMonth();

            $activeUsersCounts = DB::table('personal_access_tokens')
                ->select(
                    DB::raw('YEAR(last_used_at) as year'),
                    DB::raw('MONTH(last_used_at) as month'),
                    DB::raw('COUNT(DISTINCT tokenable_id) as count')
                )
                ->where('last_used_at', '>=', $startDate)
                ->groupBy('year', 'month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . $item->month;
                });

            $labels = [];
            $activeUsersData = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-n');
                $labels[] = ucfirst($date->translatedFormat('F'));
                $activeUsersData[] = $activeUsersCounts->get($monthKey)->count ?? 0;
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Utilisateurs Actifs',
                        'data' => $activeUsersData,
                        'backgroundColor' => '#FFCA28', // Ambre
                        'borderColor' => '#FFA000',
                        'tension' => 0.4
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'activité des utilisateurs: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Récupère un résumé des actions des utilisateurs sur une période donnée.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentActions(Request $request)
    {
        try {
            $period = $request->query('period', 'month'); // day, week, month, year
            $startDate = now();

            switch ($period) {
                case 'day':
                    $startDate = $startDate->subDay();
                    break;
                case 'week':
                    $startDate = $startDate->subWeek();
                    break;
                case 'year':
                    $startDate = $startDate->subYear();
                    break;
                case 'month':
                default:
                    $startDate = $startDate->subMonth();
                    break;
            }

            $actions = DB::table('log_actions')
                ->where('created_at', '>=', $startDate)
                ->select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->pluck('total', 'action');

            return response()->json([
                'data' => [
                    'period' => $period,
                    'start_date' => $startDate->toDateTimeString(),
                    'actions' => $actions,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des actions récentes: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Génère les données pour un graphique des actions utilisateurs sur les 6 derniers mois.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActionsChart()
    {
        try {
            \Carbon\Carbon::setLocale('fr');
            $startDate = now()->subMonths(5)->startOfMonth();

            // 1. Récupérer toutes les données en une seule requête
            $results = DB::table('log_actions')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    'action',
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('year', 'month', 'action')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            // 2. Préparer les structures de données
            $labels = [];
            $datasets = [];
            $monthMap = [];

            // Créer les labels et une map pour les 6 derniers mois
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-n');
                $labels[] = ucfirst($date->translatedFormat('F'));
                $monthMap[$monthKey] = 0; // Initialiser chaque mois à 0
            }

            // 3. Transformer les résultats de la requête
            $actionData = [];
            foreach ($results as $result) {
                $monthKey = $result->year . '-' . $result->month;
                $action = $result->action;
                if (!isset($actionData[$action])) {
                    $actionData[$action] = $monthMap;
                }
                $actionData[$action][$monthKey] = $result->count;
            }

            // 4. Construire les datasets pour le graphique
            $colors = [
                ['background' => '#42A5F5', 'border' => '#1E88E5'], // Bleu
                ['background' => '#9CCC65', 'border' => '#7CB342'], // Vert
                ['background' => '#FF7043', 'border' => '#E64A19'], // Orange
                ['background' => '#7E57C2', 'border' => '#5E35B1'], // Violet
                ['background' => '#EC407A', 'border' => '#D81B60'], // Rose
            ];
            $colorIndex = 0;

            foreach ($actionData as $action => $data) {
                $datasets[] = [
                    'label' => ucfirst($action),
                    'data' => array_values($data),
                    'backgroundColor' => $colors[$colorIndex % count($colors)]['background'],
                    'borderColor' => $colors[$colorIndex % count($colors)]['border'],
                    'tension' => 0.4
                ];
                $colorIndex++;
            }

            return response()->json(['labels' => $labels, 'datasets' => $datasets]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du graphique des actions utilisateurs: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur lors de la récupération des statistiques.'], 500);
        }
    }

    /**
     * Récupère les statistiques détaillées d'envoi de documents (partages).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocumentSharingStats(Request $request)
    {
        try {
            // 1. Liste détaillée des partages (qui a envoyé à qui, quand)
            $sharingHistory = DocumentShare::with(['document:id,nom,type,taille', 'user:id,name,email,service_id', 'sharedBy:id,name,email,service_id'])
                ->select('id', 'document_id', 'user_id', 'shared_by', 'permission_level', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(100) // Limiter à 100 derniers partages
                ->get()
                ->map(function ($share) {
                    return [
                        'id' => $share->id,
                        'document_nom' => $share->document->nom ?? 'Document supprimé',
                        'document_type' => $share->document->type ?? null,
                        'document_taille' => $share->document->taille ?? null,
                        'envoyeur' => [
                            'id' => $share->sharedBy->id ?? null,
                            'nom' => $share->sharedBy->name ?? 'Utilisateur inconnu',
                            'email' => $share->sharedBy->email ?? null,
                            'service_id' => $share->sharedBy->service_id ?? null,
                        ],
                        'destinataire' => [
                            'id' => $share->user->id ?? null,
                            'nom' => $share->user->name ?? 'Utilisateur inconnu',
                            'email' => $share->user->email ?? null,
                            'service_id' => $share->user->service_id ?? null,
                        ],
                        'permission' => $share->permission_level,
                        'date' => $share->created_at->format('Y-m-d H:i:s'),
                        'date_humaine' => $share->created_at->diffForHumans(),
                    ];
                });

            // 2. Nombre de documents envoyés par utilisateur
            $documentsByUser = DocumentShare::select('document_shares.shared_by', 'users.name', 'users.email', DB::raw('COUNT(document_shares.id) as total'))
                ->join('users', 'document_shares.shared_by', '=', 'users.id')
                ->groupBy('document_shares.shared_by', 'users.name', 'users.email')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->shared_by,
                        'user_name' => $item->name,
                        'user_email' => $item->email,
                        'total_envoyes' => $item->total,
                    ];
                });

            // 3. Nombre de documents envoyés par service (basé sur le service de l'envoyeur)
            $documentsByService = DocumentShare::join('users', 'document_shares.shared_by', '=', 'users.id')
                ->leftJoin('services', 'users.service_id', '=', 'services.id')
                ->select('services.id', 'services.nom', DB::raw('COUNT(document_shares.id) as total'))
                ->whereNotNull('services.id')
                ->groupBy('services.id', 'services.nom')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'service_id' => $item->id,
                        'service_nom' => $item->nom,
                        'total_envoyes' => $item->total,
                    ];
                });

            // 4. Nombre total de documents partagés
            $totalShared = DocumentShare::count();

            // 5. Nombre de documents reçus par utilisateur
            $documentsReceivedByUser = DocumentShare::select('document_shares.user_id', 'users.name', 'users.email', DB::raw('COUNT(document_shares.id) as total'))
                ->join('users', 'document_shares.user_id', '=', 'users.id')
                ->groupBy('document_shares.user_id', 'users.name', 'users.email')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->name,
                        'user_email' => $item->email,
                        'total_recus' => $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_partages' => $totalShared,
                    'historique_partages' => $sharingHistory,
                    'documents_par_utilisateur' => $documentsByUser,
                    'documents_par_service' => $documentsByService,
                    'documents_recus_par_utilisateur' => $documentsReceivedByUser,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques de partage: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des statistiques.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les flux de documents entre services pour la visualisation réseau.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLiveDocumentFlow()
    {
        try {
            // Récupérer les flux de documents entre services
            $flows = DB::table('document_shares')
                ->select([
                    'services_source.id as source_service_id',
                    'services_source.nom as source_service',
                    'services_dest.id as dest_service_id',
                    'services_dest.nom as dest_service',
                    'documents.type',
                    DB::raw('COUNT(document_shares.id) as total'),
                    DB::raw('SUM(documents.taille) as total_size')
                ])
                ->join('users as u1', 'document_shares.shared_by', '=', 'u1.id')
                ->leftJoin('services as services_source', 'u1.service_id', '=', 'services_source.id')
                ->join('users as u2', 'document_shares.user_id', '=', 'u2.id')
                ->leftJoin('services as services_dest', 'u2.service_id', '=', 'services_dest.id')
                ->join('documents', 'document_shares.document_id', '=', 'documents.id')
                ->whereNotNull('services_source.id')
                ->whereNotNull('services_dest.id')
                ->groupBy('services_source.id', 'services_source.nom', 'services_dest.id', 'services_dest.nom', 'documents.type')
                ->get();

            // Construire les nœuds (services uniques)
            $servicesMap = [];
            $nodes = [];
            $nodeIndex = 0;

            foreach ($flows as $flow) {
                // Ajouter le service source
                if (!isset($servicesMap[$flow->source_service_id])) {
                    $servicesMap[$flow->source_service_id] = $nodeIndex;
                    $nodes[] = [
                        'id' => $nodeIndex,
                        'service_id' => $flow->source_service_id,
                        'label' => $flow->source_service,
                        'type' => 'service',
                        'size' => 20
                    ];
                    $nodeIndex++;
                }

                // Ajouter le service destination
                if (!isset($servicesMap[$flow->dest_service_id])) {
                    $servicesMap[$flow->dest_service_id] = $nodeIndex;
                    $nodes[] = [
                        'id' => $nodeIndex,
                        'service_id' => $flow->dest_service_id,
                        'label' => $flow->dest_service,
                        'type' => 'service',
                        'size' => 20
                    ];
                    $nodeIndex++;
                }
            }

            // Calculer la taille des nœuds en fonction de l'activité
            $activityCount = [];
            foreach ($flows as $flow) {
                $sourceId = $servicesMap[$flow->source_service_id];
                $destId = $servicesMap[$flow->dest_service_id];
                
                if (!isset($activityCount[$sourceId])) {
                    $activityCount[$sourceId] = 0;
                }
                if (!isset($activityCount[$destId])) {
                    $activityCount[$destId] = 0;
                }
                
                $activityCount[$sourceId] += $flow->total;
                $activityCount[$destId] += $flow->total;
            }

            // Mettre à jour la taille des nœuds
            foreach ($nodes as &$node) {
                $node['size'] = 15 + ($activityCount[$node['id']] ?? 0) * 2;
                $node['activity'] = $activityCount[$node['id']] ?? 0;
            }

            // Construire les liens
            $links = [];
            $linkMap = [];

            foreach ($flows as $flow) {
                $sourceIndex = $servicesMap[$flow->source_service_id];
                $targetIndex = $servicesMap[$flow->dest_service_id];
                $linkKey = $sourceIndex . '-' . $targetIndex;

                if (!isset($linkMap[$linkKey])) {
                    $linkMap[$linkKey] = [
                        'source' => $sourceIndex,
                        'target' => $targetIndex,
                        'value' => 0,
                        'total_size' => 0,
                        'types' => []
                    ];
                }

                $linkMap[$linkKey]['value'] += $flow->total;
                $linkMap[$linkKey]['total_size'] += $flow->total_size ?? 0;
                
                if (!in_array($flow->type, $linkMap[$linkKey]['types'])) {
                    $linkMap[$linkKey]['types'][] = $flow->type;
                }
            }

            // Convertir les liens en tableau
            foreach ($linkMap as $link) {
                $links[] = [
                    'source' => $link['source'],
                    'target' => $link['target'],
                    'value' => $link['value'],
                    'total_size' => $link['total_size'],
                    'types' => $link['types'],
                    'width' => min(10, 1 + $link['value'] * 0.5) // Épaisseur du lien
                ];
            }

            // Statistiques globales
            $totalFlows = array_sum(array_column($links, 'value'));
            $totalSize = array_sum(array_column($links, 'total_size'));

            return response()->json([
                'success' => true,
                'data' => [
                    'nodes' => array_values($nodes),
                    'links' => array_values($links),
                    'stats' => [
                        'total_flows' => $totalFlows,
                        'total_size' => $totalSize,
                        'total_services' => count($nodes),
                        'total_connections' => count($links)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des flux de documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des flux.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les utilisateurs actuellement connectés et les activités récentes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveUsersAndActivity()
    {
        try {
            // 1. Utilisateurs connectés (actifs dans les 15 dernières minutes)
            $activeUsers = DB::table('personal_access_tokens')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'services.nom as service_nom',
                    'services.id as service_id',
                    DB::raw('MAX(personal_access_tokens.last_used_at) as last_activity')
                )
                ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
                ->leftJoin('services', 'users.service_id', '=', 'services.id')
                ->where('personal_access_tokens.last_used_at', '>=', now()->subMinutes(15))
                ->groupBy('users.id', 'users.name', 'users.email', 'services.nom', 'services.id')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'service_id' => $user->service_id,
                        'service_nom' => $user->service_nom ?? 'Sans service',
                        'last_activity' => $user->last_activity,
                        'status' => 'online'
                    ];
                });

            // 2. Activités récentes (partages des 5 dernières minutes)
            $recentActivities = DocumentShare::with([
                    'document:id,nom,type,taille',
                    'user:id,name,service_id',
                    'sharedBy:id,name,service_id'
                ])
                ->select('id', 'document_id', 'user_id', 'shared_by', 'created_at')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($share) {
                    return [
                        'id' => $share->id,
                        'document_nom' => $share->document->nom ?? 'Document supprimé',
                        'document_type' => $share->document->type ?? null,
                        'from_user' => [
                            'id' => $share->sharedBy->id ?? null,
                            'name' => $share->sharedBy->name ?? 'Utilisateur inconnu',
                            'service_id' => $share->sharedBy->service_id ?? null,
                        ],
                        'to_user' => [
                            'id' => $share->user->id ?? null,
                            'name' => $share->user->name ?? 'Utilisateur inconnu',
                            'service_id' => $share->user->service_id ?? null,
                        ],
                        'timestamp' => $share->created_at->toIso8601String(),
                        'time_ago' => $share->created_at->diffForHumans(),
                    ];
                });

            // 3. Statistiques des utilisateurs connectés par service
            $usersByService = $activeUsers->groupBy('service_nom')->map(function ($users, $service) {
                return [
                    'service' => $service,
                    'count' => $users->count(),
                    'users' => $users->pluck('name')->toArray()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'active_users' => $activeUsers,
                    'total_active' => $activeUsers->count(),
                    'recent_activities' => $recentActivities,
                    'users_by_service' => $usersByService,
                    'last_update' => now()->toIso8601String()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des utilisateurs actifs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des utilisateurs actifs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
