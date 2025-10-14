<?php

namespace App\Traits;

use App\Models\LogAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogActionTrait
{

    /**
     * Enregistre une action dans les logs.
     *
     * @param string $action L'action effectuée (e.g., 'création', 'mise à jour').
     * @param Model $model L'instance du modèle concerné.
     * @param array|null $nouvelles_valeurs Les nouvelles valeurs du modèle.
     * @param array|null $anciennes_valeurs Les anciennes valeurs du modèle.
     */
    protected function logAction(string $action, Model $model, ?array $nouvelles_valeurs = null, ?array $anciennes_valeurs = null)
    {
        // Empêche la boucle infinie en désactivant les événements pendant la création du log.
        if ($model instanceof \App\Models\LogAction) {
            return;
        }

        // Utilise withoutEvents pour une protection robuste contre les boucles de récursion.
        LogAction::withoutEvents(function () use ($action, $model, $nouvelles_valeurs, $anciennes_valeurs) {
            LogAction::create([
                'action' => $action,
                'table_affectee' => $model->getTable(),
                'user_id' => Auth::id() ?? null,
                'nouvelles_valeurs' => json_encode($nouvelles_valeurs),
                'anciennes_valeurs' => json_encode($anciennes_valeurs),
                'adresse_ip' => request()->ip(),
            ]);
        });
    }
}
