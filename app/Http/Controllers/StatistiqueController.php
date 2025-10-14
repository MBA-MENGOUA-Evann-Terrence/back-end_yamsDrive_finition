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
}
