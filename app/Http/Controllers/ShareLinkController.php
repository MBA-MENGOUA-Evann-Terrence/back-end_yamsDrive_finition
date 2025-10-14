<?php

namespace App\Http\Controllers;
use App\Models\ShareLink;
use App\Http\Resources\ShareLinkResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShareLinkController extends Controller
{

    public function index() 
    {
        $share_links = ShareLink::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($share_links)->count();

        return ShareLinkResource::collection($this->AsdecodefilterBy($share_links));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'token' => 'required',
               //'expires_at' => 'required',
               //'utilisateur_id' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'token.required' => 'Le champ token ne peut etre vide',
               //'expires_at.required' => 'Le champ expires_at ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $share_links = ShareLink::latest();
        if ($share_links
        ->where('token', $request->token)
        ->where('expires_at', $request->expires_at)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $share_link = ShareLink::create($request->all());
        return $this->sendApiResponse($share_link, 'Share_Link ajouté', 201);
    }

    public function show($id)
    {
        return new ShareLinkResource(ShareLink::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'token' => 'required',
               //'expires_at' => 'required',
               //'utilisateur_id' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'token.required' => 'Le champ token ne peut etre vide',
               //'expires_at.required' => 'Le champ expires_at ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $share_links = ShareLink::latest();
        if ($share_links
        ->where('token', $request->token)
        ->where('expires_at', $request->expires_at)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $share_link = ShareLink::find($id);
        $share_link->update($request->all());
        return $this->sendApiResponse($share_link, 'Share_Link modifié', 201);
    }

    public function destroy($id) 
    {
        $share_link = ShareLink::find($id);
        $share_link->delete();

        return $this->sendApiResponse($share_link, 'Share_Link supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $share_link = ShareLink::find($selected);
            if (isset($share_link)) {
                if ($share_link->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $share_link->delete();
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
