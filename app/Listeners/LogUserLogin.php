<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        AuditTrail::log(
            event: 'login',
            auditable: $event->user,
            newValues: [
                'message' => 'User logged in',
            ],
            tags: 'auth'
        );
    }
}
