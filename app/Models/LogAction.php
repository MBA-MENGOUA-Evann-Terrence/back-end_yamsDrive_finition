<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAction extends Model
{
    /**
     * The model's event map.
     *
     * @var array
     */
    protected $dispatchesEvents = [];
    use HasFactory;
    protected $fillable = [
        'action',
        'table_affectee',
        'user_id',
        'nouvelles_valeurs',
        'anciennes_valeurs',
        'adresse_ip',
    ];
}
