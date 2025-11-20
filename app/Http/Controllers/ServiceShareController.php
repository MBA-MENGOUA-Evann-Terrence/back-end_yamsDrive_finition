<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Service;
use App\Models\DocumentShare;
use App\Models\Notification;
use App\Events\UserActionLogged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceShareController extends Controller
{
    /**
     * Partager un document avec tous les utilisateurs d'un service
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function shareByService(Request $request, string $uuid)
    {
        try {
            // Vérifier l'authentification
            if (!Auth::check()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            $user = Auth::user();

            // Validation des données
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données invalides.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Règle de sécurité : un utilisateur normal ne peut envoyer qu'à son propre service.
            if ($user->role != 1 && $user->service_id != $request->service_id) {
                return response()->json(['message' => 'Action non autorisée. Vous ne pouvez partager des documents qu\'avec votre propre service.'], 403);
            }

            // Vérifier que le document existe et appartient à l'utilisateur
            $document = Document::where('uuid', $uuid)->first();
            
            if (!$document) {
                return response()->json(['message' => 'Document non trouvé.'], 404);
            }

            if ($document->user_id !== $user->id) {
                return response()->json(['message' => 'Vous n\'avez pas l\'autorisation de partager ce document.'], 403);
            }

            // Récupérer le service
            $service = Service::find($request->service_id);
            
            // Récupérer tous les utilisateurs du service (sauf l'expéditeur)
            $serviceUsers = User::where('service_id', $request->service_id)
                                ->where('id', '!=', $user->id)
                                ->get();

            if ($serviceUsers->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun utilisateur trouvé dans ce service (ou vous êtes le seul).',
                    'summary' => [
                        'service_name' => $service->nom,
                        'created' => 0,
                        'skipped' => 0,
                        'excluded' => 1
                    ]
                ], 200);
            }

            $created = 0;
            $skipped = 0;

            foreach ($serviceUsers as $targetUser) {
                // Vérifier si le partage existe déjà
                $existingShare = DocumentShare::where('document_id', $document->id)
                    ->where('user_id', $targetUser->id)
                    ->first();

                if ($existingShare) {
                    $skipped++;
                    continue;
                }

                // Créer le partage
                DocumentShare::create([
                    'document_id' => $document->id,
                    'user_id' => $targetUser->id,
                    'shared_by' => $user->id,
                    'permissions' => 'read', // Permission par défaut
                ]);

                // Créer une notification pour chaque utilisateur du service
                Notification::create([
                    'user_id' => $targetUser->id,
                    'sender_id' => $user->id,
                    'document_id' => $document->id,
                    'type' => 'document_shared',
                    'message' => $user->name . ' a partagé un document avec votre service (' . $service->nom . ') : ' . $document->nom,
                ]);

                $created++;
            }

            // Journaliser l'action
            event(new UserActionLogged(
                'share_by_service', 
                $document,
                ['service_name' => $service->nom, 'users_count' => $created]
            ));

            return response()->json([
                'message' => 'Document partagé avec succès avec le service.',
                'summary' => [
                    'service_name' => $service->nom,
                    'created' => $created,
                    'skipped' => $skipped,
                    'excluded' => 1, // L'expéditeur
                    'total_users_in_service' => $serviceUsers->count() + 1
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du partage par service', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_uuid' => $uuid,
                'service_id' => $request->service_id ?? null
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors du partage.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
