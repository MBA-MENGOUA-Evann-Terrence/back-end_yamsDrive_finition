<?php

namespace App\Listeners;

use App\Events\UserActionLogged;
use App\Models\LogAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserAction
{
    /**
     * Crée l'écouteur d'événement.
     */
    public function __construct()
    {
        //
    }

    /**
     * Gère l'événement.
     */
    public function handle(UserActionLogged $event): void
    {
        // DÉSACTIVÉ TEMPORAIREMENT: Retour immédiat pour éviter la récursion infinie
        return;
        
        // Obtenir le dispatcher d'événements actuel pour le modèle LogAction
        $dispatcher = LogAction::getEventDispatcher();

        // Le retirer temporairement pour empêcher tout événement de se déclencher
        LogAction::unsetEventDispatcher();

        try {
            if ($event->user) {
                LogAction::create([
                    'action' => $event->action,
                    'table_affectee' => $event->model->getTable(),
                    'id_affecte' => $event->model->getKey(),
                    'user_id' => $event->user->id,
                    'nouvelles_valeurs' => json_encode($event->newValues),
                    'anciennes_valeurs' => json_encode($event->oldValues),
                    'adresse_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        } finally {
            // Toujours restaurer le dispatcher d'événements, même en cas d'erreur
            if ($dispatcher) {
                LogAction::setEventDispatcher($dispatcher);
            }
        }
    }
}
