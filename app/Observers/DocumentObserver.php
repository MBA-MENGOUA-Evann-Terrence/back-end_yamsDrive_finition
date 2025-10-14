<?php

namespace App\Observers;

use App\Models\Document;
use App\Traits\LogActionTrait;

class DocumentObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'un document.
     */
    public function created(Document $document)
    {
        // DÉSACTIVÉ: La journalisation est maintenant gérée manuellement dans DocumentController
        // pour éviter la double journalisation et la récursion infinie
        // $this->logAction('création', $document, $document->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'un document.
     */
    public function updated(Document $document)
    {
        // DÉSACTIVÉ: La journalisation est maintenant gérée manuellement dans DocumentController
        // $this->logAction('mise à jour', $document, $document->getChanges(), $document->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'un document.
     */
    public function deleted(Document $document)
    {
        // DÉSACTIVÉ: La journalisation est maintenant gérée manuellement dans DocumentController
        // $this->logAction('suppression', $document, null, $document->getAttributes());
    }
}
