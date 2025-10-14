<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'type' => $this->type,
            'taille' => $this->taille,
            'description' => $this->description,
            'created_at' => $this->created_at,
            // On ne charge pas la relation 'user' ici pour éviter la boucle
        ];
    }
}
