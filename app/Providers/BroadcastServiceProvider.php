<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Web (admin panel) uses session cookie auth
        Broadcast::routes([
            'middleware' => ['web', 'auth'],
        ]);

        require base_path('routes/channels.php');
    }
}
