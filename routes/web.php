<?php

use App\Mail\PasswordGenerated;
use Illuminate\Support\Facades\Mail;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Route;


// Route pour servir le front Vue buildé
// Route::get('/{any}', function () {
//     return file_get_contents(public_path('dist/index.html'));
// })->where('any', '.*');