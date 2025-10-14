<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'titre',
        'message',
        'statut',
        'client_id',
        'utilisateur_id',
    ];
}
