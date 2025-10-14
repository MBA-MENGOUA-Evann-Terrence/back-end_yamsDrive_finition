<?php

namespace App\Http\Controllers;
use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{

    public function index() 
    {
        $notifications = Notification::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($notifications)->count();

        return NotificationResource::collection($this->AsdecodefilterBy($notifications));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'titre' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'titre.required' => 'Le champ titre ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $notifications = Notification::latest();
        if ($notifications
        ->where('titre', $request->titre)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $notification = Notification::create($request->all());
        return $this->sendApiResponse($notification, 'Notification ajouté', 201);
    }

    public function show($id)
    {
        return new NotificationResource(Notification::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'titre' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
               //'client_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'titre.required' => 'Le champ titre ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'client_id.required' => 'Le champ client_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $notifications = Notification::latest();
        if ($notifications
        ->where('titre', $request->titre)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->where('client_id', $request->client_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $notification = Notification::find($id);
        $notification->update($request->all());
        return $this->sendApiResponse($notification, 'Notification modifié', 201);
    }

    public function destroy($id) 
    {
        $notification = Notification::find($id);
        $notification->delete();

        return $this->sendApiResponse($notification, 'Notification supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $notification = Notification::find($selected);
            if (isset($notification)) {
                if ($notification->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $notification->delete();
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
