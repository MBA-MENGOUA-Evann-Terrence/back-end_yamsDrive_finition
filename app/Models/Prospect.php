<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone1',
        'telephone2',
        'adresse',
        'type',
        'ville',
        'statut',
        'source',
        'note',
        'utilisateur_id',
    ];
}
