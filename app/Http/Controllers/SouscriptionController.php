<?php

namespace App\Http\Controllers;
use App\Models\Souscription;
use App\Http\Resources\SouscriptionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SouscriptionController extends Controller
{

    public function index() 
    {
        $souscriptions = Souscription::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($souscriptions)->count();

        return SouscriptionResource::collection($this->AsdecodefilterBy($souscriptions));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'service' => 'required',
               //'prix' => 'required',
               //'duree' => 'required',
               //'date_souscription' => 'required',
               //'statut' => 'required',
               //'clientusername' => 'required',
           ],
           $messages = [
               //'service.required' => 'Le champ service ne peut etre vide',
               //'prix.required' => 'Le champ prix ne peut etre vide',
               //'duree.required' => 'Le champ duree ne peut etre vide',
               //'date_souscription.required' => 'Le champ date_souscription ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'clientusername.required' => 'Le champ clientusername ne peut etre vide',
           ]
         );

        $souscriptions = Souscription::latest();
        if ($souscriptions
        ->where('service', $request->service)
        ->where('prix', $request->prix)
        ->where('duree', $request->duree)
        ->where('date_souscription', $request->date_souscription)
        ->where('statut', $request->statut)
        ->where('clientusername', $request->clientusername)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $souscription = Souscription::create($request->all());
        return $this->sendApiResponse($souscription, 'Souscription ajouté', 201);
    }

    public function show($id)
    {
        return new SouscriptionResource(Souscription::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'service' => 'required',
               //'prix' => 'required',
               //'duree' => 'required',
               //'date_souscription' => 'required',
               //'statut' => 'required',
               //'clientusername' => 'required',
           ],
           $messages = [
               //'service.required' => 'Le champ service ne peut etre vide',
               //'prix.required' => 'Le champ prix ne peut etre vide',
               //'duree.required' => 'Le champ duree ne peut etre vide',
               //'date_souscription.required' => 'Le champ date_souscription ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'clientusername.required' => 'Le champ clientusername ne peut etre vide',
           ]
         );

        $souscriptions = Souscription::latest();
        if ($souscriptions
        ->where('service', $request->service)
        ->where('prix', $request->prix)
        ->where('duree', $request->duree)
        ->where('date_souscription', $request->date_souscription)
        ->where('statut', $request->statut)
        ->where('clientusername', $request->clientusername)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $souscription = Souscription::find($id);
        $souscription->update($request->all());
        return $this->sendApiResponse($souscription, 'Souscription modifié', 201);
    }

    public function destroy($id) 
    {
        $souscription = Souscription::find($id);
        $souscription->delete();

        return $this->sendApiResponse($souscription, 'Souscription supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $souscription = Souscription::find($selected);
            if (isset($souscription)) {
                if ($souscription->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $souscription->delete();
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
