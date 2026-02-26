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
            // Super Admin must always have full access.
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // Explicit deny applies to non-super-admin users only.
            if ($user->isPermissionDenied($ability)) {
                return false;
            }

            // Delegate to Spatie Permission
            return null;
        });
    }
}
