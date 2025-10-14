<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PasswordGenerated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Traits\LogActionTrait;

class AuthController extends Controller
{
    use LogActionTrait;
    
    public function registerUser(Request $request)
    {
        // Validation
        try {
                $request->validate([
                    'nom' => 'required|string|max:255',
                    'prenom' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users,email',
                    'service_id' => 'required|exists:services,id',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'error' => 'Cette adresse email est déjà utilisée par un autre utilisateur.'
                ], 422);
            }

            $nom = $request->input('nom');
            $prenom = $request->input('prenom');
            $email = $request->input('email');
            $password = Str::random(8);

            // on genere le name unique de l'utilisateur
                $username = 'user' . now()->format('YmdHis');

            $utilisateur = User::create([
                'name' => $username,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password' => Hash::make($password),
                'service_id' => $request->input('service_id'),
            ]);

            // Envoi de l'email avec le mot de passe généré 
            // exception gérée : l'utilisateur est enregistré meme si l'envoi de l'email échoue
            try {
                Mail::to($email)->send(new PasswordGenerated($utilisateur, $password));
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return response()->json([
                'message' => 'Utilisateur créé avec succès! Vous avez reçu un mot de passe de connexion.',
                'user' => [
                    'id' => $utilisateur->id,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'email' => $utilisateur->email,
                    'name' => $username,
                    'password' => $password,
                    'service_id' => $utilisateur->service_id,
                ]
            ], 201);
    }

    public function authenticate(Request $request)
        {
            // Validation des données
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);
    
            // Tentative d'authentification
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Identifiants invalides'
                ], 401);
            }
    
            // Récupération de l'utilisateur
            $user = User::where('email', $request->email)->firstOrFail();
            
            // Création du token Sanctum
            $token = $user->createToken('yamsdigital')->plainTextToken;
    
            // Réponse avec le token et les infos utilisateur
            $user_datas =[
                'token' => $token,
                'user' => $user
            ];

            return $this->sendApiResponse($user_datas, 'Authentification réussie');
        }

    /**
     * Déconnexion de l'utilisateur (Sanctum)
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // DÉSACTIVÉ: Journalisation temporairement désactivée pour éviter la récursion infinie
        // $this->logAction('déconnexion', $user);

        // Supprime le token courant de l'utilisateur connecté
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
