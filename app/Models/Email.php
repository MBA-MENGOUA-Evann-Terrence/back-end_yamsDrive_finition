<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'objet',
        'message',
        'statut',
        'type',
        'date_envoi',
        'date_lecture',
        'prospect_id',
        'utilisateur_id',
    ];
}
