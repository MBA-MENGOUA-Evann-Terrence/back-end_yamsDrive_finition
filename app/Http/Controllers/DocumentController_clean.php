<?php

namespace App\Http\Controllers;

use App\Events\UserActionLogged;
use App\Models\Document;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $user = Auth::user();
        $query = Document::where('user_id', $user->id);

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $documents = $query->with('service')->latest()->paginate(10);

        return response()->json($documents);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:20480', // 20MB Max
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $service = Service::find($request->service_id);

        if (!$user->services->contains($service)) {
            return response()->json(['message' => 'Service non autorisé pour cet utilisateur.'], 403);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
        
        $path = $file->storeAs('documents', $uniqueFileName, 'private');

        $document = Document::create([
            'uuid' => (string) Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'user_id' => $user->id,
            'service_id' => $request->service_id,
        ]);

        event(new UserActionLogged($user, 'upload', 'a téléchargé le document: ' . $document->name));

        return response()->json($document, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::where('uuid', $uuid)->with('service')->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'service_id' => 'sometimes|required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $document = Document::where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            $document->update($request->only(['name', 'description', 'service_id']));

            event(new UserActionLogged(Auth::user(), 'update', 'a mis à jour le document: ' . $document->name));

            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            $document->delete(); // Soft delete

            return response()->json(['message' => 'Document mis à la corbeille.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        }
    }

    /**
     * Preview the specified document.
     *
     * @param  string  $uuid
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function preview(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            if (!Storage::disk('private')->exists($document->file_path)) {
                return response()->json(['message' => 'Fichier non trouvé sur le serveur.'], 404);
            }

            return Storage::disk('private')->response($document->file_path);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        }
    }

    /**
     * Download the specified document.
     *
     * @param  string  $uuid
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function download(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            if (!Storage::disk('private')->exists($document->file_path)) {
                return response()->json(['message' => 'Fichier non trouvé sur le serveur.'], 404);
            }

            event(new UserActionLogged(Auth::user(), 'download', 'a téléchargé le document: ' . $document->name));

            return Storage::disk('private')->download($document->file_path, $document->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        }
    }

    /**
     * Display a listing of the soft-deleted documents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $user = Auth::user();
        $query = Document::onlyTrashed()->where('user_id', $user->id);

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        $trashedDocuments = $query->with('service')->latest()->paginate(10);

        return response()->json($trashedDocuments);
    }

    /**
     * Restore a soft-deleted document.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::onlyTrashed()->where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            $document->restore();

            event(new UserActionLogged(Auth::user(), 'restore', 'a restauré le document: ' . $document->name));

            return response()->json(['message' => 'Document restauré avec succès.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé dans la corbeille.'], 404);
        }
    }

    /**
     * Permanently delete a document.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $uuid)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        try {
            $document = Document::onlyTrashed()->where('uuid', $uuid)->firstOrFail();

            if ($document->user_id !== Auth::id()) {
                return response()->json(['message' => 'Accès non autorisé à ce document.'], 403);
            }

            // Supprimer le fichier physique
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            // Log de l'action avant la suppression définitive
            event(new UserActionLogged(Auth::user(), 'force_delete', 'a supprimé définitivement le document: ' . $document->name));

            // Suppression définitive
            $document->forceDelete();

            return response()->json(['message' => 'Document supprimé définitivement.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Document non trouvé.'], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression définitive du document: ' . $e->getMessage());
            return response()->json(['message' => 'Une erreur est survenue lors de la suppression définitive.'], 500);
        }
    }
}
