<?php

namespace App\Observers;

use App\Models\Categorie;
use App\Traits\LogActionTrait;

class CategorieObserver
{
    use LogActionTrait;

    /**
     * Gère l'événement de création d'une catégorie.
     */
    public function created(Categorie $categorie)
    {
        $this->logAction('création', $categorie, $categorie->getAttributes());
    }

    /**
     * Gère l'événement de mise à jour d'une catégorie.
     */
    public function updated(Categorie $categorie)
    {
        $this->logAction('mise à jour', $categorie, $categorie->getChanges(), $categorie->getOriginal());
    }

    /**
     * Gère l'événement de suppression d'une catégorie.
     */
    public function deleted(Categorie $categorie)
    {
        $this->logAction('suppression', $categorie, null, $categorie->getAttributes());
    }
}
