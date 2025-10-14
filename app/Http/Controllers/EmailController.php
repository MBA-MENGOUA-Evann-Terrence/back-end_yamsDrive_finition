<?php

namespace App\Http\Controllers;
use App\Models\Email;
use App\Http\Resources\EmailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{

    public function index() 
    {
        $emails = Email::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($emails)->count();

        return EmailResource::collection($this->AsdecodefilterBy($emails));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'objet' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
               //'type' => 'required',
               //'date_envoi' => 'required',
               //'date_lecture' => 'required',
               //'prospect_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'objet.required' => 'Le champ objet ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'date_envoi.required' => 'Le champ date_envoi ne peut etre vide',
               //'date_lecture.required' => 'Le champ date_lecture ne peut etre vide',
               //'prospect_id.required' => 'Le champ prospect_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $emails = Email::latest();
        if ($emails
        ->where('objet', $request->objet)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->where('type', $request->type)
        ->where('date_envoi', $request->date_envoi)
        ->where('date_lecture', $request->date_lecture)
        ->where('prospect_id', $request->prospect_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $email = Email::create($request->all());
        return $this->sendApiResponse($email, 'Email ajouté', 201);
    }

    public function show($id)
    {
        return new EmailResource(Email::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'objet' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
               //'type' => 'required',
               //'date_envoi' => 'required',
               //'date_lecture' => 'required',
               //'prospect_id' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'objet.required' => 'Le champ objet ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'date_envoi.required' => 'Le champ date_envoi ne peut etre vide',
               //'date_lecture.required' => 'Le champ date_lecture ne peut etre vide',
               //'prospect_id.required' => 'Le champ prospect_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $emails = Email::latest();
        if ($emails
        ->where('objet', $request->objet)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->where('type', $request->type)
        ->where('date_envoi', $request->date_envoi)
        ->where('date_lecture', $request->date_lecture)
        ->where('prospect_id', $request->prospect_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $email = Email::find($id);
        $email->update($request->all());
        return $this->sendApiResponse($email, 'Email modifié', 201);
    }

    public function destroy($id) 
    {
        $email = Email::find($id);
        $email->delete();

        return $this->sendApiResponse($email, 'Email supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $email = Email::find($selected);
            if (isset($email)) {
                if ($email->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $email->delete();
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
