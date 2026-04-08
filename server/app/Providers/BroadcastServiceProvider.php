<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes([
            'prefix' => 'api',
            // Keep auth lightweight here; presence/last-seen updates should not block channel auth.
            'middleware' => ['api', 'auth:api'],
        ]);

        require base_path('routes/channels.php');
    }
}
