<?php

namespace App\Http\Controllers;
use App\Models\LogAction;
use App\Http\Resources\LogActionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogActionController extends Controller
{

    public function index() 
    {
        $log_actions = LogAction::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($log_actions)->count();

        return LogActionResource::collection($this->AsdecodefilterBy($log_actions));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'action' => 'required',
               //'table_affectee' => 'required',
               //'utilisateur_id' => 'required',
               //'nouvelles_valeurs' => 'required',
               //'anciennes_valeurs' => 'required',
               //'adresse_ip' => 'required',
           ],
           $messages = [
               //'action.required' => 'Le champ action ne peut etre vide',
               //'table_affectee.required' => 'Le champ table_affectee ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'nouvelles_valeurs.required' => 'Le champ nouvelles_valeurs ne peut etre vide',
               //'anciennes_valeurs.required' => 'Le champ anciennes_valeurs ne peut etre vide',
               //'adresse_ip.required' => 'Le champ adresse_ip ne peut etre vide',
           ]
         );

        $log_actions = LogAction::latest();
        if ($log_actions
        ->where('action', $request->action)
        ->where('table_affectee', $request->table_affectee)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('nouvelles_valeurs', $request->nouvelles_valeurs)
        ->where('anciennes_valeurs', $request->anciennes_valeurs)
        ->where('adresse_ip', $request->adresse_ip)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $log_action = LogAction::create($request->all());
        return $this->sendApiResponse($log_action, 'Log_Action ajouté', 201);
    }

    public function show($id)
    {
        return new LogActionResource(LogAction::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'action' => 'required',
               //'table_affectee' => 'required',
               //'utilisateur_id' => 'required',
               //'nouvelles_valeurs' => 'required',
               //'anciennes_valeurs' => 'required',
               //'adresse_ip' => 'required',
           ],
           $messages = [
               //'action.required' => 'Le champ action ne peut etre vide',
               //'table_affectee.required' => 'Le champ table_affectee ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'nouvelles_valeurs.required' => 'Le champ nouvelles_valeurs ne peut etre vide',
               //'anciennes_valeurs.required' => 'Le champ anciennes_valeurs ne peut etre vide',
               //'adresse_ip.required' => 'Le champ adresse_ip ne peut etre vide',
           ]
         );

        $log_actions = LogAction::latest();
        if ($log_actions
        ->where('action', $request->action)
        ->where('table_affectee', $request->table_affectee)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('nouvelles_valeurs', $request->nouvelles_valeurs)
        ->where('anciennes_valeurs', $request->anciennes_valeurs)
        ->where('adresse_ip', $request->adresse_ip)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $log_action = LogAction::find($id);
        $log_action->update($request->all());
        return $this->sendApiResponse($log_action, 'Log_Action modifié', 201);
    }

    public function destroy($id) 
    {
        $log_action = LogAction::find($id);
        $log_action->delete();

        return $this->sendApiResponse($log_action, 'Log_Action supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $log_action = LogAction::find($selected);
            if (isset($log_action)) {
                if ($log_action->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $log_action->delete();
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
