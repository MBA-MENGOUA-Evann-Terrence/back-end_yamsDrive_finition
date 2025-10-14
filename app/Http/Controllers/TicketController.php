<?php

namespace App\Http\Controllers;
use App\Models\Ticket;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{

    public function index() 
    {
        $tickets = Ticket::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($tickets)->count();

        return TicketResource::collection($this->AsdecodefilterBy($tickets));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'sujet' => 'required',
               //'utilisateur_id' => 'required',
               //'service_id' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
           ],
           $messages = [
               //'sujet.required' => 'Le champ sujet ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
           ]
         );

        $tickets = Ticket::latest();
        if ($tickets
        ->where('sujet', $request->sujet)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('service_id', $request->service_id)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket = Ticket::create($request->all());
        return $this->sendApiResponse($ticket, 'Ticket ajouté', 201);
    }

    public function show($id)
    {
        return new TicketResource(Ticket::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'sujet' => 'required',
               //'utilisateur_id' => 'required',
               //'service_id' => 'required',
               //'message' => 'required',
               //'statut' => 'required',
           ],
           $messages = [
               //'sujet.required' => 'Le champ sujet ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
           ]
         );

        $tickets = Ticket::latest();
        if ($tickets
        ->where('sujet', $request->sujet)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('service_id', $request->service_id)
        ->where('message', $request->message)
        ->where('statut', $request->statut)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket = Ticket::find($id);
        $ticket->update($request->all());
        return $this->sendApiResponse($ticket, 'Ticket modifié', 201);
    }

    public function destroy($id) 
    {
        $ticket = Ticket::find($id);
        $ticket->delete();

        return $this->sendApiResponse($ticket, 'Ticket supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $ticket = Ticket::find($selected);
            if (isset($ticket)) {
                if ($ticket->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $ticket->delete();
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
