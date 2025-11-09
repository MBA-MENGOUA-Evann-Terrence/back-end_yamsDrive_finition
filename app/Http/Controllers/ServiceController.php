<?php

namespace App\Http\Controllers;
use App\Models\Service;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Récupère le service de l'utilisateur authentifié.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserService(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
            }

            // Charger la relation 'service' pour l'utilisateur
            $user->load('service');

            if (!$user->service) {
                return response()->json(['message' => 'Aucun service associé à cet utilisateur.'], 404);
            }

            // Retourner le service dans un tableau pour que le front-end puisse l'itérer
            return response()->json(['data' => [$user->service]]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du service de l\'utilisateur', [
                'user_id' => $request->user() ? $request->user()->id : 'N/A',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur serveur lors de la récupération du service.'], 500);
        }
    }


    public function index(Request $request) 
    {
        $services = Service::latest();

        if (isset($_GET['req_count'])) return $this->filterByColumn($services)->count();

        return ServiceResource::collection($this->AsdecodefilterBy($services));
    }

    public function store(Request $request) 
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
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

    public function show(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        return new ServiceResource(Service::find($id));
    }

    public function update(Request $request, $id) 
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
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

    public function destroy(Request $request, $id) 
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
        $service = Service::find($id);
        $service->delete();

        return $this->sendApiResponse($service, 'Service supprimé');
    }

    public function destroy_group(Request $request)
    {
        if (!$request->user() || $request->user()->role != 1) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }
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
