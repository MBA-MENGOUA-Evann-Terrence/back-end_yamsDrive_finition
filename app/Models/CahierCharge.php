<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CahierCharge extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'client_nom',
        'email_nom',
        'titre',
        'objectifs',
        'specifications',
        'fonctionnalites',
        'contraintes',
        'budget',
        'delai',
        'priorisation',
        'design_preferencence',
        'hebergement',
        'utilisateur_id',
        'maintenance',
    ];
}
