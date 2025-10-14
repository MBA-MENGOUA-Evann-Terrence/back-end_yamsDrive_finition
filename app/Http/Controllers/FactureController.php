<?php

namespace App\Http\Controllers;
use App\Models\Facture;
use App\Http\Resources\FactureResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FactureController extends Controller
{

    public function index() 
    {
        $factures = Facture::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($factures)->count();

        return FactureResource::collection($this->AsdecodefilterBy($factures));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'numero' => 'required',
               //'montant_ht' => 'required',
               //'montant_ttc' => 'required',
               //'taux_tva' => 'required',
               //'statut' => 'required',
               //'date_emission' => 'required',
               //'date_echeance' => 'required',
               //'date_paiement' => 'required',
               //'mode_paiement' => 'required',
               //'reference_paiement' => 'required',
               //'fichier_pdf' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'numero.required' => 'Le champ numero ne peut etre vide',
               //'montant_ht.required' => 'Le champ montant_ht ne peut etre vide',
               //'montant_ttc.required' => 'Le champ montant_ttc ne peut etre vide',
               //'taux_tva.required' => 'Le champ taux_tva ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'date_emission.required' => 'Le champ date_emission ne peut etre vide',
               //'date_echeance.required' => 'Le champ date_echeance ne peut etre vide',
               //'date_paiement.required' => 'Le champ date_paiement ne peut etre vide',
               //'mode_paiement.required' => 'Le champ mode_paiement ne peut etre vide',
               //'reference_paiement.required' => 'Le champ reference_paiement ne peut etre vide',
               //'fichier_pdf.required' => 'Le champ fichier_pdf ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $factures = Facture::latest();
        if ($factures
        ->where('numero', $request->numero)
        ->where('montant_ht', $request->montant_ht)
        ->where('montant_ttc', $request->montant_ttc)
        ->where('taux_tva', $request->taux_tva)
        ->where('statut', $request->statut)
        ->where('date_emission', $request->date_emission)
        ->where('date_echeance', $request->date_echeance)
        ->where('date_paiement', $request->date_paiement)
        ->where('mode_paiement', $request->mode_paiement)
        ->where('reference_paiement', $request->reference_paiement)
        ->where('fichier_pdf', $request->fichier_pdf)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $facture = Facture::create($request->all());
        return $this->sendApiResponse($facture, 'Facture ajouté', 201);
    }

    public function show($id)
    {
        return new FactureResource(Facture::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'numero' => 'required',
               //'montant_ht' => 'required',
               //'montant_ttc' => 'required',
               //'taux_tva' => 'required',
               //'statut' => 'required',
               //'date_emission' => 'required',
               //'date_echeance' => 'required',
               //'date_paiement' => 'required',
               //'mode_paiement' => 'required',
               //'reference_paiement' => 'required',
               //'fichier_pdf' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'numero.required' => 'Le champ numero ne peut etre vide',
               //'montant_ht.required' => 'Le champ montant_ht ne peut etre vide',
               //'montant_ttc.required' => 'Le champ montant_ttc ne peut etre vide',
               //'taux_tva.required' => 'Le champ taux_tva ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'date_emission.required' => 'Le champ date_emission ne peut etre vide',
               //'date_echeance.required' => 'Le champ date_echeance ne peut etre vide',
               //'date_paiement.required' => 'Le champ date_paiement ne peut etre vide',
               //'mode_paiement.required' => 'Le champ mode_paiement ne peut etre vide',
               //'reference_paiement.required' => 'Le champ reference_paiement ne peut etre vide',
               //'fichier_pdf.required' => 'Le champ fichier_pdf ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $factures = Facture::latest();
        if ($factures
        ->where('numero', $request->numero)
        ->where('montant_ht', $request->montant_ht)
        ->where('montant_ttc', $request->montant_ttc)
        ->where('taux_tva', $request->taux_tva)
        ->where('statut', $request->statut)
        ->where('date_emission', $request->date_emission)
        ->where('date_echeance', $request->date_echeance)
        ->where('date_paiement', $request->date_paiement)
        ->where('mode_paiement', $request->mode_paiement)
        ->where('reference_paiement', $request->reference_paiement)
        ->where('fichier_pdf', $request->fichier_pdf)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $facture = Facture::find($id);
        $facture->update($request->all());
        return $this->sendApiResponse($facture, 'Facture modifié', 201);
    }

    public function destroy($id) 
    {
        $facture = Facture::find($id);
        $facture->delete();

        return $this->sendApiResponse($facture, 'Facture supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $facture = Facture::find($selected);
            if (isset($facture)) {
                if ($facture->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $facture->delete();
                    $nb_supprimes++;
                    $messages[$key] = [
                        'severity' => 'success',
                        'value' => $nb_supprimes.' lignes ont été supprimé'
                    ];
                }
            }
        }
        return $this->sendApiResponse([], $messages);
    }

}
