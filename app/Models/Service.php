<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'nom',
        'description',
        'prix',
        'statut',
    ];

    /**
     * Relation avec les documents associés à ce service.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
