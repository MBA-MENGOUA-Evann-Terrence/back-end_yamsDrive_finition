<?php

namespace App\Http\Controllers;
use App\Models\TicketAttachment;
use App\Http\Resources\TicketAttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketAttachmentController extends Controller
{

    public function index() 
    {
        $ticket_attachments = TicketAttachment::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($ticket_attachments)->count();

        return TicketAttachmentResource::collection($this->AsdecodefilterBy($ticket_attachments));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'fichier' => 'required',
               //'ticket_id' => 'required',
               //'type' => 'required',
               //'taille' => 'required',
           ],
           $messages = [
               //'fichier.required' => 'Le champ fichier ne peut etre vide',
               //'ticket_id.required' => 'Le champ ticket_id ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'taille.required' => 'Le champ taille ne peut etre vide',
           ]
         );

        $ticket_attachments = TicketAttachment::latest();
        if ($ticket_attachments
        ->where('fichier', $request->fichier)
        ->where('ticket_id', $request->ticket_id)
        ->where('type', $request->type)
        ->where('taille', $request->taille)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket_attachment = TicketAttachment::create($request->all());
        return $this->sendApiResponse($ticket_attachment, 'Ticket_Attachment ajouté', 201);
    }

    public function show($id)
    {
        return new TicketAttachmentResource(TicketAttachment::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'fichier' => 'required',
               //'ticket_id' => 'required',
               //'type' => 'required',
               //'taille' => 'required',
           ],
           $messages = [
               //'fichier.required' => 'Le champ fichier ne peut etre vide',
               //'ticket_id.required' => 'Le champ ticket_id ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'taille.required' => 'Le champ taille ne peut etre vide',
           ]
         );

        $ticket_attachments = TicketAttachment::latest();
        if ($ticket_attachments
        ->where('fichier', $request->fichier)
        ->where('ticket_id', $request->ticket_id)
        ->where('type', $request->type)
        ->where('taille', $request->taille)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $ticket_attachment = TicketAttachment::find($id);
        $ticket_attachment->update($request->all());
        return $this->sendApiResponse($ticket_attachment, 'Ticket_Attachment modifié', 201);
    }

    public function destroy($id) 
    {
        $ticket_attachment = TicketAttachment::find($id);
        $ticket_attachment->delete();

        return $this->sendApiResponse($ticket_attachment, 'Ticket_Attachment supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $ticket_attachment = TicketAttachment::find($selected);
            if (isset($ticket_attachment)) {
                if ($ticket_attachment->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $ticket_attachment->delete();
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
