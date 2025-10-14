<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'sujet',
        'description',
        'statut',
        'date_resolution',
        'client_id',
        'utilisateur_id',
    ];
}
