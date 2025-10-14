<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\LogAction;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event->user) {
            LogAction::create([
                'action' => 'Connexion',
                'user_id' => $event->user->id,
                'adresse_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'table_affectee' => 'users',
                'id_affecte' => $event->user->id
            ]);
        }
    }
}
