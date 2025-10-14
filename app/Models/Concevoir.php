<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concevoir extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'facture_id',
        'service_id',
        'utilisateur_id',
        'nouvelles_valeurs',
        'anciennes_valeurs',
        'adresse_ip',
    ];
}
