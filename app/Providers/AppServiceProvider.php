<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Augmente la mémoire en environnement local pour éviter les erreurs 500 liées à la mémoire
        if (app()->environment('local')) {
            @ini_set('memory_limit', '2048M');
        }
    }
}
