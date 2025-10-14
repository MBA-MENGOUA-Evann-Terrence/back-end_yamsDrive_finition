<?php

namespace App\Http\Controllers;
use App\Models\PieceJointe;
use App\Http\Resources\PieceJointeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PieceJointeController extends Controller
{

    public function index() 
    {
        $piece_jointes = PieceJointe::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($piece_jointes)->count();

        return PieceJointeResource::collection($this->AsdecodefilterBy($piece_jointes));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'chemin' => 'required',
               //'nom_fichier' => 'required',
               //'type' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'chemin.required' => 'Le champ chemin ne peut etre vide',
               //'nom_fichier.required' => 'Le champ nom_fichier ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $piece_jointes = PieceJointe::latest();
        if ($piece_jointes
        ->where('chemin', $request->chemin)
        ->where('nom_fichier', $request->nom_fichier)
        ->where('type', $request->type)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $piece_jointe = PieceJointe::create($request->all());
        return $this->sendApiResponse($piece_jointe, 'Piece_Jointe ajouté', 201);
    }

    public function show($id)
    {
        return new PieceJointeResource(PieceJointe::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'chemin' => 'required',
               //'nom_fichier' => 'required',
               //'type' => 'required',
               //'cahiercharge_id' => 'required',
           ],
           $messages = [
               //'chemin.required' => 'Le champ chemin ne peut etre vide',
               //'nom_fichier.required' => 'Le champ nom_fichier ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'cahiercharge_id.required' => 'Le champ cahiercharge_id ne peut etre vide',
           ]
         );

        $piece_jointes = PieceJointe::latest();
        if ($piece_jointes
        ->where('chemin', $request->chemin)
        ->where('nom_fichier', $request->nom_fichier)
        ->where('type', $request->type)
        ->where('cahiercharge_id', $request->cahiercharge_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $piece_jointe = PieceJointe::find($id);
        $piece_jointe->update($request->all());
        return $this->sendApiResponse($piece_jointe, 'Piece_Jointe modifié', 201);
    }

    public function destroy($id) 
    {
        $piece_jointe = PieceJointe::find($id);
        $piece_jointe->delete();

        return $this->sendApiResponse($piece_jointe, 'Piece_Jointe supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $piece_jointe = PieceJointe::find($selected);
            if (isset($piece_jointe)) {
                if ($piece_jointe->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $piece_jointe->delete();
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
