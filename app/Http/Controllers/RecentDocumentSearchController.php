<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\DocumentShare;

class RecentDocumentSearchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $limit = (int) $request->query('limit', 10);
        $q = $request->query('q');
        $owner = $request->query('owner');

        $publishedQuery = $user->documents()->with(['user', 'service'])->latest();
        if ($q) {
            $publishedQuery->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('chemin', 'like', "%{$q}%");
            });
        }
        if ($owner) {
            $publishedQuery->whereHas('user', function ($uq) use ($owner) {
                $uq->where('name', 'like', "%{$owner}%");
            });
        }
        $published = $publishedQuery->take($limit)->get()->map(function ($doc) {
            $doc->source = 'published';
            $doc->action_date = $doc->created_at;
            return $doc;
        });

        $sharedIdsQuery = DocumentShare::where('user_id', $user->id)
            ->where(function ($q2) {
                $q2->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        if ($q) {
            $sharedIdsQuery->whereHas('document', function ($dq) use ($q) {
                $dq->where(function ($sub) use ($q) {
                    $sub->where('nom', 'like', "%{$q}%")
                        ->orWhere('chemin', 'like', "%{$q}%");
                });
            });
        }
        if ($owner) {
            $sharedIdsQuery->whereHas('document.user', function ($uq) use ($owner) {
                $uq->where('name', 'like', "%{$owner}%");
            });
        }
        $sharedDocumentIds = $sharedIdsQuery->latest('created_at')->pluck('document_id');

        $receivedQuery = Document::whereIn('id', $sharedDocumentIds)->with(['user', 'service'])->latest();
        $received = $receivedQuery->take($limit)->get()->map(function ($doc) {
            $doc->source = 'received';
            $doc->action_date = $doc->created_at;
            return $doc;
        });

        $all = $published->merge($received)->sortByDesc('action_date')->take($limit)->values();
        return response()->json(['data' => $all]);
    }
}
