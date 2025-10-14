<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Favori;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FavoriResource;

class FavoriController extends Controller
{
    /**
     * Display a listing of the user's favorite documents.
     */
    public function index()
    {
        $user = Auth::user();
        // Charger seulement les favoris sans aucune relation pour éviter la récursion
        $favorites = Favori::where('user_id', $user->id)->get();

        return response()->json($favorites);
    }

    /**
     * Store a newly created favorite in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        $user = Auth::user();

        // Check if the document is already a favorite
        $existingFavorite = Favori::where('user_id', $user->id)
                                  ->where('document_id', $request->document_id)
                                  ->first();

        if ($existingFavorite) {
            return response()->json(['message' => 'Document is already in favorites.'], 409);
        }

        // Désactiver temporairement les événements pour éviter la récursion
        $favorite = Favori::withoutEvents(function () use ($user, $request) {
            return Favori::create([
                'user_id' => $user->id,
                'document_id' => $request->document_id,
            ]);
        });

        // Retourner directement le favori sans relations pour éviter la boucle
        return response()->json($favorite, 201);
    }

    /**
     * Remove the specified favorite from storage.
     */
    public function destroy($document_id)
    {
        $user = Auth::user();

        $favorite = Favori::where('user_id', $user->id)
                            ->where('document_id', $document_id)
                            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Favorite not found.'], 404);
        }

        // Désactiver temporairement les événements pour éviter la récursion
        Favori::withoutEvents(function () use ($favorite) {
            $favorite->delete();
        });

        return response()->json(null, 204);
    }
}