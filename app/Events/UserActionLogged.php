<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserActionLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $model;
    public $newValues;
    public $oldValues;
    public $user;

    /**
     * Crée une nouvelle instance de l'événement.
     *
     * @param string $action L'action effectuée (ex: 'création', 'mise à jour').
     * @param Model $model Le modèle Eloquent affecté.
     * @param array|null $newValues Les nouvelles valeurs.
     * @param array|null $oldValues Les anciennes valeurs.
     */
    public function __construct(string $action, Model $model, ?array $newValues = null, ?array $oldValues = null)
    {
        $this->action = $action;
        $this->model = $model;
        $this->newValues = $newValues;
        $this->oldValues = $oldValues;
        $this->user = Auth::user(); // Capture l'utilisateur authentifié au moment de l'événement.
    }
}
