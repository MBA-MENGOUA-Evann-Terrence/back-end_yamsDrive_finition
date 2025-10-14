<?php

namespace App\Observers;

use App\Models\ShareLink;
use App\Traits\LogActionTrait;

class ShareLinkObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'un lien de partage.
     */
    public function created(ShareLink $shareLink)
    {
        $this->logAction('création', $shareLink, $shareLink->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'un lien de partage.
     */
    public function updated(ShareLink $shareLink)
    {
        $this->logAction('mise à jour', $shareLink, $shareLink->getChanges(), $shareLink->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'un lien de partage.
     */
    public function deleted(ShareLink $shareLink)
    {
        $this->logAction('suppression', $shareLink, null, $shareLink->getAttributes());
    }
}
