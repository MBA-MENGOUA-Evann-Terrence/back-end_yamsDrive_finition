<?php

namespace App\Http\Controllers;
use App\Models\TicketResponse;
use App\Http\Resources\TicketResponseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketResponseController extends Controller
{

    public function index() 
    {
        $ticket_responses = TicketResponse::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($ticket_responses)->count();

        return TicketResponseResource::collection($this->AsdecodefilterBy($ticket_responses));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'ticket_id' => 'required',
               //'utilisateur_id' => 'required',
               //'message' => 'required',
           ],
           $messages = [
               //'ticket_id.required' => 'Le champ ticket_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
           ]
         );

        $ticket_responses = TicketResponse::latest();
        if ($ticket_responses
        ->where('ticket_id', $request->ticket_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('message', $request->message)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket_response = TicketResponse::create($request->all());
        return $this->sendApiResponse($ticket_response, 'Ticket_Response ajouté', 201);
    }

    public function show($id)
    {
        return new TicketResponseResource(TicketResponse::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'ticket_id' => 'required',
               //'utilisateur_id' => 'required',
               //'message' => 'required',
           ],
           $messages = [
               //'ticket_id.required' => 'Le champ ticket_id ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'message.required' => 'Le champ message ne peut etre vide',
           ]
         );

        $ticket_responses = TicketResponse::latest();
        if ($ticket_responses
        ->where('ticket_id', $request->ticket_id)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('message', $request->message)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket_response = TicketResponse::find($id);
        $ticket_response->update($request->all());
        return $this->sendApiResponse($ticket_response, 'Ticket_Response modifié', 201);
    }

    public function destroy($id) 
    {
        $ticket_response = TicketResponse::find($id);
        $ticket_response->delete();

        return $this->sendApiResponse($ticket_response, 'Ticket_Response supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $ticket_response = TicketResponse::find($selected);
            if (isset($ticket_response)) {
                if ($ticket_response->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $ticket_response->delete();
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
