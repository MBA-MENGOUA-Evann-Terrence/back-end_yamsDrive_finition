<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index()
    {
        return Categorie::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $categorie = Categorie::create($request->all());

        return response()->json($categorie, 201);
    }

    public function show(Categorie $categorie)
    {
        return $categorie;
    }

    public function update(Request $request, Categorie $categorie)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $categorie->update($request->all());

        return response()->json($categorie, 200);
    }

    public function destroy(Categorie $categorie)
    {
        $categorie->delete();

        return response()->json(null, 204);
    }

    public function destroy_group(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        Categorie::destroy($request->ids);

        return response()->json(null, 204);
    }
}
