<?php

namespace App\Http\Controllers;
use App\Models\Client;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{

    public function index() 
    {
        $clients = Client::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($clients)->count();

        return ClientResource::collection($this->AsdecodefilterBy($clients));
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
               //'nom_entreprise' => 'required',
               //'date_inscription' => 'required',
               //'service_id' => 'required',
               //'prospect_id' => 'required',
               //'description' => 'required',
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
               //'nom_entreprise.required' => 'Le champ nom_entreprise ne peut etre vide',
               //'date_inscription.required' => 'Le champ date_inscription ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'prospect_id.required' => 'Le champ prospect_id ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
           ]
         );

        $clients = Client::latest();
        if ($clients
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('adresse', $request->adresse)
        ->where('type', $request->type)
        ->where('ville', $request->ville)
        ->where('nom_entreprise', $request->nom_entreprise)
        ->where('date_inscription', $request->date_inscription)
        ->where('service_id', $request->service_id)
        ->where('prospect_id', $request->prospect_id)
        ->where('description', $request->description)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $client = Client::create($request->all());
        return $this->sendApiResponse($client, 'Client ajouté', 201);
    }

    public function show($id)
    {
        return new ClientResource(Client::find($id));
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
               //'nom_entreprise' => 'required',
               //'date_inscription' => 'required',
               //'service_id' => 'required',
               //'prospect_id' => 'required',
               //'description' => 'required',
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
               //'nom_entreprise.required' => 'Le champ nom_entreprise ne peut etre vide',
               //'date_inscription.required' => 'Le champ date_inscription ne peut etre vide',
               //'service_id.required' => 'Le champ service_id ne peut etre vide',
               //'prospect_id.required' => 'Le champ prospect_id ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
           ]
         );

        $clients = Client::latest();
        if ($clients
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('adresse', $request->adresse)
        ->where('type', $request->type)
        ->where('ville', $request->ville)
        ->where('nom_entreprise', $request->nom_entreprise)
        ->where('date_inscription', $request->date_inscription)
        ->where('service_id', $request->service_id)
        ->where('prospect_id', $request->prospect_id)
        ->where('description', $request->description)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $client = Client::find($id);
        $client->update($request->all());
        return $this->sendApiResponse($client, 'Client modifié', 201);
    }

    public function destroy($id) 
    {
        $client = Client::find($id);
        $client->delete();

        return $this->sendApiResponse($client, 'Client supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $client = Client::find($selected);
            if (isset($client)) {
                if ($client->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $client->delete();
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
