<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        AuditTrail::log(
            event: 'logout',
            auditable: $event->user,
            newValues: [
                'message' => 'User logged out',
            ],
            tags: 'auth'
        );
    }
}
