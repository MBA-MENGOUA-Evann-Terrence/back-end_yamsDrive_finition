<?php

namespace App\Http\Controllers;
use App\Models\Concevoir;
use App\Http\Resources\ConcevoirResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConcevoirController extends Controller
{

    public function index() 
    {
        $concevoirs = Concevoir::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($concevoirs)->count();

        return ConcevoirResource::collection($this->AsdecodefilterBy($concevoirs));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'facture_id' => 'required',
               //'service_id' => 'required',
               //'utilisateur_id' => 'required',
               //'nouvelles_valeurs' => 'required',
               //'anciennes_valeurs' => 'required',
               //'adresse_ip' => 'required',
           ],
           $messages = [
               //'facture_id.required' => 'Le champ facture_id ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'nouvelles_valeurs.required' => 'Le champ nouvelles_valeurs ne peut etre vide',
               //'anciennes_valeurs.required' => 'Le champ anciennes_valeurs ne peut etre vide',
               //'adresse_ip.required' => 'Le champ adresse_ip ne peut etre vide',
           ]
         );

        $concevoirs = Concevoir::latest();
        if ($concevoirs
        ->where('facture_id', $request->facture_id)
        ->where('service_id', $request->service_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('nouvelles_valeurs', $request->nouvelles_valeurs)
        ->where('anciennes_valeurs', $request->anciennes_valeurs)
        ->where('adresse_ip', $request->adresse_ip)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $concevoir = Concevoir::create($request->all());
        return $this->sendApiResponse($concevoir, 'Concevoir ajouté', 201);
    }

    public function show($id)
    {
        return new ConcevoirResource(Concevoir::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'facture_id' => 'required',
               //'service_id' => 'required',
               //'utilisateur_id' => 'required',
               //'nouvelles_valeurs' => 'required',
               //'anciennes_valeurs' => 'required',
               //'adresse_ip' => 'required',
           ],
           $messages = [
               //'facture_id.required' => 'Le champ facture_id ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'nouvelles_valeurs.required' => 'Le champ nouvelles_valeurs ne peut etre vide',
               //'anciennes_valeurs.required' => 'Le champ anciennes_valeurs ne peut etre vide',
               //'adresse_ip.required' => 'Le champ adresse_ip ne peut etre vide',
           ]
         );

        $concevoirs = Concevoir::latest();
        if ($concevoirs
        ->where('facture_id', $request->facture_id)
        ->where('service_id', $request->service_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('nouvelles_valeurs', $request->nouvelles_valeurs)
        ->where('anciennes_valeurs', $request->anciennes_valeurs)
        ->where('adresse_ip', $request->adresse_ip)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $concevoir = Concevoir::find($id);
        $concevoir->update($request->all());
        return $this->sendApiResponse($concevoir, 'Concevoir modifié', 201);
    }

    public function destroy($id) 
    {
        $concevoir = Concevoir::find($id);
        $concevoir->delete();

        return $this->sendApiResponse($concevoir, 'Concevoir supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $concevoir = Concevoir::find($selected);
            if (isset($concevoir)) {
                if ($concevoir->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $concevoir->delete();
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
