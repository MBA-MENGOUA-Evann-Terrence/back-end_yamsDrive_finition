<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'document_id',
        'token',
        'shared_by',
        'permission_level',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Obtenir le document associé à ce lien de partage.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Obtenir l'utilisateur qui a créé le lien.
     */
    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Vérifier si le lien est expiré.
     */
    public function isExpired()
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }
}
