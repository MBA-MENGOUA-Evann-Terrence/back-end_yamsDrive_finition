<?php

namespace App\Observers;

use App\Models\PieceJointe;
use App\Traits\LogActionTrait;

class PieceJointeObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'une pièce jointe.
     */
    public function created(PieceJointe $pieceJointe)
    {
        $this->logAction('création', $pieceJointe, $pieceJointe->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'une pièce jointe.
     */
    public function updated(PieceJointe $pieceJointe)
    {
        $this->logAction('mise à jour', $pieceJointe, $pieceJointe->getChanges(), $pieceJointe->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'une pièce jointe.
     */
    public function deleted(PieceJointe $pieceJointe)
    {
        $this->logAction('suppression', $pieceJointe, null, $pieceJointe->getAttributes());
    }
}
