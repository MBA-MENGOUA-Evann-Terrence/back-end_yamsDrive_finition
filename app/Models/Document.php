<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\UserActionLogged;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nom',
        'chemin',
        'type',
        'taille',
        'description',
        'user_id',
        'service_id',
    ];
    
    // Cacher les relations qui peuvent causer des boucles de sérialisation
    protected $hidden = ['user'];

    /**
     * Boot the model and register model event listeners for journaling actions.
     */
    protected static function booted(): void
    {
        // TEMPORAIRE: Tous les événements désactivés pour éliminer la récursion
        // Les hooks deleted/restored/forceDeleted sont complètement neutralisés
        
        /*
        // Soft delete (deleted_at filled)
        static::deleted(function (Document $document) {
            // For soft-deletes, the model remains and "trashed()" returns true
            if (method_exists($document, 'trashed') && $document->trashed()) {
                $user = Auth::user();
                $context = [
                    'document_uuid' => $document->uuid,
                    'document_id' => $document->id,
                    'user_id' => optional($user)->id,
                ];
                // Journalisation allégée sans déclencher d'événements pour éviter toute récursion
                Log::info('Document soft-deleted', $context);
            }
        });

        // Restore
        static::restored(function (Document $document) {
            $user = Auth::user();
            $context = [
                'document_uuid' => $document->uuid,
                'document_id' => $document->id,
                'user_id' => optional($user)->id,
            ];
            // Journalisation allégée sans déclencher d'événements pour éviter toute récursion
            Log::info('Document restored', $context);
        });

        // Force delete (permanent)
        static::forceDeleted(function (Document $document) {
            $user = Auth::user();
            $context = [
                'document_uuid' => $document->uuid,
                'document_id' => $document->id,
                'user_id' => optional($user)->id,
            ];
            // Journalisation allégée sans déclencher d'événements pour éviter toute récursion
            Log::info('Document force-deleted', $context);
        });
        */
    }

    /**
     * Get the user that owns the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service associated with the document.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    
    /**
     * Get the shares for the document.
     */
    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }
    
    /**
     * Vérifie si le document est partagé avec un utilisateur spécifique.
     *
     * @param int $userId
     * @return bool
     */
    public function isSharedWithUser(int $userId)
    {
        return $this->shares()
            ->where('user_id', $userId)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->exists();
    }
    
    /**
     * Récupère tous les utilisateurs avec qui ce document est partagé.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sharedWithUsers()
    {
        return User::whereIn('id', $this->shares()->pluck('user_id'))->get();
    }
}
