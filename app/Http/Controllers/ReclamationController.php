<?php

namespace App\Http\Controllers;
use App\Models\Reclamation;
use App\Http\Resources\ReclamationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReclamationController extends Controller
{

    public function index() 
    {
        $reclamations = Reclamation::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($reclamations)->count();

        return ReclamationResource::collection($this->AsdecodefilterBy($reclamations));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'sujrt' => 'required',
               //'description' => 'required',
               //'statut' => 'required',
               //'date_resolution' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'sujrt.required' => 'Le champ sujrt ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'date_resolution.required' => 'Le champ date_resolution ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $reclamations = Reclamation::latest();
        if ($reclamations
        ->where('sujrt', $request->sujrt)
        ->where('description', $request->description)
        ->where('statut', $request->statut)
        ->where('date_resolution', $request->date_resolution)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $reclamation = Reclamation::create($request->all());
        return $this->sendApiResponse($reclamation, 'Reclamation ajouté', 201);
    }

    public function show($id)
    {
        return new ReclamationResource(Reclamation::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'sujrt' => 'required',
               //'description' => 'required',
               //'statut' => 'required',
               //'date_resolution' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'sujrt.required' => 'Le champ sujrt ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'date_resolution.required' => 'Le champ date_resolution ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $reclamations = Reclamation::latest();
        if ($reclamations
        ->where('sujrt', $request->sujrt)
        ->where('description', $request->description)
        ->where('statut', $request->statut)
        ->where('date_resolution', $request->date_resolution)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $reclamation = Reclamation::find($id);
        $reclamation->update($request->all());
        return $this->sendApiResponse($reclamation, 'Reclamation modifié', 201);
    }

    public function destroy($id) 
    {
        $reclamation = Reclamation::find($id);
        $reclamation->delete();

        return $this->sendApiResponse($reclamation, 'Reclamation supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $reclamation = Reclamation::find($selected);
            if (isset($reclamation)) {
                if ($reclamation->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $reclamation->delete();
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
