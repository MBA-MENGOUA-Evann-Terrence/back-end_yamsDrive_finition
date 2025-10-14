<?php

namespace App\Observers;

use App\Models\CahierCharge;
use App\Traits\LogActionTrait;

class CahierChargeObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'un cahier de charges.
     */
    public function created(CahierCharge $cahierCharge)
    {
        $this->logAction('création', $cahierCharge, $cahierCharge->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'un cahier de charges.
     */
    public function updated(CahierCharge $cahierCharge)
    {
        $this->logAction('mise à jour', $cahierCharge, $cahierCharge->getChanges(), $cahierCharge->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'un cahier de charges.
     */
    public function deleted(CahierCharge $cahierCharge)
    {
        $this->logAction('suppression', $cahierCharge, null, $cahierCharge->getAttributes());
    }
}
