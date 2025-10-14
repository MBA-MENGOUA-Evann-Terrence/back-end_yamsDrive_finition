<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\LogActionTrait;

class LogoutController extends Controller
{
    use LogActionTrait;
    /**
     * Déconnecte l'utilisateur en invalidant son token Sanctum.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Aucun utilisateur authentifié.'
            ], 401);
        }

        $token = $user->currentAccessToken();

        if ($token) {
            // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
            // $this->logAction('déconnexion', $user);

            $token->delete();
            return response()->json([
                'message' => 'Déconnexion réussie.'
            ]);
        }

        return response()->json([
            'message' => 'Aucun token d\'accès trouvé.'
        ], 400);
    }
}
