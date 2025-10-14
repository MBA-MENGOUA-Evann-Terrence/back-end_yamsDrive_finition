<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueCahier extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'action',
        'detail',
        'utilisateur_id',
        'cahiercharge_id',
    ];
}
