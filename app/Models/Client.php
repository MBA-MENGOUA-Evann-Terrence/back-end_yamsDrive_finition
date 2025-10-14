<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
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
        'nom_entreprise',
        'date_inscription',
        'service_id',
        'prospect_id',
        'description',
    ];
}
