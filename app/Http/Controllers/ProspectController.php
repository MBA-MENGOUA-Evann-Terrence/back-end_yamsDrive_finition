<?php

namespace App\Http\Controllers;
use App\Models\Prospect;
use App\Http\Resources\ProspectResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProspectController extends Controller
{

    public function index() 
    {
        $prospects = Prospect::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($prospects)->count();

        return ProspectResource::collection($this->AsdecodefilterBy($prospects));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'prenom' => 'required',
               //'email' => 'required',
               //'telephone1' => 'required',
               //'telephone2' => 'required',
               //'adresse' => 'required',
               //'type' => 'required',
               //'ville' => 'required',
               //'statut' => 'required',
               //'source' => 'required',
               //'note' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'prenom.required' => 'Le champ prenom ne peut etre vide',
               //'email.required' => 'Le champ email ne peut etre vide',
               //'telephone1.required' => 'Le champ telephone1 ne peut etre vide',
               //'telephone2.required' => 'Le champ telephone2 ne peut etre vide',
               //'adresse.required' => 'Le champ adresse ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'ville.required' => 'Le champ ville ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'source.required' => 'Le champ source ne peut etre vide',
               //'note.required' => 'Le champ note ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $prospects = Prospect::latest();
        if ($prospects
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('adresse', $request->adresse)
        ->where('type', $request->type)
        ->where('ville', $request->ville)
        ->where('statut', $request->statut)
        ->where('source', $request->source)
        ->where('note', $request->note)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $prospect = Prospect::create($request->all());
        return $this->sendApiResponse($prospect, 'Prospect ajouté', 201);
    }

    public function show($id)
    {
        return new ProspectResource(Prospect::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'prenom' => 'required',
               //'email' => 'required',
               //'telephone1' => 'required',
               //'telephone2' => 'required',
               //'adresse' => 'required',
               //'type' => 'required',
               //'ville' => 'required',
               //'statut' => 'required',
               //'source' => 'required',
               //'note' => 'required',
               //'utilisateur_id' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'prenom.required' => 'Le champ prenom ne peut etre vide',
               //'email.required' => 'Le champ email ne peut etre vide',
               //'telephone1.required' => 'Le champ telephone1 ne peut etre vide',
               //'telephone2.required' => 'Le champ telephone2 ne peut etre vide',
               //'adresse.required' => 'Le champ adresse ne peut etre vide',
               //'type.required' => 'Le champ type ne peut etre vide',
               //'ville.required' => 'Le champ ville ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'source.required' => 'Le champ source ne peut etre vide',
               //'note.required' => 'Le champ note ne peut etre vide',
               //'utilisateur_id.required' => 'Le champ utilisateur_id ne peut etre vide',
           ]
         );

        $prospects = Prospect::latest();
        if ($prospects
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('adresse', $request->adresse)
        ->where('type', $request->type)
        ->where('ville', $request->ville)
        ->where('statut', $request->statut)
        ->where('source', $request->source)
        ->where('note', $request->note)
        ->where('utilisateur_id', $request->utilisateur_id)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $prospect = Prospect::find($id);
        $prospect->update($request->all());
        return $this->sendApiResponse($prospect, 'Prospect modifié', 201);
    }

    public function destroy($id) 
    {
        $prospect = Prospect::find($id);
        $prospect->delete();

        return $this->sendApiResponse($prospect, 'Prospect supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $prospect = Prospect::find($selected);
            if (isset($prospect)) {
                if ($prospect->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $prospect->delete();
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
