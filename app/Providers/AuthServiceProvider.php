<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Model::class => Policy::class,
    ];

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {

            // 🔴 Absolute deny
            if ($user->isPermissionDenied($ability)) {
                return false;
            }

            // 🟢 Super Admin override
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // Delegate to Spatie Permission
            return null;
        });
    }
}
