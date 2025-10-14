<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\User;
use App\Models\LogAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $domains = Domain::with('user')->get();
            return response()->json($domains);
        } catch (\Exception $e) {
            Log::error('Error fetching domains: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('DomainController@store: Début de la requête.');
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:domains|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Création directe sans événements - plus simple et sans récursion
            $domain = new Domain();
            $domain->name = $request->name;
            $domain->user_id = Auth::id();
            $domain->status = 'pending';
            $domain->expires_at = now()->addYear();
            
            // Sauvegarder sans déclencher d'événements
            $domain->saveQuietly();

            Log::info('Domaine créé avec succès: ' . $domain->id);

            return response()->json([
                'message' => 'Domaine créé avec succès.',
                'domain' => [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'status' => $domain->status,
                    'user_id' => $domain->user_id,
                    'expires_at' => $domain->expires_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur création domaine: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $domain = Domain::with('user')->findOrFail($id);
            return response()->json($domain);
        } catch (\Exception $e) {
            Log::error('Error fetching domain: ' . $e->getMessage());
            return response()->json(['error' => 'Domain not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $domain = Domain::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|unique:domains,name,' . $id . '|max:255',
                'status' => 'sometimes|required|in:pending,active,suspended',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Mise à jour sans événements pour éviter la récursion
            Domain::withoutEvents(function () use ($domain, $request) {
                $domain->update($request->only(['name', 'status']));
            });

            return response()->json([
                'message' => 'Domaine mis à jour avec succès.',
                'domain' => $domain->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating domain: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $domain = Domain::findOrFail($id);
            
            // Suppression sans événements pour éviter la récursion
            Domain::withoutEvents(function () use ($domain) {
                $domain->delete();
            });

            return response()->json(['message' => 'Domaine supprimé avec succès.']);
        } catch (\Exception $e) {
            Log::error('Error deleting domain: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
