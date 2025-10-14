<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost:8080', 
            'http://127.0.0.1:8000',
            'http://192.168.1.66:8081'  // Nouvelle adresse IP
        ];
        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With, X-CSRF-TOKEN, X-Auth-Token, Origin, Authorization',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Expose-Headers' => 'X-Auth-Token'
            ];
            
            // Gestion des requêtes OPTIONS (preflight)
            if ($request->isMethod('OPTIONS')) {
                return response('', 200)->withHeaders($headers);
            }
            
            $response = $next($request);
            
            // Ajouter les en-têtes à la réponse
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            
            return $response;
        }
        
        return $next($request);
    }
}
