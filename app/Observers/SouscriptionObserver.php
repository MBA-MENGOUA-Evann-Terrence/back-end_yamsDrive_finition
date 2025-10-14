<?php

namespace App\Observers;

use App\Models\Souscription;
use App\Traits\LogActionTrait;

class SouscriptionObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'une souscription.
     */
    public function created(Souscription $souscription)
    {
        $this->logAction('création', $souscription, $souscription->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'une souscription.
     */
    public function updated(Souscription $souscription)
    {
        $this->logAction('mise à jour', $souscription, $souscription->getChanges(), $souscription->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'une souscription.
     */
    public function deleted(Souscription $souscription)
    {
        $this->logAction('suppression', $souscription, null, $souscription->getAttributes());
    }
}
