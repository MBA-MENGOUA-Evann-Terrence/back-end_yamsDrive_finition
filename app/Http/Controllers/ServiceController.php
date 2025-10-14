<?php

namespace App\Http\Controllers;
use App\Models\Service;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{

    public function index() 
    {
        $services = Service::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($services)->count();

        return ServiceResource::collection($this->AsdecodefilterBy($services));
    }

    public function store(Request $request) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'description' => 'required',
               //'prix' => 'required',
               //'duree' => 'required',
               //'date_souscription' => 'required',
               //'statut' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
               //'prix.required' => 'Le champ prix ne peut etre vide',
               //'duree.required' => 'Le champ duree ne peut etre vide',
               //'date_souscription.required' => 'Le champ date_souscription ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
           ]
         );

        $services = Service::latest();
        if ($services
        ->where('nom', $request->nom)
        ->where('description', $request->description)
        ->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $service = Service::create($request->all());
        return $this->sendApiResponse($service, 'Service ajouté', 201);
    }

    public function show($id)
    {
        return new ServiceResource(Service::find($id));
    }

    public function update(Request $request, $id) 
    {
        $validator = Validator::make(
           $request->all(),
           [
               //'nom' => 'required',
               //'description' => 'required',
               //'prix' => 'required',
               //'duree' => 'required',
               //'date_souscription' => 'required',
               //'statut' => 'required',
           ],
           $messages = [
               //'nom.required' => 'Le champ nom ne peut etre vide',
               //'description.required' => 'Le champ description ne peut etre vide',
               //'prix.required' => 'Le champ prix ne peut etre vide',
               //'duree.required' => 'Le champ duree ne peut etre vide',
               //'date_souscription.required' => 'Le champ date_souscription ne peut etre vide',
               //'statut.required' => 'Le champ statut ne peut etre vide',
           ]
         );

        $services = Service::latest();
        if ($services
        ->where('nom', $request->nom)
        ->where('description', $request->description)
        ->where('id','!=', $id)->first()) {
           $messages = [ 'Cet enregistrement existe déjà' ];
           return $this->sendApiErrors($messages);
        }

        if ($validator->fails()) return $this->sendApiErrors($validator->errors()->all());

        $service = Service::find($id);
        $service->update($request->all());
        return $this->sendApiResponse($service, 'Service modifié', 201);
    }

    public function destroy($id) 
    {
        $service = Service::find($id);
        $service->delete();

        return $this->sendApiResponse($service, 'Service supprimé');
    }

    public function destroy_group(Request $request)
    {
        $key = 0;
        $nb_supprimes = 0;
        $messages= [];
        foreach ($request->selected_lines as $selected) {
            $service = Service::find($selected);
            if (isset($service)) {
                if ($service->est_valide == 1) {
                    $messages[$key] = [
                        'severity' => 'warn',
                        'value' => 'Impossible de supprimer ID0'.$selected
                    ];
                    $key++;
                }
                else {
                    $service->delete();
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
