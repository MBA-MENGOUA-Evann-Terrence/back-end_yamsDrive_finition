<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\Document;
use App\Observers\DocumentObserver;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Categorie;
use App\Observers\CategorieObserver;
use App\Models\PieceJointe;
use App\Observers\PieceJointeObserver;
use App\Models\CahierCharge;
use App\Observers\CahierChargeObserver;
use App\Models\ShareLink;
use App\Observers\ShareLinkObserver;
use App\Models\Souscription;
use App\Observers\SouscriptionObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],
        'App\Events\UserActionLogged' => [
            'App\Listeners\LogUserAction',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot()
    {
                // L'approche par observateurs a été retirée au profit d'un système d'événements/écouteurs explicites.
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
