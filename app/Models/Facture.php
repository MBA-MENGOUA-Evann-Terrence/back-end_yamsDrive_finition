<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'numero',
        'montant_ht',
        'montant_ttc',
        'taux_tva',
        'statut',
        'date_emission',
        'date_echeance',
        'date_paiement',
        'mode_paiement',
        'reference_paiement',
        'fichier_pdf',
        'client_id',
        'utilisateur_id',
    ];
}
