<?php

namespace App\Observers;

use App\Models\User;
use App\Traits\LogActionTrait;

class UserObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'un utilisateur.
     */
    public function created(User $user)
    {
        // TEMPORAIRE: Désactivé pour éviter la récursion
        return;
        // $this->logAction('création', $user, $user->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'un utilisateur.
     */
    public function updated(User $user)
    {
        // TEMPORAIRE: Désactivé pour éviter la récursion
        return;
        // $this->logAction('mise à jour', $user, $user->getChanges(), $user->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'un utilisateur.
     */
    public function deleted(User $user)
    {
        // TEMPORAIRE: Désactivé pour éviter la récursion
        return;
        // $this->logAction('suppression', $user, null, $user->getAttributes());
    }
}
