<?php

namespace App\Http\Controllers;
use App\Models\HistoriqueCdc;
use App\Http\Resources\HistoriqueCdcResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoriqueCdcController extends Controller
{

    public function index() 
    {
        $historique_cahiers = HistoriqueCahier::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($historique_cahiers)->count();

        return HistoriqueCahierResource::collection($this->AsdecodefilterBy($historique_cahiers));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'action' => 'required',
               //'detail' => 'required',
               //'utilisateur_id' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'action.required' => 'Le champ action ne peut etre vide',
               //'detail.required' => 'Le champ detail ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $historique_cahiers = HistoriqueCahier::latest();
        if ($historique_cahiers
        ->where('action', $request->action)
        ->where('detail', $request->detail)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $historique_cahier = HistoriqueCahier::create($request->all());
        return $this->sendApiResponse($historique_cahier, 'Historique_Cahier ajouté', 201);
    }

    public function show($id)
    {
        return new HistoriqueCahierResource(HistoriqueCahier::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'action' => 'required',
               //'detail' => 'required',
               //'utilisateur_id' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'action.required' => 'Le champ action ne peut etre vide',
               //'detail.required' => 'Le champ detail ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $historique_cahiers = HistoriqueCahier::latest();
        if ($historique_cahiers
        ->where('action', $request->action)
        ->where('detail', $request->detail)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $historique_cahier = HistoriqueCahier::find($id);
        $historique_cahier->update($request->all());
        return $this->sendApiResponse($historique_cahier, 'Historique_Cahier modifié', 201);
    }

    public function destroy($id) 
    {
        $historique_cahier = HistoriqueCahier::find($id);
        $historique_cahier->delete();

        return $this->sendApiResponse($historique_cahier, 'Historique_Cahier supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $historique_cahier = HistoriqueCahier::find($selected);
            if (isset($historique_cahier)) {
                if ($historique_cahier->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $historique_cahier->delete();
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
