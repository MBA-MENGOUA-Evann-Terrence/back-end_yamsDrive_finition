<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UtilisateurResource;

class UtilisateurController extends Controller
{

    public function index() 
    {
        $utilisateurs = User::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($utilisateurs)->count();

        return UtilisateurResource::collection($this->AsdecodefilterBy($utilisateurs));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'prenom' => 'required',
               //'email' => 'required',
               //'password' => 'required',
               //'statut' => 'required',
               //'role' => 'required',
               //'signature' => 'required',
               //'telephone1' => 'required',
               //'telephone2' => 'required',
               //'permissions' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'prenom.required' => 'Le champ prenom ne peut etre vide',
               //'email.required' => 'Le champ email ne peut etre vide',
               //'password.required' => 'Le champ password ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'role.required' => 'Le champ role ne peut etre vide',
               //'signature.required' => 'Le champ signature ne peut etre vide',
               //'telephone1.required' => 'Le champ telephone1 ne peut etre vide',
               //'telephone2.required' => 'Le champ telephone2 ne peut etre vide',
               //'permissions.required' => 'Le champ permissions ne peut etre vide',
           ]
         );

        $utilisateurs = User::latest();
        if ($utilisateurs
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('password', $request->password)
        ->where('statut', $request->statut)
        ->where('role', $request->role)
        ->where('signature', $request->signature)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('permissions', $request->permissions)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $utilisateur = User::create($request->all());
        return $this->sendApiResponse($utilisateur, 'Utilisateur ajouté', 201);
    }

    public function show($id)
    {
        return new UtilisateurResource(User::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'prenom' => 'required',
               //'email' => 'required',
               //'password' => 'required',
               //'statut' => 'required',
               //'role' => 'required',
               //'signature' => 'required',
               //'telephone1' => 'required',
               //'telephone2' => 'required',
               //'permissions' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'prenom.required' => 'Le champ prenom ne peut etre vide',
               //'email.required' => 'Le champ email ne peut etre vide',
               //'password.required' => 'Le champ password ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
               //'role.required' => 'Le champ role ne peut etre vide',
               //'signature.required' => 'Le champ signature ne peut etre vide',
               //'telephone1.required' => 'Le champ telephone1 ne peut etre vide',
               //'telephone2.required' => 'Le champ telephone2 ne peut etre vide',
               //'permissions.required' => 'Le champ permissions ne peut etre vide',
           ]
         );

        $utilisateurs = User::latest();
        if ($utilisateurs
        ->where('nom', $request->nom)
        ->where('prenom', $request->prenom)
        ->where('email', $request->email)
        ->where('password', $request->password)
        ->where('statut', $request->statut)
        ->where('role', $request->role)
        ->where('signature', $request->signature)
        ->where('telephone1', $request->telephone1)
        ->where('telephone2', $request->telephone2)
        ->where('permissions', $request->permissions)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $utilisateur = User::find($id);
        $utilisateur->update($request->all());
        return $this->sendApiResponse($utilisateur, 'Utilisateur modifié', 201);
    }

    public function destroy($id) 
    {
        $utilisateur = User::find($id);
        $utilisateur->delete();

        return $this->sendApiResponse($utilisateur, 'Utilisateur supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $utilisateur = User::find($selected);
            if (isset($utilisateur)) {
                if ($utilisateur->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $utilisateur->delete();
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
